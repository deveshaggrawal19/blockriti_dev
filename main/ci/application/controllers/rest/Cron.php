<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        //todo libraries used, report
        $this->load->library('redis');
        $this->load->library('api_security');
        //todo Models that are used for api
        $this->load->model('redis_model');
        $this->load->model('api_model');
        $this->load->model('trade_model');
        $this->load->model('order_model');
        $this->load->model('meta_model');
        $this->load->model('permissions_model');
        $this->load->model('user_model');
        $this->load->model('note_model');
        $this->load->model('user_balance_model');
        $this->load->model('caching_model');
        $this->load->model('deposit_model');
        $this->load->model('withdrawal_model');
        $this->load->model('notification_model');
        $this->load->model('admin_model');
        $this->load->model('bitcoin_model');
        $this->load->model('rest_user_model');
    }
    public function checkBitcoinDeposits() {

        $this->load->model('admin_model');
        $this->load->model('bitcoin_model');
        if($this->admin_model->getBitcoinDepositData('status') != "disabled"){
            $this->bitcoin_model->checkBitcoind();
        }
        echo "Request done: ".date("Y-m-d H:i:s");
    }

    public function bitcoinAutoWithdrawalsRequest() {
        $this->load->model('admin_model');
        $this->load->model('bitcoin_model');
        sleep(5);
        if($this->admin_model->getBitcoinWithdrawalsData('status') != "disabled"){
            $this->bitcoin_model->bitcoinAutoWithdrawals();
        }
        echo "Request AutoWithdrawals done: ".date("Y-m-d H:i:s");
    }

    public function garbageCollectionProtectimus() {
        $this->rest_user_model->clearProtectimusGarbage();
        echo "Protectimus unused token cleanup done: ".date("Y-m-d H:i:s");
    }
}
