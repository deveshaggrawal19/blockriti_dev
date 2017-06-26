<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Permissions_model extends Redis_model {

    public function __construct() {
        parent::__construct();
    }

    public function set($what, $status) {
        $this->redis->set('permission:' . $what, $status);
    }

    public function get($what) {
        return $this->redis->get('permission:' . $what);
    }
}