<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\Token\TokenException;

class Firebase_pub
{

    private $_fbObject;
    private $_fbTokenObject;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('firebase_token');
        $this->CI->load->library('curl');
        $this->_fbObject = new \Firebase\FirebaseLib(FB_PUB_URL, FB_PUB_TOKEN);
        $this->_fbTokenObject = Firebase_token::getFirebaseTokenObject(FB_PUB_TOKEN);

    }

    public function setData($sMethod, $data){
        if (empty($sMethod) === false) {
            $this->_fbObject->set($sMethod, $data);
        }
    }

    public function getData($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $aResponse['body'] = json_decode(curl_exec($ch), true);
        $aResponse['status'] = (integer)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $aResponse;
    }

    private function _generateFireBaseTokenForServiceAccount($sServiceWorker)
    {
        return $this->_fbTokenObject->setData(array('uid' => $sServiceWorker))->create();
    }

    public function getCurrentBuy()
    {
        $url = FB_PUB_URL.'/getCurrentBuy.json?auth='.trim($this->_generateFireBaseTokenForServiceAccount('my-service-worker'));
        return $this->getData($url);
    }
}
