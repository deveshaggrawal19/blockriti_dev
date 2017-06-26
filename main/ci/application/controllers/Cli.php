<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Cli extends CI_Controller {
    public function __construct() {
        parent::__construct();

        $this->load->library('redis');
        $this->load->library('layout');

        $this->load->model('redis_model');
        $this->load->model('bitcoin_model');
        $this->load->model('deposit_model');
        $this->load->model('user_model');
        $this->load->model('user_balance_model');
        $this->load->model('caching_model');
        $this->load->model('meta_model');
        $this->load->model('notification_model');
        $this->load->model('merchant_model');

        error_reporting(E_ALL);
    }

    public function processTransaction($transactionId) {
        $this->bitcoin_model->processTransaction($transactionId);
    }
}