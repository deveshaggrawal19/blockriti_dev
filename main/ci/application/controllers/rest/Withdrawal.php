<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Auth_Api.php');

class Withdrawal extends Auth_api
{

    protected $postData;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('deposit_model');
        $this->load->model('user_model');
        $this->load->model('admin_model');
        $this->load->model('withdrawal_model');
        $this->load->model('referral_model');
        $this->load->model('rest_user_model');
        $this->load->model('user_balance_model');
        $this->_obj_Memcache  = new Memcache();
        //Load user from session.
        if(empty($this->sessionData) === false) {
            $this->user = $this->rest_user_model->loadFromSession($this->sessionData);
        }
        if ($this->user_model->isPINLockedOut($this->userId)) {
            $this->_displayErrorUnauthorised(array("code" => 69));
        }
        $this->load->model('withdrawal_model');
    }
    /*
    * @param $error integer - has the following values
    * 80: Not valid page parameter
    * */
    public function withdrawlSummary() {
        $page = $this->postData['page'];
        if(!is_int($page)){
            $this->_displayBadRequest(array("code"=>80));
        }
        $this->withdrawal_model->getCountForUser($this->userId, 'all');
        if (!$page)
            $page = 1;
        $data['records'] = $this->withdrawal_model->getSubsetForUser($page, 20);
        $this->_display($data);
    }
    public function validate_pin($pin,&$error) {
        if (sha1($pin) !== $this->user->pin) {
            $this->rest_user_model->increasePINLockedOut($this->userId);
            $error = 1;
            return;
        }
        $error = 10;
        return;
    }

    public function valid_currency_format($input, $currency) {
        if (!isCurrency($currency, $input)) {
            $this->_displayBadRequest(array("code" => 72));
        }
        return;
    }

    public function has_enough_balance($amount, $currency) {
        if (bccomp($this->user->balances->{$currency . '_available'}, $amount, getPrecision($currency)) < 0) {
            $this->_displayBadRequest(array("code" => 73));
        }
        return;
    }

    public function valid_bitcoin_address($address) {
        if (!preg_match ('/^[13][1-9A-HJ-NP-Za-km-z]{20,40}$/', $address)) {
            $this->_displayBadRequest(array("code" => 74));
        }
        return;
    }
    /*
    * @param $error integer - has the following values
    * 71: Invalid pin/security code
    * 72: Invalid currency format
    * 73: Insufficient balance
    * 74: Invalid bitcoin address
    * 75: Amount is less than min limit
    * 76: Amount is greter than max limit
    * 77: Required fields should not be empty
    * 78: Unverified user
    * 79: Success
    * 81: Withdrawal disabled
    * */
    public function bitcoin() {
        if (count($this->postData) > 0){
            $this->checkBitcoinWithdrawalsData();
            $currency = 'btc';
            $balances = $this->user_balance_model->get($this->userId);
            $newBalance = bcsub($balances->$currency, $this->postData['amount'], getPrecision($currency));

            $str = "amount=".$this->postData['amount'].":::".
                "address=".$this->postData['address'].":::".
                "ip=".getIp().":::".
                "userId=".$this->userId.":::".
                "new_balance=".$newBalance;

            $this->check2FA($str);

            $this->valid_currency_format($this->postData['amount'],'btc');
            $this->has_enough_balance($this->postData['amount'],'btc');
            $this->valid_bitcoin_address($this->postData['address']);

            $data = array(
                'client'   => $this->userId,
                'method'   => 'btc',
                'currency' => 'btc',
                'amount'   => abs($this->postData['amount'])
            );

            $details = array(
                'address' => $this->postData['address']
            );

            if ($withdrawal = $this->withdrawal_model->add($data, $details)) {
                $this->_obj_Memcache->delete('user:' . $this->userId . ':withdrawals');
                $this->notification_model->pushUserNotification($this->userId);
                $this->_display(array("code" => 79));
            }
            else {
                header('Content-Type: application/json');
                header("HTTP/1.1 500 Internal Server Error");
            }
        }else{
            $this->_displayBadRequest(array("code" => 77));
        }
    }
    /*
       * @param $error integer - has the following values
       * 71: Invalid pin/security code
       * 72: Invalid currency format
       * 73: Insufficient balance
       * 74: Invalid bitcoin address
       * 75: Amount is less than min limit
       * 76: Amount is greater than max limit
       * 77: Required fields should not be empty
       * 78: Unverified user
       * 79: Success
       * */
    public function bankwire() {
        if ($this->user->verified == 0) {
            $this->_displayErrorUnauthorised(array("code" => 78));
        }
        $allowed = array(
            'cad' => array('min' => 100, 'max' => 500000)
        );

        $currency     = 'cad';

        if (count($this->postData) > 0){
            $min = $allowed[$currency]['min'];
            $max = $allowed[$currency]['max'];

            $this->valid_currency_format($this->postData['amount'],$currency);
            $this->has_enough_balance($this->postData['amount'],$currency);

            if($this->postData['amount'] < $min)
                $this->_displayBadRequest(array("code" => 75));
            if($this->postData['amount'] > $max)
                $this->_displayBadRequest(array("code" => 76));

            if(empty($this->postData['address'])||empty($this->postData['bank_name'])||empty($this->postData['bank_address'])||
                empty($this->postData['account'])||empty($this->postData['swift'])||empty($this->postData['instructions'])) {
                $this->_displayBadRequest(array("code" => 77));
            }

            $balances = $this->user_balance_model->get($this->userId);
            $newBalance = bcsub($balances->$currency, $this->postData['amount'], getPrecision($currency));

            $str = "amount=".$this->postData['amount'].":::".
                "address=".$this->postData['address'].":::".
                "bank_name=".$this->postData['bank_name'].":::".
                "bank_address=".$this->postData['bank_address'].":::".
                "account=".$this->postData['account'].":::".
                "swift=".$this->postData['swift'].":::".
                "ip=".getIp().":::".
                "userId=".$this->userId.":::".
                "new_balance=".$newBalance;
            
            $this->check2FA($str);

            $data = array(
                'client'   => $this->userId,
                'method'   => 'bw',
                'currency' => $currency,
                'amount'   => abs($this->postData['amount'])
            );
            $details = array(
                'address'      => $this->postData['address'],
                'bank_name'    => $this->postData['bank_name'],
                'bank_address' => $this->postData['bank_address'],
                'account'      => $this->postData['account'],
                'swift'        => $this->postData['swift'],
                'instructions' => $this->postData['instructions']
            );

            if ($withdrawal = $this->withdrawal_model->add($data, $details)) {
                if(!empty($this->postData['comments']))
                    $details['comments'] = $this->postData['comments'];
                $this->user_model->setUserWithdrawalDetails($this->userId, $details);
                systemEmail("Bank Wire withdrawal request made by User #".$this->userId." (".abs($this->postData['amount'])." ".$currency.")");
                $this->_obj_Memcache->delete('user:' . $this->userId . ':withdrawals');
                $this->notification_model->pushUserNotification($this->userId);
                $this->_displaySuccess(array("code" => 79));
            }
            else {
                header('Content-Type: application/json');
                header("HTTP/1.1 500 Internal Server Error");
            }
        }else{
            $this->_displayBadRequest(array("code" => 77));
        }

    }
    /*
   * @param $error integer - has the following values
   * 71: Invalid pin/security code
   * 72: Invalid currency format
   * 73: Insufficient balance
   * 74: Invalid bitcoin address
   * 75: Amount is less than min limit
   * 76: Amount is greter than max limit
   * 77: Required fields should not be empty
   * 78: Unverified user
   * 79: Success
   * */
    public function cheque() {

        $this->load->model('referral_model');
        $this->load->model('trade_model');
        if ($this->user->verified == 0) {
           $this->_displayErrorUnauthorised(array("code" => 78));
        }

        $allowed = array(
            'cad' => array('min' => 100, 'max' => 500000)
        );
        $currency     = 'cad';
        $min = $allowed[$currency]['min'];
        $max = $allowed[$currency]['max'];

        if (count($this->postData) > 0){
            $balances = $this->user_balance_model->get($this->userId);
            $newBalance = bcsub($balances->$currency, $this->postData['amount'], getPrecision($currency));

            $str = "amount=".$this->postData['amount'].":::".
                "address=".$this->postData['address'].":::".
                "ip=".getIp().":::".
                "userId=".$this->userId.":::".
                "new_balance=".$newBalance;

            $this->check2FA($str);
            if($this->postData['amount'] < $min)
                $this->_displayBadRequest(array("code" => 75));
            if($this->postData['amount'] > $max)
                $this->_displayBadRequest(array("code" => 76));

            $this->valid_currency_format($this->postData['amount'],$currency);
            $this->has_enough_balance($this->postData['amount'],$currency);
            if(empty($this->postData['address'])) {
                $this->_displayBadRequest(array("code" => 77));
            }
            $data = array(
                'client'   => $this->userId,
                'method'   => 'ch',
                'currency' => $currency,
                'amount'   => abs($this->postData['amount'])
            );
            $details = array(
                'address'      => $this->postData['address'],
            );
            if ($withdrawal = $this->withdrawal_model->add($data, $details)) {
                $this->_obj_Memcache->delete('user:' . $this->userId . ':withdrawals');
                $this->notification_model->pushUserNotification($this->userId);
                $this->_displaySuccess(array("code" => 79));
            }
            else {
                header('Content-Type: application/json');
                header("HTTP/1.1 500 Internal Server Error");
            }
        }else{
            $this->_displayBadRequest(array("code" => 77));
        }
    }
    private function check2FA($str = null){
        $error = 1;
        $code = empty($this->postData['code']) ? '' : $this->postData['code'];
        if(property_exists($this->user, 'twofa_type')){
            switch($this->user->twofa_type){
                case '2fauth':
                    $this->rest_user_model->validateTwoFACode($code,$this->user->twofa_secret,$error);
                    break;
                case 'pin':
                    $this->validate_pin($code, $error);
                    break;
                case 'pmail':
                    $this->validatePMail($code, $str, $error);
                    break;
                case 'psms':
                    $this->validatePMail($code, $str, $error);
                    break;
            }
        } else {
                $this->validate_pin($this->postData['code'],$error);
        }
        if($error != 10){
            $this->_displayBadRequest(array("code" => 71));
        }
        return;
    } 

    public function validatePMail1($code, $str = null, &$error) {
        if(!is_null($str)){
            $type = true;
        } else {
            $type = false;
        }

        $this->load->library('Protectimus');
        $api = $this->protectimus->getApi();
        $str = str_replace(":::","|",$str);

        if($type){
            $response = $api->verifySignedTransaction($this->user->token_id, $str, hash_hmac("sha256", $str, $this->protectimus->getApiKey()), $code);
        } else {
            $response = $api->authenticateToken($this->protectimus->getResourceId(), $this->user->token_id, $code, null);
        }

        $authenticationResult = $response->response->result;

        if(!$authenticationResult || $authenticationResult == '') {
            $error = 1;
            return;
        }
        $error = 10;
        return;
    }
    public function checkBitcoinWithdrawalsData() {
        if($this->admin_model->getBitcoinWithdrawalsData('status') == "disabled") {
            $this->_displayErrorUnauthorised(array("code" => 81));
        }
        return;
    }
    /* Function to withdraw via coupon
    * @param code integer - has the following values
    *  191. Not verified user
    *  192. Amount should be in digits
    *  193. Amount exceeds available balance
    *  194. Amount is greater than allowed balance
    *  195. Amount should be greater than 5 
    *  196. Please enter values
    *  71. Please enter valid authentication code
    *  200. Success
    * */
    public function coupon(){
        if ($this->user->verified == 0) {
            $this->_displayErrorUnauthorised(array("code" => 191));
        }
        $data['verified'] = $this->user_model->verified;
        $this->load->model('voucher_model');
        $this->load->helper('string');
        $currency = 'cad';
        $twoFASecured = $this->user->twofa_status == '1';
        $balances = $this->user_balance_model->get($this->userId);
        if (count($this->postData) > 0) {

        if(!preg_match("/^[0-9]*$/", $this->postData['amount']))
            $this->_displayBadRequest(array("code" => 192));
        if($this->postData['amount'] > $balances->{$currency.'_available'})
            $this->_displayBadRequest(array("code" => 193));
        if($this->postData['amount'] > 999.99)
            $this->_displayBadRequest(array("code" => 194));
        if($this->postData['amount'] < 5)
            $this->_displayBadRequest(array("code" => 195));

        $newBalance = bcsub($balances->$currency, $this->postData['amount'], getPrecision($currency));

            $str = "amount=".$this->postData['amount'].":::".
                "ip=".getIp().":::".
                "userId=".$this->userId.":::".
                "new_balance=".$newBalance;
            $this->check2FA($str);

                $code = $this->voucher_model->generateCode();

                $dataWidthdrawal = array(
                    'client'   => $this->userId,
                    'method'   => 'cou',
                    'currency' => 'cad',
                    'amount'   => abs($this->postData['amount']),
                    'couponCode' => $code
                );

                $details = array();
                $withdrawal = $this->withdrawal_model->addComplete($dataWidthdrawal, $details);

                $data = array(
                    'value'    => $this->postData['amount'],
                    'currency' => $currency,
                    'expiry'   => $this->postData['expiry'],
                    'user_id'  => $this->userId,
                    'referrer' => $this->userId,
                    'code'     => $code,
                    'withdrawal' => $withdrawal
                );

                $data['voucher'] = $this->voucher_model->userGenerate($data);
                $user = $this->user_model->getUser($this->userId);
                $data['name']     = $user->first_name;

                    $this->load->library('Mandrilllibrary');
                    $api = $this->mandrilllibrary->getApi();

                    $name = 'withdrawal_coupon';
                    $template = $api->templates->info($name);
                    $templateContent = array(
                        array(
                            'name' => 'editable',
                            'content' => $template['code']
                        )
                    );

                $mergeVars = array(
                    array(
                        'name' => 'name',
                        'content' => $data['name']
                    ),
                    array(
                        'name' => 'value',
                        'content' => $data['value']
                    ),
                    array(
                        'name' => 'voucher',
                        'content' => $data['voucher']
                    )
                );

                    $resultRender = $api->templates->render($name, $templateContent, $mergeVars);

                    $htmlContent = $resultRender['html'];

                    $pgpData = array();
                    $pgpData['content'] = $resultRender['html'];
                    if(isset($user->pgp_status) && $user->pgp_status == 1) {
                        $pgpData['key'] = $user->pgp_key;
                        $pgpData['content'] = strip_tags($resultRender['html']);
                        $encryptMessage = $this->mandrilllibrary->send("/pgpEncrypt","POST",$pgpData);

                        if(isset($encryptMessage->message) && $encryptMessage->message != ''){
                            $htmlContent = $this->mandrilllibrary->formatMessage($encryptMessage->message);
                        }
                    } else {
                        $encryptMessage = $this->mandrilllibrary->send("/pgpSign","POST",$pgpData);
                        $htmlContent = $this->mandrilllibrary->formatSign($encryptMessage->message);
                    }


                    $message = array(
                        'html' => $htmlContent,
                        'subject' => $this->config->item('site_full_name') . ' - ' . _l('coupon_email_subject'),
                        'from_email' => 'support@taurusexchange.com',
                        'from_name' => 'whitebarter Bitcoin Exchange',
                        'to' => array(
                            array(
                                'email' => $user->email,
                                'name' => 'Recipient Name',
                                'type' => 'to'
                            )
                        ),
                        'headers' => array('Reply-To' => $user->email),

                    );

                    $result = $api->messages->send($message);
            $this->notification_model->pushUserNotification($this->userId);
            $this->_obj_Memcache->delete('user:' . $this->userId . ':withdrawals');
            $this->_displaySuccess(array("code"=>200));

        }else {
            $this->_displayBadRequest(array("code" => 196));
        }

    }
}