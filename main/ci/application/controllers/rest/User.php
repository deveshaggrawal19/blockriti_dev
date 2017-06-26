<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Auth_Api.php');
use google\appengine\api\cloud_storage\CloudStorageTools;
use \Firebase\JWT\JWT;
class User extends Auth_api
{

    protected $postData;

    private $locks = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->model('rest_user_model');
        $this->load->model('event_model');
        $this->load->model('referral_model');
        $this->load->library('api_security');
        $this->load->library('api_model');
        $this->_obj_Memcache  = new Memcache();
        //Load user from session.
        if(empty($this->sessionData) === false) {
            $this->user = $this->rest_user_model->loadFromSession($this->sessionData);
        }
    }

    protected function _validateFields($required, $base) {
        $i = 0;
        foreach ($required as $req => $code) {
            if (empty($this->_getProperty($req))) {
                $this->_displayBadRequest(array("code" => ($base + intval($code) ) ) );
            }
            $i++;
        }
    }

    protected function _setProperties(){
        if (empty($this->postData) === false) {
            foreach ($this->postData as $key => $value) {
                $this->properties->{$key} = $value;
            }
        }
    }

    protected function _getProperty($key) {
        return isset($this->properties->{$key}) ? $this->properties->{$key} : null;
    }
    private function _checkFirstLogin($user){
        if (empty($user->pin)) {
            return true;
        } else {
            return false;
        }
    }

    public function login()
    {
          if (empty($this->postData) === false) {

            $clientId = $this->postData['id'];
            $password = $this->postData['password'];

            $error = 0;

            if ($user = $this->rest_user_model->login($clientId, $password, $error)) {
                $aSessionArray = $this->rest_user_model->saveSession($user);

            }

            if ($error == 10 && isset($user)) {
                if (!empty($user->twofa_type)) {
                    $aSessionArray['type'] = $user->twofa_type;
                } else {
                    $aSessionArray['type'] = "pin";
                }
                if ($user->twofa_status !== '0') {
                    if ($user->sms_status == '1'){
                        $this->load->library('msg91');
                        $rs = $this->msg91->sendOtp($user->_id);
                    }
                }
                $aSessionArray = array_merge($aSessionArray, $this->getLimits());
                $aSessionArray['first_login'] = $this->_checkFirstLogin($user);
                $this->notification_model->pushUserNotification($user->_id);
                $this->_displaySuccess($aSessionArray);

            } else {
                switch ($error) {
                    case 1:
                    case 2:
                    case 3:
                    case 4:
                        header('Content-Type: application/json');
                        header("HTTP/1.1 401 Unauthorized");
                        $this->_display(array("code" => $error + 10));
                        break;
                    default:
                        header('Content-Type: application/json');
                        header("HTTP/1.1 500 Internal Server Error");
                        break;
                }

            }

        }
    }

    public function logout()
    {  var_dump($this->userId);
        $code = $this->rest_user_model->logout($this->userId);
        if ($code == 10) {
            $this->_displaySuccess(array("code" => $code + 150));
        } else {
            switch ($code) {
                case 1:
                default:
                    $this->_displayErrorInternal(array("code" => $code + 150) );
                    break;
            }
        }
    }

    /* Function to checck Google Authentication code.
        * @param $error integer - has the following values
        *  1. Code is incorrect
        *  2. Account blocked due too many failed attempts
        *  3. Missing account in protectimus
        *  4. Wrong Twofa Method.
        *  9. Unreconised authentication method.
        *  10. Success
        * */
    public function authenticate() {

        if (empty($this->postData) === FALSE  && count($this->postData) > 0){
            $error = 0;
            $sUserID = $this->postData['user_id'];
            $this->user = $this->rest_user_model->get($sUserID);
            if(empty($this->user->twofa_type))
                $this->user->twofa_type = 'pin';
            if($this->user->twofa_type != $this->postData['type'])
                $this->_displayBadRequest(array("code" => 34) );

            switch ($this->postData['type']) {
                case '2fauth':
                    $this->rest_user_model->validateTwoFACode($this->postData['code'],$this->user->twofa_secret,$error);
                    break;
                case 'pmail':
                    $this->validatePMail($this->postData['code'],$error);
                    break;
                case 'psms':
                    //$this->validatePMail($this->postData['code'],$error);
                    $this->load->library('msg91');
                    $error = $this->msg91->verify_otp($this->postData['code'], $sUserID);

                    break;
                case 'pin':
                    $this->validate_pin($this->postData['code'],$error);
                    break;
                default:
                    $error = 9;
                    break;
            }
            if ($error == 10) {
                header('Content-Type: application/json');
                header("HTTP/1.1 200 OK");
                $sSessionID = $this->postData['session_id'];
                $aSessionData = $this->rest_user_model->getUserSessionData($sUserID);
                if(trim($sSessionID) == trim($aSessionData['body']['_id']) )
                {
                    $this->_display(array("code" => $error + 30,
                        "_token" => $aSessionData['body']['_token'],
                        "_accessToken" => $aSessionData['body']['_accessToken']) );
                }
                else
                {
                    $this->_displayErrorUnauthorised(array("code" => 33) );
                }

            } else {
                switch ($error) {
                    case 1:
                        $this->_displayErrorUnauthorised(array("code" => $error + 30));
                        break;
                    case 2:
                        $this->_displayBadRequest(array("code" => $error + 30));
                        break;
                    case 9:
                        $this->_displayBadRequest(array("code" => $error + 30));
                        break;
                    default:
                        header('Content-Type: application/json');
                        header("HTTP/1.1 500 Internal Server Error");
                        break;
                }

            }
        }
    }
    public function pay(){
            if (empty($this->postData) === FALSE  && count($this->postData) > 0){
                $uid1 = $this->postData['uid1'];
                $user1 = $this->rest_user_model->get($uid1);

                if($user1){
                    if (sha1($this->postData['pin']) !== $user1->pin) {
                        $this->_displayErrorUnauthorised(array("code" => 152) ); //Unathorized Invalid Username & Password
                    } else {
                        $uid2 = $this->postData['uid2'];
                        $user2 = $this->rest_user_model->get($uid2);
                        if($user2){
                            $amount = $this->postData['amount'];
                            $balances = $this->user_balance_model->get($uid1);
                            if($balances->btc_available){
                                $this->user_balance_model->transferBalance($uid1, $uid2, $amount);
                                $this->_display( array("code" => 151) );
                            } else{
                                $this->_display( array("code" => 153) ); // Insufficient balance
                            }
                        } else {
                            $this->_display( array("code" => 154) ); // seller not found
                        }
                    }
                } else {
                    $this->_displayErrorUnauthorised( array("code" => 152) ); //Unathorized Invalid Username & Password
                }
            }
    }
    public function confirm_order($type = null){ // $type = buy/sell
        $this->load->model('exchange_model');
        $this->load->model('user_balance_model');
        $this->load->model('brokerage_order_model');

        if(!$type){
            $type = $this->postData['type'];
        }

        $amount = $this->postData['amount'];
        $amount_value = $amount;
        $currency = $this->postData['currency'];

        if(!$amount || !$currency || !$type){
            $this->_display(array("code" => 154)); // missing fields data
        } else {

            $currency2 = ($currency == 'btc') ? 'cad' : 'btc';
            $brokerage = $this->exchange_model->getBrokerage();
            $brokerage_fee = $this->exchange_model->getBrokerageFee();
            $balances = $this->user_balance_model->get($this->userId);

            $rate = $brokerage[$type];
            //$amount_value = $amount * $rate;
            //$m = $currency2 . "_available";

            $balance_currency = ($type == 'buy')?"cad":'btc';
            $m = ($type == 'buy')?"cad_available":'btc_available';
            if($type == 'buy'){
                $amt = ($currency == 'cad')?$amount:($amount * $rate);
                $amount_value = ($currency == 'cad')?bcmul((1/$rate), $amount, getPrecision($currency2)):bcmul($rate, $amount, getPrecision($currency2));
            } else {
                $amt = ($currency == 'btc')?$amount:($amount * $rate);
                $amount_value = ($currency == 'btc')?bcmul((1/$rate), $amount, getPrecision($currency2)):bcmul($rate, $amount, getPrecision($currency2));
            }

            echo "m:". $m;
            echo ", amt:" . $amt;
            print_r($balances);


            if ($balances->$m >= ( $amt + ($brokerage_fee / 100) * $amt ) ) {
                $order_data = new stdClass();
                $order_data->major = $currency;
                $order_data->minor = $currency2;
                $order_data->amount = $amount;
                $order_data->brokerage_fee = $brokerage_fee;
                $order_data->rate = $rate;
                $order_data->method = 'api';
                $order_data->value = $amount_value;

                $order = $this->brokerage_order_model->create($this->userId, $order_data, $type);
                $this->brokerage_order_model->add($order);

                if($type == 'buy'){
                    if($currency == 'cad'){
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'cad', $amount + (($brokerage_fee / 100) * $amount) , 'sub');
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'btc', $order->value , 'add');
                    } else {
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'cad', $order->value + + (($brokerage_fee / 100) * $order->value), 'sub');
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'btc', $amount , 'add');
                    }
                } else {
                    if($currency == 'cad'){
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'btc', $order->value + (($brokerage_fee / 100) * $order->value), 'sub');
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'cad', $amount , 'add');
                    } else {
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'btc', $amount + (($brokerage_fee / 100) * $amount), 'sub');
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'cad', $order->value , 'add');
                    }
                }

                $this->_display(array("code" => 152));
            } else {
                $this->_display(array("code" => 153)); // Insufficient balance
            }
        }
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



    public function validatePMail($code,&$error) {
        $this->load->library('Protectimus');
        try {
            $api = $this->protectimus->getApi();

            $response = $api->authenticateToken($this->protectimus->getResourceId(), $this->user->token_id, $code, null);
            $authenticationResult = $response->response->result;

            if(!$authenticationResult || $authenticationResult == '') {
                $error = 1;
            }
            else
            {
                $error = 10;
            }
        }
        catch (Exception\ProtectimusApiException $e) {
            switch ($e->errorCode) {
                case 'ACCESS_RESTRICTION':
                    $error = 2;
                    break;
                case 'MISSING_DB_ENTITY':
                    $error = 3;
                    break;
            }
        }
        return;
    }
    public function two_factor_authentication_dependancies($type = null) {
        if($this->user->twofa_type !== '2fauth' && $type == '2fauth'){
            $this->load->helper('GoogleAuthenticator');
            $ga = new PHPGangsta_GoogleAuthenticator();
            $secret = $ga->createsecret();

            $width = 150;
            $height = 150;

            $qrCodeUrl = $ga->getQRCodeGoogleUrl($this->config->item('site_name'), $secret, $width, $height);

            $data['secret'] = $secret;
            $data['qrUrl']  = $qrCodeUrl;

            $this->_display($data);
        }
        if($this->user->twofa_type !== 'pemail' && $type == 'pemail'){
            $data['email'] = $this->user->email;
            $this->_display($data);
        }
    }
    /* error 10 - success
     * error 1 - phone no not found
     *
     *
     */
    public function send_otp(){
        $this->load->library('msg91');
        $error = $this->msg91->sendOtp($this->userId);
        $this->_displayBadRequest(array("code" => $error ));

    }
    /*
     * error 10 - success
     * error 1 - fail
     *
     *
     */
    public function verify_otp(){
        $this->load->library('msg91');
        $otp = $this->postData['otp'];
        $error = $this->msg91->verify_otp($otp, $this->userId);
        $this->_displayBadRequest(array("code" => $error + 100));

    }

    /*
     * 101 : Incorrect authentication method
     * 102 : Incorrect telephone number
     * 103 : Token access restricted
     * 104 : Database entry already deleted.
     * 105 : Protectimus response pending, please check after 5 minutes.
     */
    public function two_factor_authentication($type = null) {
        $sStatus = $this->postData['enable'];
        $status = ($sStatus === 'true')? 'enable': 'disable';
        $error = 1; $reset = '';
        $this->load->library('Protectimus');
        $api = $this->protectimus->getApi();
        switch ($status) {
            case 'enable':
                    switch ($type) {
                        case '2fauth':
                            if (count($this->postData) > 0) {
                            $secret = $this->postData['two_factor_secret'];
                            $this->rest_user_model->validateTwoFACode($this->postData['code'],$secret, $error);
                                if ($error == 10) {
                                    // Ok set up the TWO FA for that user
                                    $reset = generateRandomString();

                                    $data = array(
                                        'twofa_status' => '1',
                                       'twofa_secret' => $secret,
                                       'twofa_reset' => $reset,
                                        'twofa_type' => '2fauth'
                                    );

                                    $this->user_model->save($data, $this->userId);

                                    // Log 2fa enabled
                                    $this->event_model->add($this->userId, '2faon');
                                }
                            }
                        break;
                        /*case 'pmail':
                            try {
                                $response = $api->addSoftwareToken(null, null, "MAIL",
                                    $this->user->email, "Mail token " . $this->user->email,
                                    null, null, 6, null, null, null);
                                if(!empty($response->status) && $response->status == "OK") {
                                    $result = $api->assignTokenToResource($this->protectimus->getResourceId(), null, $response->response->id);
                                    if (!empty($result->status) && $result->status == "OK") {
                                        $data = array(
                                            'twofa_status' => '1',
                                            'twofa_type' => 'pmail',
                                            'token_id' => $response->response->id
                                        );

                                        $this->user_model->save($data, $this->userId);

                                        // Log 2fa enabled
                                        $this->event_model->add($this->userId, '2faon');
                                        $error = 10;
                                    }
                                }

                            }catch (Exception\ProtectimusApiException $e) {
                                switch ($e->errorCode) {
                                    case 'INVALID_PARAMETER':
                                        $error = 2;
                                        break;
                                    case 'ACCESS_RESTRICTION':
                                        $error = 3;
                                        break;
                                    case 'MISSING_DB_ENTITY':
                                        $error = $this->retryAssignEmailToken($response->response->id);
                                        break;
                                }
                            }
                            catch(Exception $e){
                                //Something went wrong while parsing the HTTP response from Protectimus
                                $error = 5;
                            }
                            break;
                    */
                        case 'psms':
                            $data = array(
                                'twofa_status' => '1',
                                'sms_status' =>'1',
                                'twofa_type' => 'psms'
                            );
                            $this->user_model->save($data, $this->userId);
                            //$this->event_model->add($this->userId, '2faoff');
                            $this->_displaySuccess(array("code" => $error + 100));
                            break;

                    }
                break;
            case 'disable':
                    switch ($type) {
                        case '2fauth':
                            $data = array(
                                'twofa_status' => '0',
                                'twofa_secret' => '',
                                'twofa_reset' => '',
                                'secure_withdrawals' => '0',
                                'twofa_type' => 'pin'
                            );

                            $this->user_model->save($data, $this->userId);
                            // Log 2fa enabled
                            $this->event_model->add($this->userId, '2faoff');
                            $error = 10;
                            break;
                        /*case 'pmail':
                            try {
                                $data = array(
                                    'twofa_status' => '0',
                                    'twofa_type' => 'pin',
                                    'token_id' => ''
                                );

                                $this->user_model->save($data, $this->userId);
                                // Log 2fa enabled
                                $this->event_model->add($this->userId, '2faoff');
                                $error = 10;
                                $api->unassignTokenFromResource($this->protectimus->getResourceId(),
                                    null, $this->user->token_id);
                                $api->deleteToken($this->user->token_id);
                            }
                            catch(Exception\ProtectimusApiException $e){
                                switch ($e->errorCode) {
                                    case 'MISSING_DB_ENTITY':
                                        $error = 10; // 4; As we are removing token_id prior to protectimus response so no need to send error to user.
                                        break;
                                }
                            }
                            catch(Exception $e){
                                //Something went wrong while parsing the HTTP response from Protectimus
                                $error = 10;// 5; As we are removing token_id prior to protectimus response so no need to send error to user.
                            }
                            break;
                            */
                        case 'psms':
                            try {
                                $data = array(
                                    'sms_status' =>'0',
                                    'user_otp' => '',
                                    'twofa_type' => 'pin'
                                );
                                $this->user_model->save($data, $this->userId);
                                // Log 2fa enabled
                                $this->event_model->add($this->userId, '2faoff');
                                $error = 10;
                            }
                            catch(Exception\ProtectimusApiException $e)
                            {
                                switch ($e->errorCode)
                                {
                                    case 'MISSING_DB_ENTITY':
                                        $error = 10; // 4; As we are removing token_id prior to protectimus response so no need to send error to user.
                                        break;
                                }
                            }
                            catch(Exception $e){
                                //Something went wrong while parsing the HTTP response from Protectimus
                                $error = 10;// 5; As we are removing token_id prior to protectimus response so no need to send error to user.
                            }
                            break;
                    }
                if ($error == 10) {
                    $this->_displaySuccess(array("code" => $error + 100));
                } else {
                    $this->rest_user_model->clearProtectimusGarbage();
                    switch ($error) {
                        case 1://Incorrect authentication code
                            $this->_displayBadRequest(array("code" => $error + 100));
                            break;
                        case 2: //incorrect telephone no
                            $this->_displayBadRequest(array("code" => $error + 100));
                            break;
                        case 3: //tocken access restricted
                            $this->_displayBadRequest(array("code" => $error + 100));
                            break;
                        case 4: //Database entry already deleted.
                            $this->_displayBadRequest(array("code" => $error + 100));
                            break;
                        case 5: //Something went wrong while parsing the HTTP response from Protectimus
                            $this->_displayErrorInternal(array("code" => $error + 100, "body" => $e->getMessage() ) );
                            break;
                        default:
                            header('Content-Type: application/json');
                            header("HTTP/1.1 500 Internal Server Error");
                            break;
                    }

                }
                break;
        }
    }
    public function retryAssignEmailToken($responseId){
        $api = $this->protectimus->getApi();
        try {
            $result = $api->assignTokenToResource($this->protectimus->getResourceId(), null, $responseId);
            if (!empty($result->status) && $result->status == "OK") {
                $data = array(
                    'twofa_status' => '1',
                    'twofa_type' => 'pmail',
                    'token_id' => $responseId
                );

                $this->user_model->save($data, $this->userId);

                // Log 2fa enabled
                $this->event_model->add($this->userId, '2faon');
                return 10;
            }
        }catch (Exception\ProtectimusApiException $e) {
            switch ($e->errorCode) {
                case 'INVALID_PARAMETER':
                    return 2;
                    break;
                case 'ACCESS_RESTRICTION':
                    return 3;
                    break;
                case 'MISSING_DB_ENTITY':
                    return 4;
                    break;
            }
        }
    }
    public function changePassword(){
        if (count($this->postData) > 0) {
            $password       = $this->postData['currentPassword'];
            $newPassword    = $this->postData['newPassword'];
            $data = array();
            // Check if the password that has been provided matches the current one
            $clientPasswordHash = $this->api_security->hash($this->userId, trim($password));
            $passwordHash = $this->api_security->hash($clientPasswordHash, $this->user->salt);

            if (!empty($newPassword) && $passwordHash != $this->user->password) {
                $this->_displayBadRequest(array("code" => 122));
            }
            //Check new password matching or not
            if(!empty($newPassword) && $this->postData['newPassword'] !== $this->postData['retypePassword']){
                $this->_displayBadRequest(array("code" => 123));
            }
                if ($newPassword) {
                    $salt = generateRandomString(20);
                    $data['salt']     = $salt;
                    $clientPasswordHash = $this->api_security->hash($this->userId, trim($this->postData['newPassword']));
                    $data['password'] = $this->api_security->hash($clientPasswordHash, $salt);
                }
                if ($user = $this->user_model->update($this->userId, $data)) {
                    $this->_displaySuccess(array("code" => 130));
                } else{
                    header('Content-Type: application/json');
                    header("HTTP/1.1 500 Internal Server Error");
                }

        }
    }
    public function unique_email($email) {
        if ($this->user_model->uniqueEmail(strtolower($email)))
            return TRUE;
        else
            return FALSE;
    }
    function valid_recaptcha($code) {
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $this->config->item('recaptcha_secret_key') . '&response=' . $code;

        if ($this->config->item('recaptcha_user_ip'))
            $url .= '&remoteip=' . getIp();

        $response = json_decode(file_get_contents($url));

        if ($response->success === FALSE)  {
            return 1;
        }

        return 10;
    }

    /* Function to checck Google Authentication code.
     * @param $error integer - has the following values
     *  131. Incorrect email
     *  132. Confirm password does not match
     *  133. Email already exist
     *  134. First name can not be empty
     *  135. Last name can not be empty
     *  136. Wrong recaptcha.
     *  140. Success
     * */
    public function register() {
            if (count($this->postData) > 0) {
                //Check valid email
                if(!filter_var($this->postData['email'], FILTER_VALIDATE_EMAIL)){
                    $this->_displayBadRequest(array("code" => 131));
                } 
                //Check new password matching or not
                if($this->postData['password'] !== $this->postData['confirm_password']){
                    $this->_displayBadRequest(array("code" => 132));
                }
                //Check email already exist or not
                if(!$this->unique_email($this->postData['email'])){
                    $this->_displayBadRequest(array("code" => 133));
                }
                //Check empty first name
                if(!sizeof($this->postData['first_name'])>0){
                    $this->_displayBadRequest(array("code" => 134));
                }

                //Check empty last name
                if(!sizeof($this->postData['last_name'])>0){
                    $this->_displayBadRequest(array("code" => 135));
                }
                //Check empty first name
                if(!sizeof($this->postData['phone'])>0){
                    $this->_displayBadRequest(array("code" => 155));
                }

                //Check recaptcha
                if($this->valid_recaptcha($this->postData['g-recaptcha-response']) == 1){
                    $this->_displayBadRequest(array("code" => 888));
                }


                $salt     = generateRandomString(20);
                $password = trim($this->postData['password']);
                $userData = array(
                    'first_name'=> $this->postData['first_name'],
                    'last_name'=> $this->postData['last_name'],
                    'phone' => $this->postData['phone'],
                    'email'=> $this->postData['email'],
                    'language' => $this->config->item('default_lang'),
                    'salt'=> $salt,
                    'password' => $password,
                    'twofa_status' => '0',
                    'active'   => 1
                );

                if ($userId = $this->rest_user_model->register($userData)) {
                    // All good 
                    $user = $this->user_model->get($userId);
                    $data['clientId'] = $userId;
                    $data['name']     = $this->postData['first_name'];
                    $secret = JWT::encode(array('email'=>$this->postData['email'],'secret'=>md5($this->postData['email'].":::".date("Y-m-d"))), DEFAULT_TOKEN, JWT_ALGORITHM);
                    $data['link'] = 'https://www.whitebarter.in/#/verify_email?q='.$secret;

                    $this->load->library('Mandrilllibrary');
                    $api = $this->mandrilllibrary->getApi();

                    $name = 'welcome';
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
                            'name' => 'clientid',
                            'content' => $data['clientId']
                        ),
                        array(
                            'name' => 'link',
                            'content' => $data['link']
                        )
                    );

                    $resultRender = $api->templates->render($name, $templateContent, $mergeVars);

                    $htmlContent = $resultRender['html'];

             /*       $pgpData = array();
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
               */
                    $message = array(
                        'html' => $htmlContent,
                        'subject' =>  _l('welcome_to') . ' ' . $this->config->item('site_full_name'),
                        'from_email' => 'shrikant@alulimtech.com',
                        'from_name' => 'BTC Monk',
                        'to' => array(
                            array(
                                'email' => $user->email,
                                'name' => 'Recipient Name',
                                'type' => 'to'
                            )
                        ),
                        'headers' => array('Reply-To' => $user->email),

                    );

                    $api->messages->send($message);

                    //Save referrer details
                    if(isset($this->postData['referrer'])) {
                        $referrerCode = $this->postData['referrer'];
                        $referrerData = $referrerCode ? $this->referral_model->findByCode($referrerCode) : null;
                        if (!empty($referrerData) && $referrerCode) {
                            $this->referral_model->addToUser($userId, $referrerData->id);
                            $this->_obj_Memcache->delete('user:' . $referrerData->id . ':referrals');
                        }
                    }
                    // Log registration event
                    $this->event_model->add($userId,'register');
                    $this->notification_model->pushUserNotification($userId);
                    $this->_displaySuccess(array("code" => 140));
                }
                else {
                    header('Content-Type: application/json');
                    header("HTTP/1.1 500 Internal Server Error");
                }
                
            } else {
                header('Content-Type: application/json');
                header("HTTP/1.1 500 Internal Server Error");
            }
    }
    /* Function to verify users Email
     * @param code integer - has the following values
     *  141. Incorrect or outdated link to verify the email
     *  142. Malformed token.
     *  150. Success
     * */
    public function verifyEmail(){
        if(count($this->postData) > 0){
            try{
                $data = JWT::decode($this->postData['data'], DEFAULT_TOKEN, array('HS256'));
                $email = $data->email;
                $hash = $data->secret;
                if (is_null($email) || is_null($hash) || md5($email . ":::" . date("Y-m-d")) != $hash) {
                    $this->_displayBadRequest(array("code" => 141));
                } else {
                    $user = $this->rest_user_model->findUserByEmail($email);
                    $userData['isVerifyEmail'] = 1;
                    $this->user_model->update($user->id, $userData);
                    $this->_displaySuccess(array("code" => 150));
                }
            }
            catch(DomainException $e){
                if($this->getProtectedMember($e,'message' ) == 'Syntax error, malformed JSON'){
                    $this->_displayBadRequest(array("code" => 142));
                }
            }
            header('Content-Type: application/json');
            header("HTTP/1.1 500 Internal Server Error");
        }
    }

    function getProtectedMember($class_object,$protected_member) {
        $array = (array)$class_object;      //Object typecast into (associative) array
        $prefix = chr(0). '*' .chr(0);           //Prefix which is prefixed to protected member
     return $array[$prefix.$protected_member];
    }

    private function _getGoogleStorageBucketName(){
        $sAppID = $this->config->item('google_app_id');
        return $sAppID.'.appspot.com';
    }

    private function _getCloudStorageURL($sUploadHandler){
        $options = ['gs_bucket_name' => $this->_getGoogleStorageBucketName()];
        return CloudStorageTools::createUploadUrl($sUploadHandler, $options);
    }

    public function _getPhotoIDUploadPath(){
        return $this->_getCloudStorageURL('/Upload/photoID');
    }

    private function _getUtilityBillUploadPath(){
        return $this->_getCloudStorageURL('/Upload/UtilityBill');
    }

    private function _getBankLetterUploadPath(){
        return $this->_getCloudStorageURL('/Upload/BankLetter');
    }

    /*
     * Function to Perform the User Verification
     * Returns the following Error Codes
     * 1161 : First Name Missing.
     * 1162 : Last Name Missing.
     * 1163 : Date Of Birth Missing.
     * 1164 : Address Missing.
     * 1165 : City Missing.
     * 1166 : State/Province Missing.
     * 1167 : Country Missing.
     * 1168 : Zip Missing
     * 1169 : Occupation Missing
     * 1171 : Phone Missing
     * 1180 : Success
     * */

    public function verifyUser(){
        $data = $aReturnArray = array();
        $fields = array(
            'first_name' => '1',
            'last_name' => '2',
            'dob' => '3',
            'address' => '4',
            'city' => '5',
            'state' => '6',
            'country' => '7',
            'zip' => '8',
            'occupation' => '9',
            'phone' => '11'
        );
        $this->_setProperties();
        $this->_validateFields($fields, 1160);

        $dataDob = array(
            'first_name' => $this->postData['first_name'],
            'last_name' => $this->postData['last_name'],
            'dob' => $this->postData['dob']
        );

        $this->rest_user_model->updatePin($this->userId, $dataDob);

        $data = array(
            'first_name' => $this->postData['first_name'],
            'last_name' => $this->postData['last_name'],
            'address' => $this->postData['address'],
            'city' => $this->postData['city'],
            'state' => $this->postData['state'],
            'country' => $this->postData['country'],
            'zip' => $this->postData['zip'],
            'occupation'=> $this->postData['occupation'],
            'phone'   => $this->postData['phone']
        );

        $this->rest_user_model->updateDetails($this->userId, $data);

        $aReturnArray = array(
            'photo_upload_url' => $this->_getPhotoIDUploadPath(),
            'utility_bill_url' => $this->_getUtilityBillUploadPath(),
            'bank_letter_upload' => $this->_getBankLetterUploadPath()
            );

        $this->_displaySuccess(array_merge(array('code' => 1180), $aReturnArray));
    }


    public function getUserVerificationDetails()
    {
        $data = array();
        $level = $this->rest_user_model->getUserVerificationLevel($this->user);

        if ($level === 1)
        {
            $data['verification_level'] = '1';
            $data['bank_letter_upload'] = $this->_getBankLetterUploadPath();
        }
        else
        {
            $data['verification_level'] = (string)$level;
            $data['bank_letter_upload'] = $this->_getBankLetterUploadPath();
        }

        $this->_displaySuccess($data);
    }


    public function userInfo() {
        if(!empty($this->userId)) {
            $data['verified'] = $this->user->verified == '1' ? 'YES' : 'NO';
            $data['user_name'] = $this->user->first_name . ' ' . $this->user->last_name;
            $data['pgp_status'] = $this->user->pgp_status;
            $data['pgp_key_upload_status'] = !empty($this->user->pgp_key) ? 1 : 0;
            $level = $this->rest_user_model->getAdminAprovalLevel($this->user);
            $data['verification_level'] = (string)$level;
            $this->_displaySuccess($data);
        }else{
            header('Content-Type: application/json');
            header("HTTP/1.1 500 Internal Server Error");
        }
    }
    /* Function to change users pin
    * @param code integer - has the following values
    *  161. Pin should be digit only
    *  162. Pin should be 4 to 6 digit only
    *  163. New pin and confirm pin are not matching
    *  164. Old pin is correct
    *  170. Success
    * */
    public function changePin() { 
        if (count($this->postData) > 0) {
            $pin = $this->postData['new_pin'];
            $confirmPin = $this->postData['confirm_pin'];
            $oldPin = $this->postData['old_pin'];
            //Check equality
            if ($pin !== $confirmPin) {
                $this->_displayBadRequest(array("code" => 163));
            }
            //Check valid pin
            if (!preg_match("/^[0-9]*$/", $pin) || !preg_match("/^[0-9]*$/", $confirmPin)) {
                $this->_displayBadRequest(array("code" => 161));
            }  
            //Check length
            if (strlen($pin) < 4 || strlen($pin) > 6) {
                $this->_displayBadRequest(array("code" => 162));
            }
//            Check old pin
            if (sha1($oldPin)!== $this->user->pin) {
                $this->_displayBadRequest(array("code" => 164));
            }

            $userData = array(
                'pin' => sha1($pin),
                'twofa_status' => '1',
                'twofa_type'=> 'pin'
            );

            if ($userId = $this->rest_user_model->update($this->userId,$userData))
                $this->event_model->add($userId,'setpin'); // Log pin set event
            else {
                header('Content-Type: application/json');
                header("HTTP/1.1 500 Internal Server Error");
            }
                $this->_displaySuccess(array("code" => 170));
            }
    }
    /**
    Error codes are as follows
    171: PGP Key can not be empty
    172: Invalid PGP key
    173: Flag should not be empty
     * **/
    public function submitPGPKey() {
        if(count($this->postData)>0) {
            $this->load->library('Mandrilllibrary');
            if(empty($this->postData['enable']))
                $this->_displayBadRequest(array("code" => 173));
            if($this->postData['enable']== 'false'){
                $dataUpdate = array();
                $dataUpdate['pgp_key']      = '';
                $dataUpdate['pgp_status']   = '0';
                $this->rest_user_model->updatePin($this->userId, $dataUpdate);
                $this->_displaySuccess(array("code" => 180));
            }else if(!empty($this->postData['pgpkey']) && $this->postData['enable']== 'true') {
                $dataUpdate = array();
                $dataUpdate['pgp_key'] = $this->postData['pgpkey'];
                $dataUpdate['pgp_status'] = '1';
                $status = $this->mandrilllibrary->send('/pgpValidKey', "POST", $dataUpdate);
                if ($status->status) {
                    $this->rest_user_model->updatePin($this->userId, $dataUpdate);
                    $this->_displaySuccess(array("code" => 180));
                } else {
                    $this->_displayBadRequest(array("code" => 172));
                }
            }else{
                $this->_displayBadRequest(array("code" => 171));
            }
        }
    }
    public function getReferralLink() {
        $this->_displaySuccess(array("referralLink"=> SITE_URL . '/?ref=' . $this->referral_model->getReferralCode($this->userId)));
    }

    public function valid_bitcoin_address($address) {
        if (!preg_match ('/^[13][1-9A-HJ-NP-Za-km-z]{20,40}$/', $address)) {
            $this->_displayErrorUnauthorised(array("code" => 182));
        }
        return;
    }
    /* Function to create api
    * @param code integer - has the following values
    *  181. Not allowed more than 3 apis
    *  182. Invalid bitcoin address
    *  183. Secret can not be empty
    *  184. Only alphabets, space and dash are allowed in name
    *  185. Length of name could not exceed lenth of 30
    *  186. Could not able to save data try again
    *  190. Success
    * */
    public function addAPI(){
        $this->load->helper('string');
        $userId = $this->userId;
        $this->load->model('api_model');
        if (count($this->postData) > 0){

            if (empty($this->postData['code']) && $this->api_model->countAPIs('user:'.$userId) > 2) {
                // Not allowed more than 3 APIs
                $this->_displayBadRequest(array("code" => 181));
            }

            if(empty($this->postData['secret']))
                $this->_displayBadRequest(array("code" => 183));

            if(!preg_match('/^[A-Za-z0-9 \-]+$/i', $this->postData['name']))
                $this->_displayBadRequest(array("code" => 184));

            if( strlen($this->postData['name']) > 30)
                $this->_displayBadRequest(array("code" => 185));

            $this->valid_bitcoin_address($this->postData['withdraw']);

            $name     = $this->postData['name'];
            $secret   = $this->postData['secret'];
            $withdraw = $this->postData['withdraw'];

            $data = array(
                'name'               => $name,
                'withdrawal_address' => $withdraw
            );
            if ($secret)
                $data['secret'] = $secret;
            $result = false;
            if(!empty($this->postData['code'])){
                if ($this->api_model->updateApi($userId, $this->postData['code'], $data))
                    $result = true;
            }else {
                if ($this->api_model->addApi($userId, $data))
                    $result = true;
            }
            if ($result) {
                $this->_displaySuccess(array("code" => 190));
            } else {
                $this->_displayErrorInternal(array("code" => 186));
            }
        }
    }

    /* Function to trigger password forgot.
     * The function returns the following error codes
     * 241 : User data incorrect
     * 242 : Captcha is incorrect.
     * 243 : Email does not exist.
     * 250 : Code send with email.
     *
    */
    public function forgotPassword()
    {
        if (empty($this->postData) === false){

                $clientId = $this->postData['id'];
                $data = array(
                    'id'        => $clientId
//                    'dob'       => $this->postData['dob'],
//                    'country'   => $this->postData['country']
                );
                //Check recaptcha
                if($this->valid_recaptcha($this->postData['g-recaptcha-response']) == 1){
                    $this->_displayBadRequest(array("code" => 242));
                }
                //Check email exist or not
                if(filter_var($clientId, FILTER_VALIDATE_EMAIL)) {
                    if ($this->redis->sismember('user:emails', strtolower($clientId)) != 1) {
                        $this->_displayBadRequest(array("code" => 243));
                    }
                }
                if ($data = $this->rest_user_model->resetPassword($data)){
                    $data['timestamp'] = date('c', milliseconds() / 1000);
                    $email = $data['email'];
                    $this->load->library('Mandrilllibrary');
                    $api = $this->mandrilllibrary->getApi();
                    $userData = $this->user_model->findUserByEmail($email);

                    $name = 'forgot_password';
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
                            'name' => 'clientid',
                            'content' => $data['client']
                        ),
                        array(
                            'name' => 'ip',
                            'content' => $data['ip']
                        ),
                        array(
                            'name' => 'link',
                            'content' => $data['link']
                        ),
                        array(
                            'name' => 'timestamp',
                            'content' => $data['timestamp']
                        )
                    );

                    $resultRender = $api->templates->render($name, $templateContent, $mergeVars);

                    $htmlContent = $resultRender['html'];

                    $pgpData = array();
                    $pgpData['content'] = $resultRender['html'];
                    if(isset($userData->pgp_status) && $userData->pgp_status == 1) {
                        $pgpData['key'] = $userData->pgp_key;
                        $pgpData['content'] = strip_tags($resultRender['html']);
                        $encryptMessage = $this->mandrilllibrary->send("/pgpEncrypt","POST",$pgpData);

                        if(isset($encryptMessage->message) && $encryptMessage->message != ''){
                            $htmlContent = $this->mandrilllibrary->formatMessage($encryptMessage->message);
                        }
                    } else {
                     /*   printf("pgpData");
                        var_dump($pgpData);
                       $encryptMessage = $this->mandrilllibrary->send("/pgpSign","POST",$pgpData);
                        var_dump($encryptMessage);
                        $htmlContent = $this->mandrilllibrary->formatSign($encryptMessage->message);
                        var_dump($htmlContent);
             */       }


                    $message = array(
                        'html' => $htmlContent,
                        'subject' => $this->config->item('site_full_name') . ' - ' . _l('password_reset_request'),
                        'from_email' => 'shrikant@alulimtech.com',
                        'from_name' => 'whitebarter Bitcoin Exchange',
                        'to' => array(
                            array(
                                'email' => $email,
                                'name' => 'Recipient Name',
                                'type' => 'to'
                            )
                        ),
                        'headers' => array('Reply-To' => $email),

                    );

                    $result = $api->messages->send($message);

                    $this->_displaySuccess(array("code" => 250) );
                }
                else
                {
                    $this->_displayBadRequest(array("code" => 241));
                }
        }
    }

    /* Function to trigger password forgot.
     * The function returns the following error codes
     * 251 : Code incorrect.
     * 252 : Password and confirm password did not match.
     * 253 : Malformed token.
     * 260 : Password updated successfully.
     *
    */
    public function forgotConfirm()
    {
        try {
            $code = JWT::decode($this->postData['code'], DEFAULT_TOKEN, array(JWT_ALGORITHM));
            $requestData = $this->user_model->checkResetPasswordCode($code);
            if ($requestData === false) {
                $this->_displayErrorUnauthorised(array("code" => 251));
            } else {
                $password = $this->postData['password'];
                $confirm_password = $this->postData['confirm_password'];
                if (trim($password) != trim($confirm_password)) {
                    $this->_displayBadRequest(array("code" => 252));
                } else {
                    $salt = generateRandomString(20);
                    $clientPasswordHash = $this->api_security->hash($requestData->client, trim($password));
                    $passwordHash = $this->api_security->hash($clientPasswordHash, $salt);
                    $userData = array(
                        'salt' => $salt,
                        'password' => $passwordHash
                    );
                    if ($this->user_model->changePasswordAfterReset($code, $userData)) {
                        $this->_displaySuccess(array("code" => 260));
                    }
                }
            }
        }
        catch(DomainException $e){
            if($this->getProtectedMember($e,'message' ) == 'Syntax error, malformed JSON'){
                $this->_displayBadRequest(array("code" => 253));
            }
        }
    }
    /* Function to create api
    * @param code integer - has the following values
    *  261. Pin should be digits only
    *  262. Pin length should be 4 to 6 digits only
    *  263. Pin already set
    *  264. User unauthorised
    *  270. Success
    * */
    public function setPin() {
        if (count($this->postData) > 0) {
            if(empty($this->postData['session_id']))
                $this->_displayErrorUnauthorised(array("code" => 264) );
            $sUserID = $this->postData['user_id'];
            $this->user = $this->rest_user_model->get($sUserID);
            $pin = $this->postData['code'];
            //Check valid pin
            if (!preg_match("/^[0-9]*$/", $pin)) {
                $this->_displayBadRequest(array("code" => 261));
            }
            //Check length
            if (strlen($pin) < 4 || strlen($pin) > 6) {
                $this->_displayBadRequest(array("code" => 162));
            }
            //Check length
            if (!empty($this->user->pin)) {
                $this->_displayBadRequest(array("code" => 263));
            }
            $userData = array(
                'pin' => sha1($pin),
                'twofa_status' => '0',
                'twofa_type'=> 'pin'
            );
            $sSessionID = $this->postData['session_id'];
            $aSessionData = $this->rest_user_model->getUserSessionData($sUserID);
            if (trim($sSessionID) == trim($aSessionData['body']['_id'])){
                if($this->rest_user_model->updatePin($sUserID,$userData))
                {
                    $this->_display(array("code" => 270, "_token" => $aSessionData['body']['_token']) );
                }
                else
                {
                    header('Content-Type: application/json');
                    header("HTTP/1.1 500 Internal Server Error");
                }
            }else {
                $this->_displayErrorUnauthorised(array("code" => 264) );
            }
        } 
    }


    public function getLimits(){
        foreach ($this->meta_model->getBooks() as $key => $book){
            $limits['limits'][$key] = $this->meta_model->getLimits($key);
        }
        return $limits;
    }

    public function getRecaptchaSiteKey(){
        $this->_displaySuccess(array("sitekey" => $this->config->item('recaptcha_site_key')));
    }
    public function getUploadedDocuments(){
        $this->load->model('user_document_model');
        $documents = $this->user_document_model->getForUser($this->userId);
        $data = array();
        foreach ($documents as $key => $document){
            $data[$key]['filename'] = $document->filename;
            $data[$key]['updated'] = $document->uploaded;
            $data[$key]['status'] = $document->status;
        }
        $this->_displaySuccess($data);
    }
    public function getUserInteracStatus(){
        $this->_displaySuccess(array("status" => $this->deposit_model->userBannedFromInterac($this->userId)));
    }
    public function getUserApis(){
        $this->load->model('api_model');
        $userApis = $this->api_model->getAPIs($this->userId);
        $data = array();
        foreach ($userApis as $key => $api) {
            $data[$key]['name']   = $api->name;
            $data[$key]['code']   = $key;
            $data[$key]['secret'] = $api->secret;
            $data[$key]['withdrawal_address'] = $api->withdrawal_address;
        }
        $this->_displaySuccess($data);
    }
    /* Function to create api
   * @param code integer - has the following values
   *  291. Code can not be empty
   *  292. Api does not exist
   *  300. Success
   * */
    public function removeApi() {
        $userId = $this->userId;
        $this->load->model('api_model');
        if(empty($this->postData['code']))
            $this->_displayBadRequest(array("code" => 291));
        if ($this->api_model->deleteApi($userId, $this->postData['code']))
            $this->_displaySuccess(array("code" => 300));
        else
            $this->_displayBadRequest(array("code" => 292));
    }

    public function getL1Details() {
        if(!empty($this->userId)) {
            $data = $this->rest_user_model->getDetails($this->userId);
            $dataArray = array(
                'firstName' => $data->first_name,
                'lastName' => $data->last_name,
                'address' => $data->address,
                'city' => $data->city,
                'state' => $data->state,
                'country' => $data->country,
                'zip' => $data->zip,
                'occupation'=> $data->occupation,
                'phone'   => $data->phone,
                'birthDate'   => $this->user->dob
            );
            $this->_displaySuccess($dataArray);
        }else{
            header('Content-Type: application/json');
            header("HTTP/1.1 500 Internal Server Error");
        }
    }

}