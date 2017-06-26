<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Email_queue_model extends Redis_model {

    public $email;
    public $subject;
    public $message;

    public function __construct() {
        parent::__construct();
    }

    public function store() {
        $u = get_object_vars($this);

        unset($u['_error']);

        $this->redis->rpush('email_queue', json_encode($u));

        return true;
    }

    public function pop() {
        $data = $this->redis->lpop('email_queue');
        if (!$data)
            return false;

        return json_decode($data);
    }
}