<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Referral_model extends Redis_model {

    public $entries;

    public function getReferralCode($userId) {
        $referralCode = $this->redis->hget('user:' . $userId, 'referral_code');

        if (!$referralCode) {
            // Need to create a new one for this user
            $referralCode = generateRandomString(24, true);

            $this->redis->hset('user:' . $userId, 'referral_code', $referralCode);

            // Set a special key for fast lookups
            $this->redis->set('referral:' . $referralCode, $userId);
        }

        return $referralCode;
    }

    public function codeValid($code) {
        return $this->redis->exists('referral:' . $code);
    }

    public function findByCode($code) {
        $data   = null;
        $userId = $this->redis->get('referral:' . $code);

        if ($userId) {
            $data = $this->user_model->getUser($userId);
            $data->id = $userId;
        }

        return $data;
    }

    public function addToUser($userId, $referrerId) {
        $this->redis->hset('user:' . $userId, 'referrer_id', $referrerId);

        $this->redis->lpush('user:' . $referrerId . ':referrals', $userId);

        // Invalidate the cache relating to summary of referrals
        $this->caching_model->delete('user:' . $referrerId . ':referrals:summary');
    }

    public function getSummary($userId) {
        $this->entries = $this->caching_model->get('user:' . $userId . ':referrals:totals');
        if (!$this->entries) {
            $this->entries = array();

            $clientIds = $this->redis->lrange('user:' . $userId . ':referrals', 0, -1);

            foreach ($clientIds as $clientId) {
                $client = $this->user_model->getUser($clientId);

                // Get the total amount given by that user
                $totals = $this->flatten($this->redis->hgetall('referral:user:' . $clientId . ':total'));

                $data = array(
                    'user_id'    => $clientId,
                    'name'       => $client->first_name . ' ' . $client->last_name,
                    'registered' => $client->_created,
                    'currencies' => $totals
                );

                $this->entries[] = (object)$data;
            }

            $this->caching_model->save($this->entries, ONE_DAY);
        }

        return $this->entries;
    }

    public function getSimpleCount($userId) {
        return $this->redis->llen('user:' . $userId . ':referrals');
    }

    public function getCount($userId) {
        $this->entries = $this->caching_model->get('user:' . $userId . ':referrals:summary');
        
        if (!$this->entries) {
            $this->entries = array();

            $referrals = $this->redis->lrange('user:' . $userId . ':referrals', 0, -1);
            foreach ($referrals as $referralId) {
                $userData = $this->user_model->getUser($referralId);

                // Get their trades
                $tradeIds = $this->redis->lrange('user:' . $referralId . ':trades', 0, -1);
                foreach ($tradeIds as $tradeId) {
                    $trade = $this->trade_model->get(_numeric($tradeId));
                    if (!isset($trade->minor_referral_fee) && !isset($trade->major_referral_fee))
                        continue;
                    if ($referralId == $trade->minor_client) {
                        $fee      = $trade->major_referral_fee;
                        $currency = $trade->major_currency;
                    }
                    else if ($referralId == $trade->major_client) {
                        $fee      = $trade->minor_referral_fee;
                        $currency = $trade->minor_currency;
                    }

                    $data = array(
                        'date'     => $trade->_created,
                        'id'       => $userData->id,
                        'name'     => $userData->first_name . ' ' . $userData->last_name,
                        'amount'   => $fee,
                        'currency' => $currency
                    );

                    $this->entries[] = (object)$data;
                }
                
                $withdrawalIds = $this->redis->smembers('user:' . $referralId . ':withdrawals');
                foreach($withdrawalIds as $withdrawalId) {
                    $withdrawal = $this->withdrawal_model->getFull(_numeric($withdrawalId));

                    if(isset($withdrawal->details->referrerFee) && $withdrawal->status == "complete"){

                        $data = array(
                            'date'     => $withdrawal->_created,
                            'id'       => $userData->id,
                            'name'     => $userData->first_name . ' ' . $userData->last_name,
                            'amount'   => $withdrawal->details->referrerFee,
                            'currency' => $withdrawal->currency
                        );
                        
                        $this->entries[] = (object)$data;
                    }
                }
                

                usort($this->entries, function($a, $b){
                    return $a->date < $b->date;
                });
            }

            $this->caching_model->save($this->entries, ONE_DAY);
        }

        return count($this->entries);
    }
    
    public function getCountReferals($userId) {
        $referrals = $this->redis->lrange('user:' . $userId . ':referrals', 0, -1);
        return count($referrals);
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
    
    public function getReferrerUserInfo($userId) {
        $commission = $this->redis->get('trades:commission');
        $referrerCommission = $this->redis->hget('user:' . $userId, 'commission');

        if (!$referrerCommission) {
            $referrerCommission = $commission;
        }
        
        return array('userId' => $userId, 'comission' => $referrerCommission);
                
    }
}
