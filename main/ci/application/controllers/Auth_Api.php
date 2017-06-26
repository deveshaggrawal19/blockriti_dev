<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auth_api extends CI_Controller {

    protected $postData = array();

    protected $user = array();
    protected $userId;
    protected $merchantCode;
    protected $sessionData = array();
    protected $ip;
    protected $language;

    protected $properties;
    protected $auth;
    protected $authheader;

    public function __construct() {
        parent::__construct();
        // Make sure we receive the format we expect
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hack = explode(';', $_SERVER['CONTENT_TYPE']);
            $_ct  = array_shift($hack);
            if ($_ct !== 'application/json' && $_ct !== 'application/x-www-form-urlencoded') {
                header('HTTP/1.1 415 Unsupported Media Type');
                exit;
            }
            if ($_ct == 'application/json')
            {
                $this->postData = json_decode(file_get_contents("php://input"), true);
            }
            else $this->postData = $_POST;
        }

        $this->_setCORSHeaders();

        //load header from the incoming request.
        $this->_loadHeaders();

        $this->load->library('redis');
        $this->load->library('firebase_lib');

        $this->load->model('redis_model');
        $this->load->model('caching_model');
        $this->load->model('meta_model');
        $this->load->model('firebase_auth_model');

        $this->load->model('api_model');
        $this->load->model('trade_model');
        $this->load->model('order_model'); 
        $this->load->model('permissions_model');
        $this->load->model('user_model');
        $this->load->model('user_balance_model');
        $this->load->model('deposit_model');
        $this->load->model('withdrawal_model');
        $this->load->model('notification_model');

        $this->meta_model->getBooks();
        $this->meta_model->getAllCurrencies();

        $this->properties = (object)array();

        $this->ip     = getIp();  
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
            $this->_displaySuccess(array('code'=>10));
        }
        $methodsArray = array('login','authenticate','register','changePin','coupon','setPin', 'forgotConfirm','cheque','bankwire','bitcoin','changePassword');
        if((in_array($this->router->fetch_method(),$methodsArray)) && $_SERVER['REQUEST_METHOD'] != 'OPTIONS')
        { 
            if($this->_isMerchantValid()){
                $dataArray = json_decode($this->_decryptPayload(), true);
                if(empty($dataArray) === true){
                    $dataArray = array();
                }
                $this->postData = array_merge($this->postData, $dataArray);
            }
            else
            {
                $this->_displayBadRequest(array('message' => 'Merchant Not Found'));
            }
        }
        $methodsNotToVerifyArray = array('login','authenticate','setPin','verifyEmail','register','getUploadPath','uploadHandler','listFiles','marketOverview', 'forgotConfirm', 'forgotPassword');
        if(!in_array($this->router->fetch_method(),$methodsNotToVerifyArray) && $_SERVER['REQUEST_METHOD'] != 'OPTIONS') {
            $this->auth = $this->firebase_auth_model->verifyToken($this->authheader, $this->userId);
            if(empty($this->auth) === false)
            {
                switch ($this->auth['code'])
                {
                    case 10:
                        $this->sessionData = (object)$this->auth['body'];
                        $this->userId = $this->sessionData->client;
                        break;
                    case 1:
                    case 2:
                        header('Content-Type: application/json');
                        header("HTTP/1.1 400 Bad Request");
                        $this->_display(array("code" => $this->auth['code'] + 20));
                        break;
                    case 3:
                    case 4:
                        header('Content-Type: application/json');
                        header("HTTP/1.1 401 Unauthorized");
                        $this->_display(array("code" => $this->auth['code'] + 20, "body" => $this->auth['body']));
                        break;
                    case 5:
                    default:
                        header('Content-Type: application/json');
                        header("HTTP/1.1 500 Internal Server Error");
                        break;
                }
            }
        }
    }
    
    private function _loadHeaders(){
        $this->authheader = (isset($_SERVER['HTTP_AUTH_TOKEN']) === true && empty($_SERVER['HTTP_AUTH_TOKEN']) === false)?
            $_SERVER['HTTP_AUTH_TOKEN'] : null;
        $this->userId = (isset($_SERVER['HTTP_AUTH_USER']) === true && empty($_SERVER['HTTP_AUTH_USER']) === false)?
            $_SERVER['HTTP_AUTH_USER'] : null;
        $this->merchantCode = (isset($_SERVER['HTTP_MERCHANT_CODE']) === true && empty($_SERVER['HTTP_MERCHANT_CODE']) === false)?
            $_SERVER['HTTP_MERCHANT_CODE'] : null;
    }

    private function _setCORSHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, AUTH_USER, AUTH_TOKEN, MERCHANT_CODE");
        header('Access-Control-Allow-Methods "PUT, GET, POST, DELETE, OPTIONS"');
    }

    private function _isMerchantValid(){
        $this->load->config('merchant_list');
        $aMerchants = $this->config->item('merchants');
        return (in_array($this->merchantCode, $aMerchants));
    }

    private function _decryptPayload(){
        $sPrivateKey = $this->firebase_lib->getPrivateKey($this->merchantCode);

        $bSuccess = openssl_private_decrypt(base64_decode($this->postData['data']) , $aDecrypt, base64_decode($sPrivateKey['body']));
        if($bSuccess === true)
        {
            return $aDecrypt;
        }
        else
        {
            $this->_displayBadRequest(array('message' => 'Unable to decrypt the request'));
        }
    }

    protected function _display($data) {
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($data));
        $this->output->_display();

        exit();
    }

    protected function _displayErrorUnauthorised($code) {
        header('Content-Type: application/json');
        header("HTTP/1.1 401 Unauthorized");
        $this->_display($code);
    }

    protected function _displayErrorInternal($code) {
        header('Content-Type: application/json');
        header("HTTP/1.1 500 Internal Server Error");
        $this->_display($code);
    }

    protected function _displaySuccess($code) {
        header('Content-Type: application/json');
        header("HTTP/1.1 200 OK");
        $this->_display($code);
    }

    protected function _displayBadRequest($code) {
        header('Content-Type: application/json');
        header("HTTP/1.1 400 Bad Request");
        $this->_display($code);
    }


}