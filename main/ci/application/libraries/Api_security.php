<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

define('SALT_FILE', FCPATH . md5('salts') . '.json');

class Api_security {
    private $salts;

    public function __construct() {
        $this->salts = $this->getSalts();
    }

    public function getSalt($ref) {
        $x = unpack('C*', $ref);

        return $this->salts[array_pop($x)];
    }

    private function getSalts() {
        return json_decode(file_get_contents(SALT_FILE));
    }

    public function hash() {
        $message = '';
        $tohash  = func_get_args();
        foreach ($tohash as $v) $message .= $v . $this->getSalt($v);

        return hash('sha256', $message);
    }

    public function check() {
        $args = func_get_args();

        return $args[0] === call_user_func_array(array($this, 'hash'), array_slice($args, 1));
    }

    public function chain($current, $previous) {
        return hash('sha256', $current . $previous);
    }
}