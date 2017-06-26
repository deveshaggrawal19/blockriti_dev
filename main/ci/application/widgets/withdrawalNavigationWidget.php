<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class WithdrawalNavigation extends Widget {
    function run() {
        $data = array(
            'route' => $this->router->uri->uri_string
        );

        $this->render('withdrawalNavigation', $data);
    }
}