<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Recaptcha extends Widget {
    function run() {
        $allowed = array(
            'main:contact',
            'user:register'
        );

        $key = $this->router->class . ':' . $this->router->method;
        if (in_array($key, $allowed) === FALSE) return;

        echo '<script src="https://www.google.com/recaptcha/api.js"></script>';
    }
}