<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Api.php');

class Private_api extends Api {

    private $api;
    private $hash;

    private $api_name;

    public function __construct() {
        parent::__construct();

        $this->checkApiEnabled('apiprivate');

        if (!$this->postData)
            $this->_error(105, 'Invalid or missing payload');

        foreach ($this->postData as $key => $value) {
            if (empty($this->auth)) $this->auth = new stdClass();
            if (in_array($key, array('key', 'nonce', 'signature'))) $this->auth->{$key} = $value;
            else $this->properties->{$key} = $value;
        }

        $this->_validateFields(array('key' => 'API Key', 'nonce' => 'Nonce', 'signature' => 'API Signature'));

        $this->authenticate();

        $this->checkRateLimit();

        // Load language stuff
        $language = $this->user->language;
        $this->lang->load('controllers', $language);
        $this->lang->load('models', $language);
        $this->lang->load('misc', $language);

        $this->language = $language;
    }

    private function authenticate() {
        if (!$this->verifyApi())
            $this->_error(101, 'Invalid API Code or Invalid Signature: ' . $this->auth->key);

        if (!$this->verifySignature())
            $this->_error(101, 'Invalid API Code or Invalid Signature: ' . $this->auth->key);

        $this->verifyNonce();

        $this->user = $this->user_model->getUser($this->userId);

        if ((int)$this->user->suspended > 0)
            $this->_error(300, 'Your account is currently suspended. Please contact customer support for additional information');

        return true;
    }

    private function verifyApi() {
        $this->api = $this->api_model->getApiFromKey($this->auth->key);

        if ($this->api != null) {
            $this->api_name = $this->api->name;

            return true;
        }

        return false;
    }

    private function verifySignature() {
        $this->userId = $this->api->client;

        // Old style hash - used in v1 API
        $oldstylehash = hash_hmac('sha256', $this->auth->nonce . $this->auth->key . $this->userId, $this->api->secret);
        if ($oldstylehash === strtolower($this->auth->signature)) {
            $this->hash = $oldstylehash;
            return true;
        }

        // New style hash - used in v2 API - (oops - Corrected to match Bitstamp, Jan 20 2015)
        $newstylehash = hash_hmac('sha256', $this->auth->nonce . $this->userId . $this->auth->key, md5($this->api->secret));
        if ($newstylehash === strtolower($this->auth->signature)) {
            $this->hash = $newstylehash;
            return true;
        }

        // And now we need to check reverse ordered one too :-(
        $newstylehash_wrong = hash_hmac('sha256', $this->auth->nonce . $this->auth->key . $this->userId, md5($this->api->secret));
        if ($newstylehash_wrong === strtolower($this->auth->signature)) {
            $this->hash = $newstylehash_wrong;
            return true;
        }

        return false;
    }

    private function verifyNonce() {
        if (!preg_match('/^[0-9]+$/', $this->auth->nonce))
            $this->_error(103, 'Field nonce should be an integer');

        if (!$this->api_model->checkNonce($this->auth->key, $this->auth->nonce))
            $this->_error(104, 'Cannot perform request - nonce already used');
    }

    public function balances() {
        $currencies = $this->meta_model->currencies;

        $balances = $this->user_balance_model->get($this->userId);

        $out = array();
        foreach ($currencies as $currency) {
            $out[$currency]             = rCurrency($currency, $balances->{$currency . '_available'}, '');
            $out[$currency . '_locked'] = rCurrency($currency, $balances->{$currency . '_locked'}, '');
        }

        $this->_display($out);
    }

    public function cancel() {
        $this->_validateFields(array('id' => 'Order Id'));

        if (!parent::_cancel($this->_getProperty('id')))
            $this->_error(106, 'Cannot perform request - not found');
        else $this->_display('ok');
    }

    public function orders() {
        $book = $this->_getProperty('book'); // optional

        if ($book && !isset($this->meta_model->books[$book]))
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $orders = $this->order_model->getForUser($this->userId, $book, 100);
        $temp = array_map('_anonymiseUserOrderForAPI', $orders);

        $out = array();

        // Split the data by book
        foreach ($temp as $t) {
            $book = $t['book'];
            unset($t['book']);
            $out[$book][] = $t;
        }

        $this->_display($out);
    }

