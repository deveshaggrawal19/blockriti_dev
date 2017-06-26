<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Logging_model extends Redis_model {

    private $entries;

    public function __construct() {
        parent::__construct();
    }

    public function get($id) {
        $id   = _numeric($id);
        $log = $this->flatten($this->redis->hgetall('log:' . $id));

        if ($log)
            $log->id = $id;

        return $log;
    }

    public function log($what, $prefix = 'debug', $userId = null) {
        if (empty($prefix)) return;
        $id = $this->newId('log');

        if (is_null($userId) && isset($this->userId))
            $userId = $this->userId;

        $prefix = strtolower($prefix);
        $key = 'logset:' . $prefix;

        $created = $this->now;
        $data = array(
            '_id'      => $id,
            '_created' => $created,
            'client'   => $userId,
            'message'  => json_encode($what)
        );

        $this->redis->hmset($id, $data);

        $this->redis->zadd($key, $created, $id);
        $this->redis->zadd('logset:all', $created, $id);
        $this->redis->sadd('logsets', $prefix);

        $this->caching_model->delete($key);
        $this->caching_model->delete('logset:all');
    }

    public function getCount($prefix = 'debug') {
        $this->entries = $this->caching_model->get('logset:' . $prefix);
        if ($this->entries === null) {
            $this->entries = $this->redis->zrevrange('logset:' . $prefix, 0, -1);

            $this->caching_model->save($this->entries, ONE_DAY);
        }

        return count($this->entries);
    }

    public function getAllPrefixes() {
        return $this->redis->smembers('logsets');
    }

    public function getSubset($page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage;
        $result = array();
        for ($i = $start; $i < $end; $i++) {
            if (!isset($this->entries[$i])) break;

            $data = $this->entries[$i];
            $log = $this->get($data);

            if (!empty($log->client))
                $log->user = $this->user_model->getUser($log->client);

            $result[] = $log;
        }

        return $result;
    }
}