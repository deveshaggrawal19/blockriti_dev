<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Redis {

    private $_connection;

    public function __construct($params = array()) {
        $CI =& get_instance();
        $CI->load->config('redis', TRUE);

        $this->_connection = new Predis\Client($CI->config->item('redis'));
        $this->_connection->connect();
    }

    public function __call($method, $arguments) {
        return call_user_func_array(array($this->_connection, $method), $arguments);
    }

    function __destruct() {
        if ($this->_connection)
            $this->_connection->disconnect();
    }
}