    public function trades() {
        $book = $this->_getProperty('book'); // optional
        $limit = $this->_getProperty('limit'); // optional
        $offset = $this->_getProperty('offset'); // optional
        $sort = $this->_getProperty('sort'); // optional

        if (empty($limit) || $limit < 0) $limit = 100;
        if (empty($offset) || $offset < 0) $offset = 0;
        if (empty($sort)) $sort = 'desc';

        if ($book && !isset($this->meta_model->books[$book]))
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $userId = $this->userId;

        $trades = array();

        $this->trade_model->getCountForUser($userId);
        $tradeIds = $this->trade_model->entries;

        if ($sort == 'asc')
            $tradeIds = array_reverse($tradeIds);

        foreach ($tradeIds as $id) {
            $trade = $this->trade_model->get($id);
            if ($userId == $trade->major_client) {
                $trade->fee   = $trade->minor_fee;
                $trade->total = $trade->minor_total;
                $trade->type  = 'sell';
                if (isset($trade->major_order)) {
                    $order = json_decode($trade->major_order);
                    if (isset($order->uid))
                        $trade->order_id = $order->uid;
                    else $trade->order_id = $order->id;
                }
            }
            else if ($userId == $trade->minor_client) {
                $trade->fee   = $trade->major_fee;
                $trade->total = $trade->major_total;
                $trade->type  = 'buy';
                if (isset($trade->minor_order)) {
                    $order = json_decode($trade->minor_order);
                    if (isset($order->uid))
                        $trade->order_id = $order->uid;
                    else $trade->order_id = $order->id;
                }
            }

            $trades[] = $trade;
        }

        $temp = array_map('_anonymiseUserTradeForAPI', $trades);
        $out = array();
        $idx = 0;

        // Split the data by book
        foreach ($temp as $t) {
            $tradeBook =  $t["major_currency"] . '_' .  $t["minor_currency"];
            if ((empty($book) || $book == $tradeBook) && $idx >= $offset) {
                $out[$tradeBook][] = $t;
            }
            $idx++;
            if (($idx-$offset) > $limit) break;
        }

        $this->_display($out);
    }

    public function deposit() {
        $this->_validateFields(array('currency' => 'Currency'));

        $currency = strtolower($this->_getProperty('currency'));

        if (in_array($currency, $this->meta_model->currencies) === FALSE)
            $this->_error(107, 'Currency ' . strtoupper($currency) . ' is not supported');

        switch ($currency) {
            case 'btc':
                $this->load->model('bitcoin_model');

                $address = $this->bitcoin_model->getFromBitcoind($this->userId);
                if (!$address) {
                    if ($blockchain = $this->bitcoin_model->getBlockchainAddress($this->userId))
                        $address = isset($blockchain->input_address) ? $blockchain->input_address : false;

                    if (!$address)
                        $address = $this->bitcoin_model->get();
                }

                $this->_display(array('address' => $address));

                break;

            default:
                $this->_error(201, 'Not implemented');
        }
    }

    public function withdraw() {
        $this->_validateFields(array('currency' => 'Currency', 'amount' => 'Amount', 'address' => 'Bitcoin Address'));

        $address = $this->_getProperty('address');

        $apiWithdrawalAddress = isset($this->api->withdrawal_address) ? $this->api->withdrawal_address : '';

        if ($apiWithdrawalAddress != '' && $address != $apiWithdrawalAddress)
            $this->_error(108, 'Address: ' . $address . ' rejected - does not match API withdrawal address');

        $currency = strtolower($this->_getProperty('currency'));

        if (in_array($currency, $this->meta_model->currencies) === FALSE)
            $this->_error(107, 'Currency ' . strtoupper($currency) . ' is not supported');

        $amount = $this->_getProperty('amount');
        if (!checkPositive($currency, $amount))
            $this->_error(22, 'Incorrect Value: ' . displayCurrency($currency, $amount, false, false) . ' must be positive');

        if (bccomp($amount, '0.0001', getPrecision($currency)) < 0)
            $this->_error(23, 'Incorrect Value: ' . displayCurrency($currency, $amount, false, false) . ' is below the minimum of ' . displayCurrency($currency, '0.0001', false, false));

        $balances = $this->user_balance_model->get($this->userId);

        if (bccomp($amount, $balances->{$currency . '_available'}, getPrecision($currency)) > 0)
            $this->_error(21, 'Insufficient Funds: Order with value ' . displayCurrency($currency, $amount, false, false) . ' exceeds balance of ' . displayCurrency($currency, $balances->{$currency . '_available'}, false, false));

        switch ($currency) {
            case 'btc':
                $data = array(
                    'client'   => $this->userId,
                    'amount'   => $amount,
                    'currency' => $currency,
                    'method'   => $currency
                );

                $details = array(
                    'address' => $address
                );

                if ($this->withdrawal_model->add($data, $details))
                    $this->_display('ok');
                else $this->_error(99, 'Error processing the request');

                break;

            default:
                $this->_error(201, 'Not implemented');
        }
    }

    public function buy() {
        $this->_processOrder('buy');
    }

    public function sell() {
        $this->_processOrder('sell');
    }

    public function buygsr() {
        $this->_processOrder('buy','limit',2);
    }

    public function sellgsr() {
        $this->_processOrder('sell','limit',2);
    }

    protected function _processOrder($type, $mode='limit', $apiversion=1) {
        $this->properties->method = 'api'; // Making sure the order will be created with the correct method

        switch ($mode) {
            case 'limit':
                $order = parent::_process($type);
                break;

            case 'market':
                $order = parent::_processMarket($type);
                break;
        }

        switch ($apiversion) {
            case 1:
                $this->_display('OK');

            case 2:
                return $order;
        }
    }
}