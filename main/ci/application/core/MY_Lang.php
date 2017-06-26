<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class MY_Lang extends CI_Lang {

    function __construct() {
        parent::__construct();
    }

    public function line() {
        $args = func_get_args();

        if (count($args)) {
            $line = array_shift($args);

            $line = ($line == '' OR !isset($this->language[$line])) ? FALSE : $this->language[$line];

            if ($line && $args)
                $line = vsprintf($line, $args);

            return $line;
        }

        return false;
    }
}