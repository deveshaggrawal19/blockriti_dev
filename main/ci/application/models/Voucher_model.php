<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Voucher_model extends Redis_model {

    private $items;

    public function __construct() {
        parent::__construct();
    }

    public function generate($amount, $data) {
        $vouchers = array();

        for ($i = 0; $i < $amount; $i++) {
            $code = random_string('alnum', 16);

            $voucherData = array(
                'value'    => rCurrency($data['currency'], $data['value'], ''),
                'currency' => $data['currency'],
                '_created' => $this->now,
                '_updated' => $this->now,
                'client'   => '',
                'referrer' => $data['referrer']
            );

            $this->redis->hmset('voucher:' . $code, $voucherData);
            if ((int)$data['expiry'] > 0)
                $this->redis->expire('voucher:' . $code, (int)$data['expiry'] * 3600 * 24);

            $vouchers[] = $code;
        }

        return $vouchers;
    }

    public function userGenerate($data) {
        if(!isset($data['code'])){
            $code = random_string('alnum', 16);
        } else {
            $code = $data['code'];
        }
        

        $voucherData = array(
            'value'    => rCurrency($data['currency'], $data['value'], ''),
            'currency' => $data['currency'],
            '_created' => $this->now,
            '_updated' => $this->now,
            'from'     => $data['user_id'],
            'client'   => '',
            'referrer' => $data['referrer'],
            'withdrawal' => $data['withdrawal']
        );

        $this->redis->hmset('voucher:' . $code, $voucherData);
        
        if ((int)$data['expiry'] > 0)
            $this->redis->expire('voucher:' . $code, (int)$data['expiry'] * 3600 * 24);
                
        $this->redis->rpush('user:' . $data['user_id'] . ':outvouchers', 'voucher:' . $code);

        return $code;
    }
    
    public function generateCode() {
        return random_string('alnum', 16);
    }
    
    public function update($code, $voucherData) {
        $this->redis->hmset('voucher:' . $code, $voucherData);
    }
    
    public function remove($code) {
        $voucher = $this->flatten($this->redis->hgetall('voucher:' . $code));
        $balances = $this->user_balance_model->get($voucher->referrer);
        $newBalance = bcadd($balances->{$voucher->currency}, $voucher->value, getPrecision($balances->currency));
        $this->user_balance_model->save($voucher->referrer, array($voucher->currency => $newBalance));
        if(property_exists($voucher, 'withdrawal')){
            $id = _numeric($voucher->withdrawal);
            $withdrawal = $this->withdrawal_model->get($id);
            $data['status'] = 'canceled';
            $this->withdrawal_model->update($withdrawal->id, $data);
        }
        $this->redis->rpop('user:' . $voucher->referrer . ':outvouchers');
        $this->redis->del('voucher:' . $code);
    }
    
    public function existCoupon($code) {
        return $this->redis->exists('voucher:' . $code);
    }
    
    public function unusedCoupon($code) {
        $coupon = $this->flatten($this->redis->hgetall('voucher:' . $code));
        if($coupon->client != ''){
            return false;
        }
        return true;
    }

    public function get($code) {
        return $this->flatten($this->redis->hgetall('voucher:' . $code));
    }

    public function redeem($code, $userId) {
        $data = array(
            'client'   => $userId,
            '_updated' => $this->now
        );

        $this->redis->hmset('voucher:' . $code, $data);
        $this->redis->rpush('user:' . $userId . ':vouchers', 'voucher:' . $code);

        $this->redis->persist('voucher:' . $code);

        $voucherData = $this->get($code);
        if (isset($voucherData->referrer)) {
            // There is a voucher referrer set up - check on the user's registered date:
            // If below 24 hours and no other referrer was set add him as a referral
            $userData = $this->flatten($this->redis->hgetall('user:' . $userId));

            if (!isset($userData->referrer_id) && $userData->_created > $this->now - (24 * 3600 * 1000)) {
                $this->load->model('referral_model');

                $this->referral_model->addToUser($userId, $voucherData->referrer);
            }
        }
    }

    public function getCount() {
        $items = $this->redis->keys('voucher:*');

        foreach ($items as $voucherId) {
            $voucher = $this->flatten($this->redis->hgetall($voucherId));

            $voucher->code = str_replace('voucher:', '', $voucherId);

            $this->items[] = $voucher;
        }

        // Order the vouchers by date
        usort($this->items, function($a, $b) {
            return $a->_created < $b->_created ? 1 : -1;
        });

        return count($items);
    }

    public function getSubset($page = 1, $perPage = 50) {
        $start = ($page - 1) * $perPage;
        $end   = min($start + $perPage, count($this->items));

        $vouchers = array();
        for ($i = $start; $i < $end; $i++) {
            $voucher = $this->items[$i];

            $userId = $voucher->client;
            if ($userId != '') {
                $firstName = $this->redis->hget('user:' . $userId, 'first_name');
                $lastName = $this->redis->hget('user:' . $userId, 'last_name');
                $voucher->clientName = $firstName . ' ' . $lastName;
            }
            else {
                $ttl = $this->redis->ttl('voucher:' . $voucher->code);
                if ($ttl >= 0)
                    $voucher->ttl = $ttl;
            }

            $voucher->userGenerated = isset($voucher->from);

            $referrerId = isset($voucher->referrer) ? $voucher->referrer : false;
            if ($referrerId) {
                $firstName = $this->redis->hget('user:' . $referrerId, 'first_name');
                $lastName = $this->redis->hget('user:' . $referrerId, 'last_name');
                $voucher->referrerName = $firstName . ' ' . $lastName;
            }

            $vouchers[] = $voucher;
        }

        return $vouchers;
    }
}