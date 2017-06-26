<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("UTC");
define("BG_SERVER_NAME", "http://104.198.41.74:8000");

class BitGo {
    private $apiKey;
    private $apiSecret;
    private $apiCurrency;
    //todo check this bitgo in regards to alex not sure why his email iss attached
    private $email = "alexey0912@rambler.ru";
    private $pass = "Lexus_09121991";
    private $otp = "0000000";

    public function __construct() {
        $CI = &get_instance();
    }

    public function send($method, $type = "GET", $data = null) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->getUrl($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if($type != "GET") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        }
        if($data != null){
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $result = curl_exec($curl);
        //print_r($result);

        /*if(curl_errno($curl)){
            echo 'Request error - '.curl_error($curl);
            return json_encode(array("Error" => curl_error($curl)));
        }*/
        $json = json_decode($result);

        //print_r($json);

        curl_close($curl);
        return $json;
    }

    public function getUrl($method) {
        return BG_SERVER_NAME.$method;
    }
}

?>