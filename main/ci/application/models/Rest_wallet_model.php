<?php

class Rest_wallet_model extends Redis_model
{
    public function __construct() {
        parent::__construct();
        $this->_obj_Memcache  = new Memcache();
    }

    public function getTradesData($userId)
    {
        if (empty($this->_obj_Memcache->get('user:' . $userId. ':trades') ) === true) {
            $count = $this->trade_model->getCountForUser($userId);
            $data['count']   = $count;
            $data['entries'] = $this->trade_model->getSubsetForUser();
            $this->_obj_Memcache->set('user:' . $userId. ':trades', $data);
        }
        else
        {
            $data = $this->_obj_Memcache->get('user:' . $userId. ':trades');
        }

        return $data;
    }

    public function getFundingData($userId)
    {
        if (empty($this->_obj_Memcache->get('user:' . $userId. ':deposits') ) === true) {
            $count = $this->deposit_model->getCountForUser($userId, 'all');
            $data['count'] = $count;
            $data['entries'] = $this->deposit_model->getSubsetForUser();
            $this->_obj_Memcache->set('user:' . $userId. ':deposits', $data);
        }
        else
        {
            $data = $this->_obj_Memcache->get('user:' . $userId. ':deposits');
        }

        return $data;
    }

    public function getWithdrawalData($userId)
    {
        if (empty($this->_obj_Memcache->get('user:' . $userId. ':withdrawals') ) === true) {
            $count = $this->withdrawal_model->getCountForUser($userId, 'all');
            $data['count'] = $count;
            $data['entries'] = $this->withdrawal_model->getSubsetForUser();
            $this->_obj_Memcache->set('user:' . $userId. ':withdrawals', $data);
        }
        else
        {
            $data = $this->_obj_Memcache->get('user:' . $userId. ':withdrawals');
        }

        return $data;
    }

    public function getReferalData($userId)
    {
        if (empty($this->_obj_Memcache->get('user:' . $userId. ':referrals') ) === true) {
            $count = $this->referral_model->getCount($userId);
            $countReferrals = $this->referral_model->getCountReferals($userId);
            $data['countReferrals'] = $countReferrals;
            $data['count'] = $count;
            $data['entries'] = $this->referral_model->getSubset();
            $this->_obj_Memcache->set('user:' . $userId. ':referrals', $data);
        }
        else
        {
            $data = $this->_obj_Memcache->get('user:' . $userId. ':referrals');
        }

        return $data;
    }
}