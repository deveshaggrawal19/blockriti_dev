<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Auth_Api.php');

class History extends Auth_api {

    private $_obj_Memcache;

    public function __construct() {
        parent::__construct();

        $this->load->model('deposit_model');
        $this->load->model('withdrawal_model');       
        $this->load->model('referral_model');
        $this->load->model('merchant_model');
        $this->load->model('redis_model');
        $this->load->model('rest_wallet_model');
        if ($this->user === 'guest') {
            $this->_displayErrorUnauthorised(array('code' => 23) );
        }
    }

    public function getUserHistory()
    {
        $aReturnArray = array();
        $aTradesData = $this->_getTrades();
        $aFundingData = $this->_getFundings();
        $aWithdrawalData = $this->_getWithdrawals();
        $aReferalData = $this->_getReferrals();

        $aReturnArray['count'] = (_numeric($aTradesData['count']) + _numeric($aFundingData['count']) + _numeric($aWithdrawalData['count']) + _numeric($aReferalData['count']));
        $aReturnArray['entries'] = array_merge($aTradesData['entries'],$aFundingData['entries'],$aWithdrawalData['entries'],$aReferalData['entries']);

        $this->_displaySuccess(json_encode($aReturnArray) );
    }

    private function _getTrades()
    {
        $aReturnArray = array();
        $aReturnArray['entries'] = array();
        $data = $this->rest_wallet_model->getTradesData($this->userId);
        $aReturnArray['count'] = $data['count'];
        foreach($data['entries'] as $order)
        {
            $tempData = array();
            if($order->type == 'buy')
            {
                $tempData['btc'] = bcadd($order->fee, $order->total, getPrecision($order->major_currency) );
                $tempData['cad'] = $order->minor_total;
            }
            else if($order->type == 'sell')
            {
                $tempData['btc'] = $order->major_total;
                $tempData['cad'] = bcadd($order->fee, $order->total, getPrecision($order->minor_currency) );
            }
            $tempData['status'] = 'complete';
            $tempData['date'] = $order->datetime;
            $tempData['type'] = 'trade';
            $tempData['action'] = $order->type == 'buy' ? 'Buy':'Sell';
            $tempData['rate'] = $order->rate;
            $tempData['fee'] = $order->fee;
            $tempData['net'] = $order->total;
            $aReturnArray['entries'][] = $tempData;
        }
        return $aReturnArray;
    }

    private function _getFundings()
    {
        $aReturnArray = array();
        $aReturnArray['entries'] = array();
        $data = $this->rest_wallet_model->getFundingData($this->userId);
        $aReturnArray['count'] = $data['count'];
        foreach($data['entries'] as $order)
        {
            $tempData = array();
            if($order->currency == "btc" )
            {
                $tempData['btc'] = empty($order->gross) ? $order->amount : $order->gross;
                $tempData['cad'] = NULL;
            }
            else if($order->currency == "cad" )
            {
                $tempData['btc'] = NULL;
                $tempData['cad'] = empty($order->gross) ? $order->amount : $order->gross;
            }
            $tempData['date'] = $order->_updated;
            $tempData['status'] = $order->status;
            $tempData['type'] = 'deposit';
            $tempData['action'] = code2Name($order->method);
            $tempData['rate'] = NULL;
            $tempData['fee'] = empty($order->fee) ? '0' : $order->fee;
            $tempData['net'] = $order->amount;
            $aReturnArray['entries'][] = $tempData;
        }
        return $aReturnArray;

    }

    private function _getWithdrawals()
    {
        $aReturnArray = array();
        $aReturnArray['entries'] = array();
        $data = $this->rest_wallet_model->getWithdrawalData($this->userId);
        $aReturnArray['count'] = $data['count'];
        foreach($data['entries'] as $order)
        {
            $tempData = array();
            if($order->currency == "btc" )
            {
                $tempData['btc'] = $order->amount;
                $tempData['cad'] = NULL;
            }
            else if($order->currency == "cad" )
            {
                $tempData['btc'] = NULL;
                $tempData['cad'] = $order->amount;
            }
            $tempData['date'] = $order->_updated;
            $tempData['status'] = $order->status;
            $tempData['type'] = 'withdrawal';
            $tempData['action'] = code2Name($order->method);
            $tempData['rate'] = NULL;
            $tempData['fee'] = NULL;
            $tempData['net'] = $order->amount;
            $aReturnArray['entries'][] = $tempData;
        }
        return $aReturnArray;
    }

    private function _getReferrals()
    {
        $aReturnArray = array();
        $aReturnArray['entries'] = array();
        $data = $this->rest_wallet_model->getReferalData($this->userId);
        $aReturnArray['count'] = $data['count'];
        foreach($data['entries'] as $order)
        {
            $tempData = array();
            if($order->currency == "btc" )
            {
                $tempData['btc'] = $order->amount;
                $tempData['cad'] = NULL;
            }
            else if($order->currency == "cad" )
            {
                $tempData['btc'] = NULL;
                $tempData['cad'] = $order->amount;
            }
            $tempData['date'] = $order->date;
            $tempData['status'] = 'complete';
            $tempData['type'] = 'referal';
            $tempData['action'] = 'Referral Earnings';
            $tempData['rate'] = NULL;
            $tempData['fee'] = NULL;
            $tempData['net'] = $order->amount;
            $aReturnArray['entries'][] = $tempData;
        }
        return $aReturnArray;
    }
}