<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Auth_Api.php');

class Fund extends Auth_api
{

    protected $postData;


    public function __construct()
    {
        parent::__construct();
        $this->load->model('deposit_model');
        $this->load->model('bitcoin_model');
        $this->load->model('rest_user_model');
        $this->_obj_Memcache  = new Memcache();
        //Load user from session.
        if(empty($this->sessionData) === false) {
            $this->user = $this->rest_user_model->loadFromSession($this->sessionData);
        }
    }
    /*  
     * @param $error integer - has the following values
     * 80 : Not valid page parameter
     * */
    public function depositSummary() {
        $page = $this->postData['page'];
        if(!is_int($page)){
            $this->_displayBadRequest(array("code"=>80));
        }
        $this->deposit_model->getCountForUser($this->userId, 'all');
        if (!$page)
            $page = 1;
        $data['records'] = $this->deposit_model->getSubsetForUser($page, 20);
        $this->_display($data);
    }

    public function depositBtc() {
        $address = $this->bitcoin_model->getFromBitcoind($this->userId);
        if(empty($address) === false)
        {
            $data = array(
                'address' => $address,
                'qrUrl' => 'https://chart.googleapis.com/chart?chs=200x200&chld=M|1&cht=qr&chl=' . urlencode('bitcoin:' . $address)
            );
            $this->_obj_Memcache->delete('user:' . $this->userId . ':deposits');
            $this->_obj_Memcache->delete('user:' . $this->userId . ':deposits:btc');
            $this->_display($data);
        }
        else
        {
            $this->_displayBadRequest(array('code' => 91));
        }
    }

    /*
     * Function to deposit coupons
     * Returns the following error codes
     * 211 : The User is not verified.
     * 212 : The given coupon does not exist
     * 213 : The coupon is already used
     * 214 : Incorrect use of the coupon
     * 220 : Coupon successfully deposited
     *
     * */

    public function depositCoupon(){
        $code = $this->postData['code'];
        $this->load->model('voucher_model');
        if ($this->user->verified == 0)
        {
            $this->_displayErrorUnauthorised(array('code' => 211) );
        }
        if(!$this->voucher_model->existCoupon($code))
        {
            $this->_displayBadRequest(array('code' => 212) );
        }
        if(!$this->voucher_model->unusedCoupon($code))
        {
            $this->_displayBadRequest(array('code' => 213) );
        }

        $voucher = $this->voucher_model->get($code);
        $data = array(
            'client'   => $this->userId,
            'method'   => 'cou',
            'currency' => 'cad',
            'amount'   => abs($voucher->value)
        );

        $details = null;

        if ($depositId = $this->deposit_model->addComplete($data, $details) )
        {
            $voucherArray = (array)$voucher;
            $voucherArray['client'] = $this->userId;
            $this->voucher_model->update($code, $voucherArray);
            $this->_obj_Memcache->delete('user:' . $this->userId . ':deposits');
            $this->_obj_Memcache->delete('user:' . $this->userId . ':deposits:btc');
            $this->notification_model->pushUserNotification($this->userId);
            $this->_displaySuccess(array('code' => 220) );
        }
        else
        {
            $this->_displayBadRequest(array('code' => 214) );
        }

    }

