<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Caching_model extends Redis_model {

    private $enabled;
    private $key;
    private $prefix = 'cache:';

    public function __construct() {
        parent::__construct();

        $this->enabled = $this->redis->get('caching:status') == 'enabled';
    }

    public function get($key) {
        $this->key = $this->prefix . $key;

        if ($this->enabled) {
            if ($this->redis->exists($this->key))
                return json_decode($this->redis->get($this->key));
        }

        return null;
    }

    public function save($data, $expiry = null) {
        if ($this->enabled) {
            $this->redis->set($this->key, json_encode($data));

            if ($expiry)
                $this->redis->expire($this->key, $expiry); // set an expiry date just in case
        }
    }

    public function delete($filter = '*') {
        $lookup = $this->prefix . $filter;

        if (strpos($lookup, '*') === FALSE)
            $this->redis->del($lookup);
        else {
            $keys = $this->redis->keys($lookup);

            foreach ($keys as $key)
                $this->redis->del($key);
        }
    }

    public function purge() {
        $this->delete('*');
    }

    public function listAll() {
        $list = $this->redis->keys($this->prefix . '*');

        $result = array();
        foreach ($list as $key) {
            $result[] = array(
                'key' => $key,
                'ttl' => $this->redis->ttl($key)
            );
        }

        return $result;
    }
}