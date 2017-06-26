<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Navigation extends Widget {
    function run() {
        $this->load->model('trade_model');

        $fiat       = $this->meta_model->getFiatCurrencies();
        $currencies = $this->meta_model->getAllCurrencies();
        $user       = $this->user;

        // Showing all the bitcoin prices in the navigation bar
        $data['lastPrices'] = array();
        foreach ($fiat as $currency)
            $data['lastPrices'][$currency] = $this->trade_model->getLastTradePrice('btc_' . $currency);

        $data['user']       = $user;
        $data['currencies'] = $currencies;
        
        $books = $this->meta_model->getBooks();

        $args = func_get_args();
        if (!$args) {
            // Check the route
            $uriString = $this->router->uri->uri_string;
            foreach ($books as $bookId=>$currencies) {
                if ((strpos($uriString, $currencies[0]) !== FALSE && strpos($uriString, $currencies[1]) !== FALSE) || (strpos($uriString, $bookId) !== FALSE)) {
                    $book = $bookId;
                    break;
                }
            }

            if (!isset($book)) {
                if (!$major = $this->session->userdata('major')) {
                    $major = $this->config->item('default_major');
                }

                if (!$minor = $this->session->userdata('minor')) {
                    $minor = $this->config->item('default_minor');
                }

                $book = $major . '_' . $minor;
            }
        }
        else $book = $args[0];
        
        $data['volume']    = $this->trade_model->getRollingVolume($book);
        //$data['volume']     = $this->trade_model->getUserVolume($user->id);

        if ($user !== 'guest')
            $data['avatar'] = $this->gravatar->get_gravatar($data['user']->email, null, 20, null, true);

        $this->render('navigation', $data);
    }
}