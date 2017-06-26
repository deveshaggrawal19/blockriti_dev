<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Api_model extends Redis_model {

    public function __construct() {
        parent::__construct();
    }

    public function isRateLimited($userId = null, $count = 30) {
        $ip = getIp();

        if ($this->redis->sismember('api:exclusion:ip', $ip))
            return false;

        if ($userId) {
            if ($this->redis->sismember('api:exclusion:user', $userId))
                return false;
        }

        $now = time();
        $key = 'api:ip:' . $ip;

        $length = $this->redis->llen($key);
        if ($length < $count)
            $this->redis->lpush($key, $now);
        else {
            $last = $this->redis->lindex($key, -1);
            if ($now - (int)$last < 60)
                return true;
            else {
                $this->redis->multi();
                $this->redis->lpush($key, $now);
                $this->redis->expire($key, 60);
                $this->redis->exec();
            }
        }

        $this->redis->ltrim($key, 0, $count);
        $this->redis->expire($key, 60);

        return false;
    }

    public function getRateLimiterData($type) {
        return $this->redis->smembers('api:exclusion:' . $type);
    }

    public function updateRateLimiterData($type, $data) {
        $this->lock();

        $this->redis->del('api:exclusion:' . $type);
        if (count($data))
            $this->redis->sadd('api:exclusion:' . $type, $data);

        $this->unlock();
    }

    public function getApiFromKey($key) {
        return $this->flatten($this->redis->hgetall('api:' . $key));
    }

    public function checkNonce($key, $nonce) {
        if (!$this->redis->sismember('api:' . $key . ':nonces', $nonce)) {
            $this->redis->sadd('api:' . $key . ':nonces', $nonce);

            return true;
        }

        return false;
    }

    public function countAPIs($userId) {
        return $this->redis->llen($userId . ':api');
    }

    public function getAPIs($userId) {
        $apis   = array();
        $apiIds = $this->redis->lrange('user:' . $userId . ':api', 0, -1);

        if ($apiIds) {
            foreach ($apiIds as $apiId) {
                $apis[$apiId] = $this->flatten($this->redis->hgetall('api:' . $apiId));
            }
        }

        return $apis;
    }

    public function addApi($userId, $data) {
        $id = random_string('alpha', 10);

        $data['client']   = $userId;
        $data['_created'] = $this->now;
        $data['_updated'] = $this->now;

        $this->redis->hmset('api:' . $id, $data);
        $this->redis->rpush('user:' . $userId . ':api', $id);

        return true;
    }

    public function deleteApi($userId, $code) {
        $apiData = $this->getApiFromKey($code);

        if ($apiData && $apiData->client == $userId) {
            $this->redis->del('api:' . $code);
            $this->redis->lrem('user:' . $userId . ':api', 0, $code);

            return true;
        }

        return false;
    }

    public function updateApi($userId, $code, $data) {
        $apiData = $this->getApiFromKey($code);

        if ($apiData && $apiData->client == $userId) {
            $data['_updated'] = $this->now;

            $this->redis->hmset('api:' . $code, $data);

            return true;
        }

        return false;
    }
}