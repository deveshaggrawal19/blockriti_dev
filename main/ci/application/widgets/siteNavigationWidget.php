<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SiteNavigation extends Widget {

    function run() {
        $class = $this->router->class;
        $method = $this->router->method;

        switch (true) {
            case $class == 'main' && $method == 'index':
                $loc = 'home';
                break;

            case $class == 'main' && $method == 'about':
            case $class == 'main' && $method == 'terms':
            case $class == 'main' && $method == 'privacy':
                $loc = 'about';
                break;

            case $class == 'main' && $method == 'merchant_info':
            case $class == 'main' && $method == 'merchant_setup':
                $loc = 'merchant';
                break;

            case $class == 'main' && $method == 'contact':
                $loc = 'contact';
                break;

            case $class == 'main' && $method == 'atms':
                $loc = 'atms';
                break;

            case $class == 'main' && $method == 'local':
                $loc = 'local';
                break;

            case $class == 'main' && $method == 'faq':
                $loc = 'faq';
                break;

            case $class == 'trade' && $method == 'dash':
            case $class == 'user' && $method == 'verify':
            case $class == 'user' && $method == 'settings':
                $loc = 'dashboard';
                break;

            case $class == 'trade' && $method == 'index':
            case $class == 'trade' && $method == 'instant':
            case $class == 'trade' && $method == 'limit':
                $loc = 'trade';
                break;

            case $class == 'trade' && $method == 'orderbook':
                $loc = 'orderbook';
                break;

            case $class == 'trade' && $method == 'market':
                $loc = 'market';
                break;

            case $class == 'fund':
                $loc = 'deposit';
                break;

            case $class == 'withdrawal':
                $loc = 'withdraw';
                break;

            default:
                $loc = '';
        }

        $data['loc']  = $loc;
        $data['user'] = $this->user;

        $this->render('siteNavigation', $data);
    }
}