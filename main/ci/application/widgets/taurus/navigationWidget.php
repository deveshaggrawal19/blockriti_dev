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

        if ($user !== 'guest')
            $data['avatar'] = $this->gravatar->get_gravatar($data['user']->email, null, 20, null, true);

        $this->render('navigation', $data);
    }
}