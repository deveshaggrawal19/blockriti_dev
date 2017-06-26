<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/_api/Private_api.php');

class Private_api_v2 extends Private_api {

    public function __construct() {
        parent::__construct();
    }

    public function balance() {
        $currencies = $this->meta_model->currencies;

        $balances = $this->user_balance_model->get($this->userId);

        $out = array();
        foreach ($currencies as $currency) {
            $out[$currency . '_available'] = rCurrency($currency, $balances->{$currency . '_available'}, '');
            $out[$currency . '_reserved']  = rCurrency($currency, $balances->{$currency . '_locked'}, '');
            $out[$currency . '_balance']   = rCurrency($currency, $balances->{$currency}, '');
        }

        $out['fee'] = number_format(100 * $this->trade_model->getTradeFee($this->userId), 4);

        $this->_display($out);
    }

    public function transactions() {
        $book = $this->_getProperty('book'); // optional
        if (empty($book))
            $book = $this->config->item("default_book");

        if (!isset($this->meta_model->books[$book]))
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $limit  = $this->_getProperty('limit'); // optional
        $offset = $this->_getProperty('offset'); // optional
        $sort   = $this->_getProperty('sort'); // optional

        if (empty($limit) || $limit < 0) $limit = 100;
        if (empty($offset) || $offset < 0) $offset = 0;
        if (empty($sort)) $sort = 'desc';

        $userId = $this->userId;

        $trades = array();

        $this->trade_model->getCountForUser($userId);
        $tradeids = $this->trade_model->entries;

        if ($sort == 'asc')
            $tradeids = array_reverse($tradeids);

        foreach ($tradeids as $id) {
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
        $out  = array();
        $idx  = 0;

        // Split the data by book
        foreach ($temp as $t) {
            $tradeBook = $t["major_currency"] . '_' . $t["minor_currency"];
            unset($t["major_currency"]);
            unset($t["minor_currency"]);
            unset($t[$tradeBook]);

            if ((empty($book) || $book == $tradeBook) && $idx >= $offset)
                $out[$tradeBook][] = $t;

            $idx++;

            if (($idx - $offset) > $limit)
                break;
        }

        $this->_display($out[$book]);
    }

    public function open_orders() {
        $book = $this->_getProperty('book'); // optional
        if (empty($book))
            $book = $this->config->item("default_book");

        if (!isset($this->meta_model->books[$book]))
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $orders = $this->order_model->getForUser($this->userId, $book, 100);

        $temp = array_map('_anonymiseUserOrderForAPI2', $orders);

        $out = array();
        $out[$book] = array();

        // Split the data by book
        foreach ($temp as $t) {
            $tbook = $t['book'];
            unset($t['book']);
            $out[$tbook][] = $t;
        }

        $this->_display($out[$book]);
    }

    public function cancel_order() {
        $this->_validateFields(array('id' => 'Order Id'));

        if ($result = parent::_cancel($this->_getProperty('id')))
            $this->_display($result);
        else $this->_error(106, 'Cannot perform request - not found');
    }

    public function lookup_order() {
        $this->_validateFields(array('id' => 'Order Id'));

        if ($result = parent::_lookup_order($this->_getProperty('id')))
            $this->_display($result);
        else $this->_error(106, 'Cannot perform request - not found');
    }

    public function buy() {
        $book = $this->_getProperty('book'); // optional
        if (empty($book))
            $book = $this->config->item("default_book");
        if (!isset($this->meta_model->books[$book]))
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $c                       = explode("_", $book);
        $this->properties->major = $c[0];
        $this->properties->minor = $c[1];
        $this->properties->rate  = $this->properties->price;

        $mode = 'limit';
        if (empty($this->properties->rate))
            $mode = 'market';

        $order = parent::_processOrder('buy', $mode, 2);
        if ($mode == 'limit') {
            $out = array_map('_anonymiseUserOrderForAPI2', array($order));
            $this->_display($out[0]);
        }
        else {
            $orderparts = array();
            foreach ($order['orders'] as $ord)
                $orderparts[] = array("amount" => $ord['amount'], "price" => $ord['rate']);

            $out = array(
                "amount"         => $order['net'],
                "orders_matched" => $orderparts
            );

            $this->_display($out);
        }
    }

    public function sell() {
        $book = $this->_getProperty('book'); // optional
        if (empty($book))
            $book = $this->config->item("default_book");
        if (!isset($this->meta_model->books[$book]))
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $c                       = explode("_", $book);
        $this->properties->major = $c[0];
        $this->properties->minor = $c[1];
        $this->properties->rate  = $this->properties->price;

        $mode = 'limit';
        if (empty($this->properties->rate))
            $mode = 'market';

        $order = parent::_processOrder('sell', $mode, 2);
        if ($mode == 'limit') {
            $out = array_map('_anonymiseUserOrderForAPI2', array($order));
            $this->_display($out[0]);
        }
        else {
            $orderparts = array();
            foreach ($order['orders'] as $ord)
                $orderparts[] = array("amount" => $ord['amount'], "price" => $ord['rate']);

            $out = array(
                "amount"         => $order['net'],
                "orders_matched" => $orderparts
            );

            $this->_display($out);
        }
    }

    public function bitcoin_withdrawal() {
        $this->properties->currency = 'btc';
        parent::withdraw();
    }

    public function ripple_withdrawal() {
        $this->_validateFields(array('currency' => 'Currency', 'amount' => 'Amount', 'address' => 'Ripple Address'));

        $send_address = $this->properties->address;
        $currency     = strtolower($this->properties->currency);
        if (in_array($currency, $this->meta_model->currencies) === FALSE)
            $this->_error(107, 'Currency ' . strtoupper($currency) . ' is not supported');

        $amount = $this->properties->amount;
        if (!checkPositive($currency, $amount))
            $this->_error(22, 'Incorrect Value: ' . displayCurrency($currency, $amount, false, false) . ' must be positive');

        $balances = $this->user_balance_model->get($this->userId);

        if (bccomp($amount, $balances->{$currency . '_available'}, getPrecision($currency)) > 0)
            $this->_error(21, 'Insufficient Funds: Order with value ' . displayCurrency($currency, $amount, false, false) . ' exceeds balance of ' . displayCurrency($currency, $balances->{$currency . '_available'}, false, false));

        // MAKE WITHDRAWAL
        $data = array(
            'client'   => $this->userId,
            'method'   => 'rp',
            'currency' => $currency,
            'amount'   => $amount
        );

        $details = array(
            'address' => $send_address
        );

        $withdrawal = $this->withdrawal_model;

        if ($withdrawal->add($data, $details)) {

            $withdrawal_id = _numeric($withdrawal->_id);

            // SEND TO RIPPLE
            $err = null;
            $this->load->model('ripple_model');
            $ok = $this->ripple_model->withdraw($withdrawal_id, $send_address, $currency, $amount, $err);

            if ($ok) {
                // All good
                $this->user_model->setRippleAddr($this->userId, $send_address);
                $this->_display('ok');
            } else {
                $this->withdrawal_model->delete($withdrawal_id);
                //systemEmail("Problem with Ripple withdrawal via API - User #" . $this->userId . " (" . $amount . " " . $currency . ") ".$err);
                $this->_error(24, 'Error with ripple withdrawal');
            }
        } else {

            $this->_error(24, 'Error with ripple withdrawal');
        }
    }

    public function bitcoin_deposit_address() {
        $this->load->model('bitcoin_model');

        $address = $this->bitcoin_model->getFromBitcoind($this->userId);
        if (!$address) {
            if ($blockchain = $this->bitcoin_model->getBlockchainAddress($this->userId))
                $address = isset($blockchain->input_address) ? $blockchain->input_address : false;

            if (!$address)
                $address = $this->bitcoin_model->get();
        }

        $this->_display($address);
    }

    public function unconfirmed_btc() {
        $this->_error(201, 'Not implemented');
        // TODO
    }

    public function withdrawal_requests() {
        $this->_error(201, 'Not implemented');
        // TODO
    }
}