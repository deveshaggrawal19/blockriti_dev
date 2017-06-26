<?php defined('BASEPATH') OR exit('No direct script access allowed');

define("SERVER_NAME", "https://104.198.41.74:8080");

use Firebase\Token\TokenException;
use Firebase\JWT\JWT;

class Firebase_lib
{

    private $_fbObject;
    private $_fbTokenObject;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('firebase_token');
        $this->CI->load->library('curl');
        $this->_fbObject = new \Firebase\FirebaseLib(DEFAULT_URL, DEFAULT_TOKEN);
        $this->_fbTokenObject = Firebase_token::getFirebaseTokenObject(DEFAULT_TOKEN);

    }

    public function getAuthToken($sUserID)
    {
        try {
            $token = $this->_fbTokenObject->setData(array('uid' => 'user:' . $sUserID))->create();
        } catch (TokenException $e) {
            echo "Error: " . $e->getMessage();
        }
        return $token;
    }

    public function setData($iUserID, $data){
        if (empty($iUserID) === false) {
            $this->_fbObject->set('users/user:' . $iUserID, $data);
        }
    }

    public function getData($url){
        if (empty($url) === false) {
            return $this->_fbObject->get($url);
        }
        return false;
    }

    public function verifyAuthToken($sAuthToken, $sUserID)
    {
        $aResponse = array();
        try {
            $obj_Data = JWT::decode($sAuthToken, DEFAULT_TOKEN, array("HS256"));
            if(isset($obj_Data) === true && empty($obj_Data) === false ){
                $obj_Data = json_decode(json_encode($obj_Data), true);
                if(isset($obj_Data['d']['uid']) === true)
                {
                    $aUserID = explode(":", $obj_Data['d']['uid']);
                    if(intval($aUserID[1]) === intval($sUserID))
                    {
                        $aResponse = $this->getData('users/user:'.$sUserID);
                    }
                    else
                    {
                        $aResponse['status'] = 401;
                        $aResponse['body']['error'] = "Invalid token";
                    }
                }
            }
            else
            {
                $aResponse['status'] = 500;
                $aResponse['body']['error'] = "Internal Server Error";
            }
        }
        catch (DomainException $e)
        {
            $aResponse['status'] = 400;
            $aResponse['body']['error'] = $e->getMessage();
        }
        catch (Firebase\JWT\ExpiredException $e)
        {
            $aResponse['status'] = 401;
            $aResponse['body']['error'] = $e->getMessage();
        }
        return $aResponse;
    }

    public function clearUserSession($iUserID)
    {
        if (empty($iUserID) === false) {

            return $this->_fbObject->delete('users/user:' . $iUserID);
        }
    }

    public function getPublicKey($sMerchantCode)
    {
        //var_dump($sMerchantCode);
        $url = 'keystore/'.$sMerchantCode.'/public';
        //var_dump($url);
        return $this->getData($url);
    }

    public function getPrivateKey($sMerchantCode)
    {

        $url = 'keystore/'.$sMerchantCode.'/private';
        return $this->getData($url);
    }

    public function getUserSessionObject($iUserID)
    {
        $url = 'users/user:'.$iUserID;
        return $this->getData($url);
    }

    public function getAccessToken($iUserID)
    {
        $token = $this->send('/getToken', "POST", array('uid' => 'user:'.$iUserID) );
        if(empty($token->data) === false){
            return $token->data->authToken;
        }
        return "";
    }

    public function send($method, $type = "GET", $data = null) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->getUrl($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json'
        ));

        if($type != "GET") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        }
        if($data != null){
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($curl);
        $json = json_decode($result);

        curl_close($curl);
        return $json;
    }

    public function getUrl($method) {
        return SERVER_NAME.$method;
    }
}
