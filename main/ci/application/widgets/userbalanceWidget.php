<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class userbalance extends Widget {

    function run($currency, $btype = '') {
        if ($this->user === 'guest') return;

        $types = array(
            'available',
            'locked',
            'pending_deposit',
            'pending_withdrawal',
        );

        $currency = strtolower(substr($currency, 0, 3));
        if ($btype == 'all') {
            $data['types']    = $types;
            $data['user']     = $this->user;
            $data['currency'] = $currency;

            $this->render('userbalance', $data);
        }
        else {
            $type = '';
            if (in_array($btype, $types)) $type = $btype;
            if (!empty($type)) $type = '_' . $type;
            echo displayCurrency($currency, $this->user->balances->{$currency . $type});
        }
    }
}