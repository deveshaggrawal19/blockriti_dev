<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
//todo this has been added by insart
include_once(APPPATH . 'controllers/_api/Public_api.php');

class Public_api_v2 extends Public_api {

    public function __construct() {
        parent::__construct();
    }

    public function info() {
        $book = $this->input->get('book'); // optional
        $book = strtolower($book);

        if (empty($book))
            $book = $this->config->item("default_book");
            
        $out = $this->getInfo($book);
        
        if ($book == 'all')
            $this->_display($out);
        else $this->_display($out[$book]);
    }

    public function order_book() {
        $out = $this->getOrderBook();

        $this->_display($out);
    }

    public function transactions() {
        $book = $this->input->get('book'); // optional
        if (empty($book))
            $book = $this->config->item("default_book");

        $timeframe = strtolower($this->input->get('time')); // optional;
        if (empty($timeframe))
            $timeframe = 'hour';

        if (in_array($timeframe, array('minute', 'hour')) === FALSE)
            $this->_error(20, 'Bad Time Frame ' . $timeframe);

        if (in_array($book, array_keys($this->meta_model->books)) === FALSE)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $mins = array(
            'hour'   => 60,
            'minute' => 1
        );
        $mins = $mins[$timeframe];

        $trades = $this->trade_model->getTradesTimeframe($book, $mins);

        $out = array_map('_anonymiseTrade2', $trades);

        $this->_display($out);
    }
}
