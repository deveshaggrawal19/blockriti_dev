<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Keystore extends CI_Controller {

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
        }
        $this->_setCORSHeaders();
    }

    private function _setCORSHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, AUTH_USER, AUTH_TOKEN");
        header('Access-Control-Allow-Methods "PUT, GET, POST, DELETE, OPTIONS"');
    }

    public function fetchKey($sMerchantCode)
    {
        $this->load->library('firebase_lib');
        $pub = $this->firebase_lib->getPublicKey($sMerchantCode);
        $this->_display(array('key' => $pub['body']));
    }

    protected function _display($data) {
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($data));
        $this->output->_display();

        exit();
    }

    public function testEncode($sMerchantCode)
    {
//        $data = array("old_pin" => 1111, "new_pin" => 2222, "confirm_pin" => 2222);
        $data = array("currentPassword" => "testing", "newPassword" => "testing", "retypePassword" => "testing");
        $this->load->library('firebase_lib');
        $pub = $this->firebase_lib->getPublicKey($sMerchantCode);
        $bSuccess = openssl_public_encrypt(json_encode($data), $encrypted, base64_decode($pub['body']));
        echo base64_encode($encrypted);exit();
    }
    public function testRegEncode($sMerchantCode)
    {
        $data = array("password" => "testing", "confirm_password" => "testing");
        $this->load->library('firebase_lib');
        $pub = $this->firebase_lib->getPublicKey($sMerchantCode);
        $bSuccess = openssl_public_encrypt(json_encode($data), $encrypted, base64_decode($pub['body']));
        echo base64_encode($encrypted);exit();
    }

    public function testLoginEncode($sMerchantCode)
    {
        $postData = json_decode(file_get_contents("php://input"), true);
        $data = array("id" => $postData['id'], "password" => $postData['password']);
        $this->load->library('firebase_lib');
        $pub = $this->firebase_lib->getPublicKey($sMerchantCode);
        $bSuccess = openssl_public_encrypt(json_encode($data), $encrypted, base64_decode($pub['body']));
        echo base64_encode($encrypted);exit();
    }

    public function testAuthEncode($sMerchantCode)
    {
        $postData = json_decode(file_get_contents("php://input"), true);
        $data = array("session_id" => $postData['session_id'], "code" => $postData['code'], "user_id" => $postData['user_id'], "type" => $postData['type']);
        $this->load->library('firebase_lib');
        $pub = $this->firebase_lib->getPublicKey($sMerchantCode);
        $bSuccess = openssl_public_encrypt(json_encode($data), $encrypted, base64_decode($pub['body']));
        echo base64_encode($encrypted);exit();
    }

}