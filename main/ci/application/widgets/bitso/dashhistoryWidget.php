<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class dashhistory extends Widget
{
    function run()
    {
        $this->load->model('deposit_model');
        $this->load->model('withdrawal_model');
        $this->load->model('exchange_model');
        $this->load->model('referral_model');

        $count = $this->trade_model->getCountForUser($this->user->id);
        $trades = $this->trade_model->getSubsetForUser(1, 10);

        $trades = $this->addOrderingDate($trades, "_created","T");

        $count = $this->deposit_model->getCountForUser($this->user->id);
        $deposits = $this->deposit_model->getSubsetForUser(1, 10);
        $deposits = $this->addOrderingDate($deposits,"_updated","D");

        $count = $this->withdrawal_model->getCountForUser($this->user->id);
        $withdrawals = $this->withdrawal_model->getSubsetForUser(1);
        $withdrawals = $this->addOrderingDate($withdrawals,"_updated","W");

        $count = $this->referral_model->getCount($this->user->id);
        $refs = $this->referral_model->getSubset(1);
        $refs = $this->addOrderingDate($refs,"date","R");

        $events = $trades + $deposits + $withdrawals + $refs;

        ksort($events);

        $events = array_reverse($events, true);

        $events = array_slice($events, 0, 10, true);

        $data['events'] = $events;

        $data['user'] = $this->user;

        $this->render('dashhistory',$data);
    }

    private function addOrderingDate($array, $field, $type) {
        $new = array();$idx=0;
        foreach ($array as $item) {
            $ntype=$type;
            if (isset($item->method) && $item->method=='rp') {
                if ($type=='D') $ntype='RD';
                else if ($type=='W') $ntype='RW';
            } else {
                if (isset($item->currency) && $item->currency=='btc') {
                    if ($type=='D') $ntype='BD';
                    else if ($type=='W') $ntype='BW';
                }
            }
            $od = $item->{$field};
            $item->etype = $ntype;
            $new[$od.'-'.$idx] = $item;
            $idx++;
        }
        return $new;
    }
}