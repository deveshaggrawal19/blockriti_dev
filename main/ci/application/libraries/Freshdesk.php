<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Freshdesk {
    private $serverName = "https://taumax.freshdesk.com";
    private $username = "ErYIp1uubLQrZsvLXhdJ";
    private $password = "X";
    private $email = "aslife0912@gmail.com";
    
    public function __construct() {
        
        $CI = &get_instance();
        $CI->load->config('creds_freshdesk', TRUE);
        
        $config = $CI->config->item('creds_freshdesk');
        
        $this->serverName = $config['server_name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->email = $config['email'];
        
    }
    
    public function send($method, $type = "GET", $data = null, $withFile = false) {
        $curl = curl_init();
        
        if($withFile){
            $header[] = "Content-type: multipart/form-data";
        } else {
            $header[] = "Content-type: application/json";
        }
        
        //echo $this->getUrl($method);
        curl_setopt($curl, CURLOPT_URL, $this->getUrl($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        //curl_setopt($curl, CURLOPT_HEADER, false);
        
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        
        curl_setopt($curl, CURLOPT_USERPWD, $this->username.":".$this->password);
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        
        if($type != "GET" && $type != "POST") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        } else if($type != "GET" && $type == "POST") {
            curl_setopt($curl, CURLOPT_POST, 1);
        }
        
        if($data != null){
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $result = curl_exec($curl);
        //print_r($result);
        
        if(curl_errno($curl)){
            echo 'Request error - '.curl_error($curl);
        }
        
        $json = json_decode($result);
        
        curl_close($curl);
        return $json;
    }
    
    public function getUrl($method) {
        return $this->serverName.$method;
    }
    
    public function getEmail() {
        return $this->email;
    }
}


?>