    /*
     * Function to deposit coupons
     * Returns the following error codes
     * 221 : The User is not verified.
     * 230 : User allowed for BankWire Deposit
     *
     * */
    public function depositBankWire()
    {
        if (intval($this->user->verified) == 0) {
            $this->_displayErrorUnauthorised(array('code' => 221) );
        }
        else
        {
            $aDetails = array(
                'domestic' => array(
                    "beneficiary" => "Taurus Crypto Services Inc.",
                    "beneficiary_address" => "503-318 Homer Street, Vancouver BC, V6B 1E8, Canada",
                    "bank" => "CCEC Credit Union",
                    "bank_address" => "2248 Commercial Drive, Vancouver, BC, Canada, V5N 4B5",
                    "bank_swift" => "CUCXCATTVAN",
                    "route_transit_number" => "080943710",
                    "account_number" => "100120535",
                    "details" => "Client ID ". $this->userId,
                ),
                'international' => array(
                    "beneficiary" => "Taurus Crypto Services Inc.",
                    "beneficiary_address" => "503-318 Homer Street, Vancouver BC, V6B 1E8, Canada",
                    "bank" => "Central 1 Credit Union",
                    "bank_address" => "1441 Creekside Drive, Vancouver, BC, Canada, V6J 4S7",
                    "bank_swift" => "CUCXCATTVAN",
                    "bank_info" => "CCEC Credit Union, 2248 Commercial Drive, Vancouver, BC, V5N 4B5",
                    "account_number" => "080943710120535",
                    "details" => "Client ID ". $this->userId,
                )
            );
            $this->_displaySuccess(array_merge($aDetails,array('code' => 230) ) );
        }
    }

    /*
     * Function to deposit coupons
     * Returns the following error codes
     * 231 : The User is not verified.
     * 232 : User banned from using INTERAC
     * 233 : Invalid Currency/ Currency not allowed
     * 234 : Amount below allowed limit
     * 235 : Amount exceeds allowed limit
     * 236 : Unable to fund as request empty
     * 240 : Deposit Successful
     *
     * */
    public function depositInterac()
    {
        $currency = strtolower($this->postData['currency']);
        $amount = $this->postData['amount'];
        if (intval($this->user->verified) == 0) {
            $this->_displayErrorUnauthorised(array('code' => 231) );
        }
        $interacBan = $this->deposit_model->userBannedFromInterac($this->userId);
        if (intval($interacBan) != 0) {
            $this->_displayErrorUnauthorised(array('code' => 232) );
        }

        // Count how many interac deposits were made by this user:
        $max = 2000;
        if($this->user->interac_limit && $this->user->interac_limit != ''){
            $max = $this->user->interac_limit;
        } else {
            $count = $this->deposit_model->getCountForUser($this->userId, 'complete', 'io');
            if ($count < 3)
                $max = 250;
            else if ($count < 6)
                $max = 500;
        }

        $allowed = array(
            'cad' => array('min' => 50, 'max' => $max)
        );
        $userDetails = $this->user_model->getDetails($this->userId);
        if (empty($this->postData) === false && $userDetails) {
            if (!isset($allowed[$currency])) {
                $this->_displayBadRequest(array("code" => 233));
            }
            else if($amount < $allowed[$currency]['min'])
            {
                $this->_displayBadRequest(array("code" => 234));
            }
            else if($amount > $allowed[$currency]['max'])
            {
                $this->_displayBadRequest(array("code" => 235));
            }
            else
            {
                $data = array(
                    'client'   => $this->userId,
                    'method'   => 'io',
                    'currency' => $currency,
                    'amount'   => abs($amount)
                );
                $deposit = $this->deposit_model->add($data);
                if ($deposit) {
                    $this->load->config('creds_admeris', TRUE);
                    $this->load->config('creds_psigate', TRUE);
                    $userClient = $this->user_model->getUser($deposit->client);
                    if(!isset($userClient->automatic_interact) || $userClient->automatic_interact != 1){
                        $this->deposit_model->toVerify(_numeric($deposit->_id), $this->userId);
                    }

                    $data['user']        = $this->user;
                    $data['userDetails'] = $userDetails;
                    $data['depositId']   = $this->deposit_model->_id;
                    $data['config']      = $this->config->item('creds_psigate');
                    $this->_obj_Memcache->delete('user:' . $this->userId . ':deposits');
                    $this->_obj_Memcache->delete('user:' . $this->userId . ':deposits:btc');
                    $this->notification_model->pushUserNotification($this->userId);
                    $this->_displaySuccess(array('code' => 240) );
                }

            }

        }
        else
        {
            $this->_displayBadRequest(array('code' => 236) );
        }
    }
}