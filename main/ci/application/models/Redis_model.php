<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Redis_model extends CI_Model {

    protected $_error;
    protected $now;

    public function __construct() {
        parent::__construct();

        $this->now = milliseconds();
    }

    protected function newId($type) {
        return $type . ':' . $this->redis->incr('id_' . strtolower($type));
    }

    protected function newRandomId($type) {
        return $type . ':' . $this->redis->incrby('id_' . strtolower($type), mt_rand(2, 10));
    }

    protected function flatten($data) {
        // This stays as it is for now as the data returned from Predis is
        // an associative array whilst we only used objects thus far!
        if ($data)
            return (object)$data;

        return false;
    }

    public function lock($single = '') {
        // auto blocking reverse lock
        $key = 'process_lock' . ($single ? '_' . $single : '');
        $this->redis->blpop($key, 5);
    }

    public function unlock($single = '') {
        // set process lock free
        $key = 'process_lock' . ($single ? '_' . $single : '');
        $this->redis->lpush($key, 'x');
    }

    public function keyExists($key) {
        return $this->redis->exists($key) == 1;
    }
}