<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class DepositNavigation extends Widget {
    function run() {
        $data = array(
            'route' => $this->router->uri->uri_string
        );

        $this->render('depositNavigation', $data);
    }
}