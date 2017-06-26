<?php

require('Protectimus/bootstrap.php');
use Exception\ProtectimusApiException;

class Protectimus {
    private $username;
    private $apiKey;
    private $apiUrl;
    private $resourseId;
    
    private $api;
    
    public function __construct() {
        $CI = &get_instance();
        $CI->load->config('creds_protectimus', TRUE);
        $config = $CI->config->item('creds_protectimus');
        
        $this->username = $config['username'];
        $this->apiKey = $config['apikey'];
        $this->apiUrl = $config['apiurl'];
        $this->resourseId = $config['resourceid']; 
        
        $this->api = new ProtectimusApi($this->username, $this->apiKey, $this->apiUrl);
    }
    
    public function getApi() {
        return $this->api;
    }
    
    public function getResourceId() {
        return $this->resourseId;
    }
    
    public function getApiKey() {
        return $this->apiKey;
    }

}
?>