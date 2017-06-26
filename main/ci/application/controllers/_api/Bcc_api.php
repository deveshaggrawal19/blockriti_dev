<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/_api/Public_api.php');

class BCC_API extends Public_api {

    public function __construct() {
        parent::__construct();
    }

    public function trades($cross='') {

        if (empty($cross)) $book = $this->config->item("default_book");
        else $book = 'btc_'.$cross;

        $tid = (int)$this->input->get('since');

        if (in_array($book, array_keys($this->meta_model->books)) === FALSE)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $trades = $this->trade_model->getTradesTimeframe($book, 2); // Last 2 mins
        $trades = array_map('_anonymiseTrade2', $trades);
        $recent = array();
        foreach ($trades as $trade) {
            if ($trade["tid"] > $tid) {
                $recent[]=$trade;
            }
        }

        $this->_display($recent);
    }

    public function orderbook($cross = '') {
        if (empty($cross))
            $book = $this->config->item("default_book");
        else $book = 'btc_' . $cross;

        if (in_array($book, array_keys($this->meta_model->books)) === FALSE)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $out = array();

        $lastChange = $this->redis->get('orders:nonce:' . $book);

        header("ETag: W/$lastChange");

        if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
            // Dont need to do any more work so exit here
            exit;
        }

        $buyList = 'orders:buy:' . $book;
        $bids    = $this->order_model->getOrders($buyList, 1000);

        $sellList = 'orders:sell:' . $book;
        $asks     = $this->order_model->getOrders($sellList, 1000);

        $out['bids'] = array_map('_anonymiseOrder2', $bids);
        $out['asks'] = array_map('_anonymiseOrder2', $asks);

        $this->_display($out);
    }
}