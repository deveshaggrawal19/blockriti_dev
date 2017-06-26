<?php
//todo Shouldnt need this anymore
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set("UTC");
define("SERVER_NAME", "https://api.coinkite.com");

class Coinkitelibrary {
    private $apiKey = "K5e9c5d34-af2f30a9-e3694fbe535e210c";
    private $apiSecret = "Sd48cb983-2e939047-16d5654194b46d8f";
    private $apiCurrency;

    
    public function __construct() {
        
        $CI = &get_instance();
        $CI->load->config('creds_coinkitelibrary', TRUE);
        
        $config = $CI->config->item('creds_coinkitelibrary');
        
        $this->apiKey = $config['apikey'];
        $this->apiSecret = $config['apisecret'];
        $this->apiCurrency = $config['currency'];
        
    }

    public function sign($endpoint, $forse_ts = false) {
        if($forse_ts) {
            $ts = $forse_ts;
        } else {
            $now = new DateTime();
            $ts = $now->format(DateTime::ISO8601);
        }
        
        $data = $endpoint.'|'.$ts;
        $hm = hash_hmac('sha256', $data, $this->apiSecret);
        
        return array($hm, $ts);
    }
    
    public function send($method, $type = "GET", $data = null) {
        $XCKSign = $this->sign($method);
        $ch = curl_init();
        if($type == "GET" && $data != null) {
            $str = "?";
            foreach($data as $key=>$value) {
                if(strlen($str) > 1) {
                    $str = $str."&"; 
                }
                $str = $str.$key."=".$value;
            }
            curl_setopt($ch, CURLOPT_URL, $this->getUrl($method).$str);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->getUrl($method));
        }
        
        if($type == "PUT"){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        //curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                             'Content-Type: application/json; charset=UTF-8',
                                             'X-CK-Key: '.$this->apiKey,
                                             'X-CK-Sign: '.$XCKSign[0],
                                             'X-CK-Timestamp: '.$XCKSign[1]
                                             //'Content-Length: '.strlen('')
                                             ));
        $result = curl_exec($ch);

        if(curl_errno($ch)){
            echo 'Request error - '.curl_error($ch);
        }

        $json = json_decode($result);
        curl_close($ch);
        return $json;
    }
    

    
    public function getUrl($method) {
        return SERVER_NAME.$method;
    }
    
    public function getCurrency() {
        return $this->apiCurrency;
    }
    
    public function getP2SHAccount() {
        $accounts = $this->send("/v1/my/all_accounts");
        foreach($accounts->results as $account) {
            if($account->CK_acct_type == "p2sh" && $account->coin_type = $this->apiCurrency){
                return $account;
            }
        }
        return false;
    }

}
?>
