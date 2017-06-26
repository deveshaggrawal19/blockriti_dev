<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Admin_model extends Redis_model {

    public function __construct() {
        parent::__construct();
    }

    public function getUsersOnline($timeout) {
        $sessions = $this->redis->keys('session:*');

        $offset = $this->now - ($timeout * 60000);

        $data = array();
        foreach ($sessions as $sessionKey) {
            $session = $this->flatten($this->redis->hgetall($sessionKey));
            if ($session && $session->time > $offset) {
                $session->user = $this->user_model->getUser($session->client);
                $data[] = $session;
            }
        }

        return $data;
    }

    public function getBlockchainData($key) {
        return $this->redis->get('blockchain:' . $key);
    }

    public function setBlockchainData($key, $value) {
        return $this->redis->set('blockchain:' . $key, $value);
    }

    public function getBitcoindData($key) {
        return $this->redis->get('bitcoind:' . $key);
    }

    public function setBitcoindData($key, $value) {
        return $this->redis->set('bitcoind:' . $key, $value);
    }
    
    public function getWallet($key) {
        return $this->redis->get('hotwallet:'.$key);
    }
    
    public function setWallet($key, $value) {
        return $this->redis->set('hotwallet:'.$key, $value);
    }
    
    public function getFeeData($key) {
        //$this->redis->set('fee_comission:status', "Hello");
        $status = $this->redis->get('fee_comission:' . $key);
        if(!$status) {
            return "disabled";
        }
        return $status;
    }

    public function setFeeData($key, $value) {
        return $this->redis->set('fee_comission:' . $key, $value);
    }
    
    public function getRenovationData($key) {
        //$this->redis->set('fee_comission:status', "Hello");
        $status = $this->redis->get('renovation_status:' . $key);
        if(!$status) {
            return "disabled";
        }
        return $status;
    }

    public function setRenovationData($key, $value) {
        return $this->redis->set('renovation_status:' . $key, $value);
    }
    
    public function getBotStatus($key) {
        $status = $this->redis->get('bot_status:' . $key);
        if(!$status) {
            return "disabled";
        }
        return $status;
    }
    //TODO INSART ADDED BOT CODE
    public function setBotStatus($key, $value) {
        return $this->redis->set('bot_status:' . $key, $value);
    }
    
    public function getBotLimit($key) {
        $status = $this->redis->get('bot_status:' . $key);
        if(!$status) {
            return "disabled";
        }
        return $status;
    }
    
    public function setBotLimit($key, $value) {
        return $this->redis->set('bot_status:' . $key, $value);
    }
    
    public function getBotMaxValue($key) {
        $status = $this->redis->get('bot_status:' . $key);
        if(!$status) {
            return "disabled";
        }
        return $status;
    }
    
    public function setBotMaxValue($key, $value) {
        return $this->redis->set('bot_status:' . $key, $value);
    }
    
    public function getBitcoinDepositData($key) {
        $status = $this->redis->get('deposit_status:'.$key);
        if(!$status) {
            return "enabled";
        }
        return $status;
    }
    
    public function setBitcoinDepositData($key, $value) {
        return $this->redis->set('deposit_status:' . $key, $value);
    }
    
    public function getBitcoinWithdrawalsData($key) {
        $status = $this->redis->get('withdrawals_status:'.$key);
        if(!$status) {
            var_dump("ENABLED");
            return "enabled";
        }
        return $status;
    }
    
    public function setBitcoinWithdrawalsData($key, $value) {
        return $this->redis->set('withdrawals_status:' . $key, $value);
    }

    public function disable_twofa($userId) {
        $this->load->library('Protectimus');
        $api = $this->protectimus->getApi();
        
        $user = $this->user_model->getUser($userId);
        
        $data = array(
            'twofa_status' => '0',
            'twofa_secret' => '',
            'twofa_reset'  => ''
        );
        
        if($user->twofa_type != '2fauth') {
            $res = $api->unassignTokenFromResource($this->protectimus->getResourceId(),
                                                                    null,$user->token_id);
            $res = $api->deleteToken($user->token_id);
        }

        $this->redis->hmset('user:' . $userId, $data);
    }

    public function userInteracPermission($userId, $status) {
        if ($status == 'ban')
            $this->redis->sadd('interac:banned', $userId);
        else $this->redis->srem('interac:banned', $userId);
    }

    public function getKeys($filter) {
        $data = $this->redis->keys($filter);

        usort($data, function($a, $b) use ($filter) {
            $_filter = str_replace('*', '', $filter);
            $timeA = str_replace($_filter, '', $a);
            $timeB = str_replace($_filter, '', $b);

            return bccomp($timeB, $timeA);
        });

        return $data;
    }

    public function getKey($key) {
        return $this->redis->get($key);
    }

    public function getFees($which) {
        $month     = date('m');
        $lastMonth = str_pad($month - 1, 2, '0', STR_PAD_LEFT);

        return array(
            'total'     => $this->flatten($this->redis->hgetall($which . ':fees:total')),
            'month'     => $this->flatten($this->redis->hgetall($which . ':fees:month:' . $month)),
            'lastMonth' => $this->flatten($this->redis->hgetall($which . ':fees:month:' . $lastMonth))
        );
    }
    
    public function getAmounts($which) {
        $month     = date('m');
        $lastMonth = str_pad($month - 1, 2, '0', STR_PAD_LEFT);

        return array(
            'total'     => $this->flatten($this->redis->hgetall($which . ':amount:total')),
            'month'     => $this->flatten($this->redis->hgetall($which . ':amount:month:' . $month)),
            'lastMonth' => $this->flatten($this->redis->hgetall($which . ':amount:month:' . $lastMonth))
        );
    }
}