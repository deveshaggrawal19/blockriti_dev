<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class User_balance_model extends Redis_model {

    public function __construct() {
        parent::__construct();

        $this->load->model('meta_model');
        $this->meta_model->getAllCurrencies();
    }

    public function init($userId) {
        $balances = array();
        foreach ($this->meta_model->currencies as $currency) {
            foreach (array('', '_locked', '_pending_deposit', '_pending_withdrawal') as $suffix)
                $balances[$currency . $suffix] = rCurrency($currency, 0);
        }

        return $this->save($userId, $balances);
    }

    public function save($userId, $data = null) {
        $balances = (array)$data;

        foreach ($this->meta_model->currencies as $currency)
            unset($balances[$currency . '_available']);

        return $this->redis->hmset('user:' . $userId . ':balances', $balances);
    }
    
    public function saveForVoucher($userId, $data = null) {
        $balances = (array)$data;
        
        return $this->redis->hmset('user:' . $userId . ':balances', $balances);
    }

    public function get($userId) {
        $balances = $this->flatten($this->redis->hgetall('user:' . $userId . ':balances'));

        if ($balances) {
            foreach ($this->meta_model->currencies as $currency){
                $balances->{$currency . '_available'} = bcsub($balances->{$currency}, $balances->{$currency . '_locked'}, getPrecision($currency));
                $balances->{$currency . '_full'} = $balances->{$currency};
            }
            
        }

        return $balances;
    }

    public function getTotalBalances() {
        $currencies = $this->meta_model->getAllCurrencies();

        $total = $this->caching_model->get('total:balances');

        if (!$total) {
            $userBalances = $this->redis->keys('user:*:balances');
            $total = array();

            $ignore = $this->redis->lrange('ignore:user:balances', 0, -1);

            foreach ($userBalances as $userBalance) {
                foreach ($ignore as $ig) {
                    if ($userBalance == 'user:' . $ig . ':balances')
                        continue(2);
                }

                $userId   = _numeric($userBalance);
                $balances = $this->get($userId);

                if ($balances) {
                    foreach ($currencies as $currency) {
                        foreach (array('', '_locked') as $option) {
                            $key = $currency . $option;

                            if (!isset($total[$key]))
                                $total[$key] = '0';

                            $total[$key] = bcadd($total[$key], $balances->{$key}, getPrecision($currency));
                        }
                    }
                }
            }

            $this->caching_model->save($total, ONE_MINUTE);
        }

        return $total;
    }
    public function transferBalance($fromUid, $toUid, $amount, $currency = 'btc')  {
        $from_balances = $this->get($fromUid);
        $to_balances = $this->get($toUid);
        if(!$currency){
            $currency = 'btc';
        }
        $to_balances->$currency = bcadd($to_balances->$currency, $amount, getPrecision($currency));
        $from_balances->$currency = bcsub($from_balances->$currency, $amount, getPrecision($currency));
        try{
            $this->save($toUid, $to_balances);
            $this->save($fromUid, $from_balances);
            return true;
        } catch(Exception $d){
            return false;
        }
    }
    public function updateBalanceByCurrency($userId, $currency, $amount, $operation ){
        $user_balances = $this->get($userId);
        switch ($operation){
            case 'add':
                $user_balances->$currency = bcadd($user_balances->$currency, $amount, getPrecision($currency));
                break;
            case 'sub':
                $user_balances->$currency = bcsub($user_balances->$currency, $amount, getPrecision($currency));
                break;
        }

        try{
            $this->save($userId, $user_balances);
            return true;
        } catch(Exception $d){
            return false;
        }
    }

    public function transferMoney($userId, $type, $amount) {
        $balances = $this->get($userId);
        $currency = 'btc';
        if($type == 's2f') {
            $balances->btc = bcsub($balances->btc, $amount, getPrecision($currency));
            $balances->btc_available = bcsub($balances->btc_available, $amount, getPrecision($currency));            
            $balances->btc_futures = bcadd($balances->btc_futures, $amount, getPrecision($currency));
        } else {
            $balances->btc = bcadd($balances->btc, $amount, getPrecision($currency));
            $balances->btc_available = bcadd($balances->btc_available, $amount, getPrecision($currency));
            $balances->btc_futures = bcsub($balances->btc_futures, $amount, getPrecision($currency));
        }
        
        $this->save($userId, $balances);
        $balances = (array)$balances;
        
        $resultArray = array('btc_available' => $balances['btc_available'], 
                             'btc_futures' => $balances['btc_futures']);
        
        print_r(json_encode($resultArray));
    }
    
    public function updateReferrerBalance($userId, $amount, $currency) {
        $user = $this->user_model->get($userId);
        if(isset($user->referrer_id)){
            $this->trade_model->processReferral($userId, $user->referrer_id, $currency, $amount);
        }
    }

    /* EXPERIMENTAL */
//    public function checkBalances($userId = '*') {
//        $userBalances = $this->redis->keys('user:' . $userId . ':balances');
//
//        $result = array();
//        foreach ($userBalances as $userBalance) {
//            $userId = str_replace(':balances', '', $userBalance);
//
//            $balances = (array)$this->flatten($this->redis->hgetall($userBalance));
//
//            $realBalances = array();
//
//            $depositIds = $this->redis->smembers($userId . ':deposits');
//            foreach ($depositIds as $depositId) {
//                $deposit = $this->flatten($this->redis->hgetall($depositId));
//
//                if ($deposit->status != 'complete') continue;
//
//                $currency = $deposit->currency;
//
//                if (!isset($realBalances[$currency]))
//                    $realBalances[$currency] = '0';
//
//                $realBalances[$currency] = bcadd($realBalances[$currency], $deposit->amount, getPrecision($currency));
//            }
//
//            $withdrawalIds = $this->redis->smembers($userId . ':withdrawals');
//            foreach ($withdrawalIds as $withdrawalId) {
//                $withdrawal = $this->flatten($this->redis->hgetall($withdrawalId));
//
//                $currency = $withdrawal->currency;
//
//                if (!isset($realBalances[$currency]))
//                    $realBalances[$currency] = '0';
//
//                $realBalances[$currency] = bcsub($realBalances[$currency], $withdrawal->amount, getPrecision($currency));
//            }
//
//            $tradeIds = $this->redis->lrange($userId . ':trades', 0, -1);
//            foreach ($tradeIds as $tradeId) {
//                $trade = $this->flatten($this->redis->hgetall($tradeId));
//
//                if (!isset($realBalances[$trade->major_currency]))
//                    $realBalances[$trade->major_currency] = '0';
//
//                if (!isset($realBalances[$trade->minor_currency]))
//                    $realBalances[$trade->minor_currency] = '0';
//
//                if ($trade->major_client == $userId) { // Sell
//                    $realBalances[$trade->major_currency] = bcsub($realBalances[$trade->major_currency], $trade->amount, getPrecision($trade->major_currency));
//                    $realBalances[$trade->minor_currency] = bcadd($realBalances[$trade->minor_currency], $trade->value, getPrecision($trade->minor_currency));
//                }
//                else { // Buy
//                    $realBalances[$trade->major_currency] = bcadd($realBalances[$trade->major_currency], $trade->amount, getPrecision($trade->major_currency));
//                    $realBalances[$trade->minor_currency] = bcsub($realBalances[$trade->minor_currency], $trade->value, getPrecision($trade->minor_currency));
//                }
//            }
//
//            $orderIds = $this->redis->lrange($userId . ':orders', 0, -1);
//            foreach ($orderIds as $orderId) {
//                $order = $this->flatten($this->redis->hgetall($orderId));
//
//                if ($order->type == 'sell') {
//                    $currency = $order->major_currency;
//                    $amount   = $order->amount;
//                }
//                else {
//                    $currency = $order->minor_currency;
//                    $amount   = $order->value;
//                }
//
//                if (!isset($realBalances[$currency]))
//                    $realBalances[$currency] = '0';
//
//                if (!isset($realBalances[$currency . '_locked']))
//                    $realBalances[$currency . '_locked'] = '0';
//
//                $realBalances[$currency] = bcsub($realBalances[$currency], $amount, getPrecision($currency));
//                $realBalances[$currency . '_locked'] = bcadd($realBalances[$currency . '_locked'], $amount, getPrecision($currency));
//            }
//
//            $inter = array_intersect_assoc($balances, $realBalances);
//            $diff  = array_diff($realBalances, $inter);
//
//            if (count($diff))
//                $result[$userId] = $diff;
//        }
//
//        return $result;
//    }
}