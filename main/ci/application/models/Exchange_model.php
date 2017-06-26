<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Exchange_model extends Redis_model {

    public $entries;

    public function __construct() {
        parent::__construct();
    }
    public function setBrokerageRate($rate)
    {
        $this->redis->set("brokerage:rate", $rate);
    }
    public function getBrokerageRate()
    {
        $rate = $this->redis->get("brokerage:rate");
        return $rate;
    }
    public function setBrokerageFee($fee)
    {
        $this->redis->set("brokerage:fee", $fee);
    }
    public function getBrokerageFee()
    {
        $fee = $this->redis->get("brokerage:fee");
        if(!$fee){$fee = 0;}
        $fee = str_replace("%",'',$fee);
        return $fee;
    }


    public function setBrokerage($data){
        //print_r($data);
        $this->redis->set("brokerage:buy", $data['buy']);
        $this->redis->set("brokerage:sell", $data['sell']);
    }
    public function getBrokerage(){
        return array(
            'buy'  => $this->redis->get('brokerage:buy'),
            'sell' => $this->redis->get('brokerage:sell')
        );
    }

    public function setRate($from, $to, $rate) {
        $this->redis->set("rate:$from:$to", $rate);
    }

    public function getRate($from, $to) {
        return $this->redis->get("rate:$from:$to");
    }

    public function setLocalRate($currency, $sell, $buy) {
        $this->redis->set('local:buy:' . $currency, $buy);
        $this->redis->set('local:sell:' . $currency, $sell);
    }

    public function getLocalRate($currency) {
        return array(
            'buy'  => $this->redis->get('local:buy:' . $currency),
            'sell' => $this->redis->get('local:sell:' . $currency)
        );
    }

    public function findTotalExchanges($userId) {
        $exchangeIds = $this->redis->lrange('user:' . $userId . ':exchanges', 0, -1);
        $total = '0';

        if ($exchangeIds) {
            foreach ($exchangeIds as $exchangeId) {
                $exchange = $this->flatten($this->redis->hgetall($exchangeId));

                if ($exchange && $exchange->date > ($this->now - (24 * 3600 * 1000)))
                    $total = bcadd($total, $exchange->amount, 2);
                else break;
            }
        }

        return $total;
    }

    public function saveTemp($data) {
        $code = generateRandomString(20, true);

        $key = 'exchange:' . $code;
        $this->redis->hmset($key, $data);
        $this->redis->expire($key, 60); // expires in 60seconds

        return $code;
    }

    public function getTemp($code) {
        $key  = 'exchange:' . $code;
        $data = $this->flatten($this->redis->hgetall($key));

        // Let's get rid of the temp
        $this->redis->del($key);

        return $data;
    }

    public function process($data) {
        unset($data['code']);

        $id = $this->newRandomId('exchange');

        $data['_id']  = $id;
        $data['date'] = $this->now;

        $userId = $data['client'];

        $this->lock();

        // Create the exchange
        $this->redis->hmset($id, $data);
        // Add it to the user's exchanges
        $this->redis->lpush('user:' . $userId . ':exchanges', $id);

        // Change the balances of the user
        $balances = $this->user_balance_model->get($userId);

        $balances->{$data['from']} = bcsub($balances->{$data['from']}, $data['amount'], 2);
        $balances->{$data['to']} = bcadd($balances->{$data['to']}, $data['value'], 2);

        // And save it
        $this->user_balance_model->save($userId, $balances);

        $this->unlock();

        // Remove the cache
        $this->caching_model->delete('user:' . $userId . ':exchanges');

        return true;
    }

    public function getCount($userId) {
        $this->entries = $this->caching_model->get('user:' . $userId . ':exchanges');
        if (!$this->entries) {
            $entries = $this->redis->lrange('user:' . $userId . ':exchanges', 0, -1);

            $this->entries = array();
            foreach ($entries as $entryId) {
                $exchange = $this->flatten($this->redis->hgetall($entryId));
                if ($exchange)
                    $this->entries[] = $exchange;
            }

            $this->caching_model->save($this->entries, ONE_DAY);
        }

        return count($this->entries);
    }

    public function getSubset($page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage;

        $result = array();
        for ($i = $start; $i < $end; $i++) {
            if (!isset($this->entries[$i])) break;

            $result[] = $this->entries[$i];
        }

        return $result;
    }
}