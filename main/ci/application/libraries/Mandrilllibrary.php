<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("UTC");

require('Mandrill/Mandrill.php');
define("MD_SERVER_NAME", "https://104.198.41.74:8000/nodeServer");

class Mandrilllibrary {
    private $key;
    private $mandrill;
    
    public function __construct() {
        $CI = &get_instance();
        $CI->load->config('creds_mandrill', TRUE);
        $config = $CI->config->item('creds_mandrill');
        
        $this->key = $config['key'];
        $this->mandrill = new Mandrill($this->key);
    }
    
    public function getApi() {
        return $this->mandrill;
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
        }*/
        $json = json_decode($result);
        
        curl_close($curl);
        return $json;
    }
    
    public function getUrl($method) {
        return MD_SERVER_NAME.$method;
    }
    
    public function formatMessage($message) {
        $splitArray = explode(".org", $message);
        $formatString = '-----BEGIN PGP MESSAGE----- <br />
                        Version: OpenPGP.js VERSION <br />
                        Comment: http://openpgpjs.org <br /><br />';
        $lastPosition = strripos($splitArray[1], "=");
        for($i = 0; $i <= $lastPosition;  $i = $i + 60){
            $lenString = 60;
            if(($lastPosition - $i) < $lenString) {
                $lenString = $lastPosition - $i;
            }
            $formatString .= substr($splitArray[1], $i, $lenString)."<br />";
        }
        $formatString .= substr($splitArray[1], $lastPosition, 5)."<br />";
        $formatString .= "-----END PGP MESSAGE-----";
        return $formatString;
    }
    
    public function formatSign($message) {
        //SHA256
        $message = str_replace("\n", "<br />", $message);
        return str_replace("<br /><br />", "<br />", $message);

        /*
        $splitArray = explode(".org", $message);
        $formatString = '-----BEGIN PGP MESSAGE----- <br />
                        Version: OpenPGP.js VERSION <br />
                        Comment: http://openpgpjs.org <br /><br />';
        $lastPosition = strripos($splitArray[1], "=");
        for($i = 0; $i <= $lastPosition;  $i = $i + 60){
            $lenString = 60;
            if(($lastPosition - $i) < $lenString) {
                $lenString = $lastPosition - $i;
            }
            $formatString .= substr($splitArray[1], $i, $lenString)."<br />";
        }
        $formatString .= substr($splitArray[1], $lastPosition, 5)."<br />";
        $formatString .= "-----END PGP MESSAGE-----";
        return $formatString;
        */
    }
    
    public function replaceParams($contents, $vars) {
        $content = $contents['content'];
        foreach($vars as $value) {
            $content = str_replace("*|".strtoupper($value['name'])."|*", $value['content'], $content);
        }
        
        return array("html" => $content);
    }
}
?>