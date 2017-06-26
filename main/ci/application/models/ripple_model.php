<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once('redis_model.php');

class Ripple_model extends Redis_model {

    var $server_url;
    var $server_url_trans;
    var $_config;

    public function __construct() {
        parent::__construct();

        $this->_config = $this->config->item('creds_ripple');

        $this->server_url       = "https://" . $this->_config['server'] . "/v1";
        $this->server_url_trans = "https://" . $this->_config['server'] . "/v1/payments";
    }

    public function setLastLedgerChecked($ledger_id) {
        $this->redis->hset('ripple:ledger', 'lastidcheck', $ledger_id);
    }

    public function getLastLedgerChecked() {
        return $this->redis->hget('ripple:ledger', 'lastidcheck');
    }

    // Might need a lookup instead of going through all users
    public function getUserForAddress($addr) {
        $users = $this->redis->sort('user:ids', array('BY' => 'nosort'));

        foreach ($users as $userId) {
            $user = $this->flatten($this->redis->hgetall($userId));

            if (isset($user->ripple_address) && $user->ripple_address == $addr)
                return $user;
        }

        return null;
    }

    public function balances($address) {

        $url = $this->server_url;
        $url .= "/accounts/" . $address . "/balances";
        $json = $this->curlSend($url, "GET");
        return json_decode($json);
    }

    public function withdraw($withdrawal_id, $send_address, $currency, $amount, &$err) {
        // Get payment options
        $url = $this->server_url;
        $url .= "/accounts/" . $this->_config['hot_wallet_address'] . "/payments/paths/" . $send_address . "/" . $amount . "+" . strtoupper($currency) . "+" . $send_address;
        error_log("Sending to :" . $url);

        $json = $this->curlSend($url, "GET");

        $get_transaction = json_decode($json);
        if (!$get_transaction || !$get_transaction->success) {
            if (isset($get_transaction)) {
                $err = $get_transaction->message;
            }
            else {
                $err = "Could not connect";
            }
            error_log("There was a problem - " . $err);

            return false;
        }

        // Now send off payment using one of them:
        $paymentOptions = $get_transaction->payments;

        foreach ($paymentOptions as $option) {
            if ($option->source_amount->currency == strtoupper($currency)) {
                $payment_object = $option;
                break;
            }
        }

        if (empty($payment_object)) {
            $err = "Could not find payment route.";

            return false;
        }

        //error_log("Going to send: ".serialize($payment_object));
        $payload = array(
            "secret"             => $this->_config['hot_wallet_secret'],
            "client_resource_id" => "" . $withdrawal_id,
            "payment"            => $payment_object
        );
        //error_log("Payload is ".serialize($payload));
        // SIGN/SEND

        $result = $this->curlSend($this->server_url_trans, "POST", $payload, true);
        if ($result) {
            $result = json_decode($result);
            if ($result->success) {
                error_log("Sent (" . $withdrawal_id . ")");

                return true;
            }
            else {
                $err = $result->message;

                return false;
            }
        }
    }

    function curlSend($url, $mode, $post = null, $jsonmode = false) {
        $timeout = 100;

        // Maybe use the curl library here

        $ch = curl_init($url);

        if ($mode == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        if ($jsonmode) {
            // Send Json
            $data_string = json_encode($post);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string)
                ));
        }
        else {
            // Send normal posted fields
            if (!is_null($post)) curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $mode);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, 5990);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); # required for https urls
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        $result = curl_exec($ch);
        curl_close($ch); // Seems like good practice
        return $result;
    }
}