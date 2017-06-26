<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Api.php');

class Public_api extends Api {

    public function __construct() {
        parent::__construct();

        $this->checkApiEnabled('apipublic');

        $this->checkRateLimit();
    }

    public function info() {
        $out = array();
        foreach (array_keys($this->meta_model->books) as $book) {
            $out[$book]['rate']   = $this->trade_model->getLastTradePrice($book);
            $out[$book]['volume'] = $this->trade_model->getRollingVolume($book);

            $list = 'orders:sell:' . $book;
            $sell = $this->order_model->head($list);
            if ($sell)
                $out[$book]['sell'] = $sell->rate;
            else $out[$book]['sell'] = 0;

            $list = 'orders:buy:' . $book;
            $buy = $this->order_model->head($list);
            if ($buy)
                $out[$book]['buy'] = $buy->rate;
            else $out[$book]['buy'] = 0;
        }

        $this->_display($out);
    }

    public function orders() {
        $book = $this->input->get('book');

        if (!$book)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        if (in_array($book, array_keys($this->meta_model->books)) === FALSE)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $out = array();

        $this->redis_model->lock();

        $list  = 'orders:sell:' . $book;
        $sells = $this->order_model->getOrders($list, 1000);

        $out['sell'] = array_map('_anonymiseOrder', $sells);

        $list = 'orders:buy:' . $book;
        $buys = $this->order_model->getOrders($list, 1000);

        $out['buy']= array_map('_anonymiseOrder', $buys);

        $this->redis_model->unlock();

        $this->_display($out);
    }

    // Special version that reveals which orders belong to GSR (for GSR to use)
    public function ordersgsr() {
        $book = $this->input->get('book');

        if (!$book)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        if (in_array($book, array_keys($this->meta_model->books)) === FALSE)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $out = array();

        $this->redis_model->lock();

        $list  = 'orders:sell:' . $book;
        $sells = $this->order_model->getOrders($list, 1000);

        $out['sell'] = array_map('_anonymiseOrderGSR', $sells);

        $list = 'orders:buy:' . $book;
        $buys = $this->order_model->getOrders($list, 1000);

        $out['buy']= array_map('_anonymiseOrderGSR', $buys);

        $this->redis_model->unlock();

        $this->_display($out);
    }

    public function trades() {
        $book = $this->input->get('book');

        if (!$book)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        if (in_array($book, array_keys($this->meta_model->books)) === FALSE)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $trades = $this->trade_model->getTrades($book, 50);

        $out = array_map('_anonymiseTrade', $trades);

        $this->_display($out);
    }
}