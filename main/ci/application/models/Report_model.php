<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Report_model extends Redis_model {

    private $exclusion;

    public function __construct() {
        parent::__construct();

        $this->exclusion = array('13', '26', '41', '6585', '6805', '7661', '7668');

        $this->load->model('deposit_model');
        $this->load->model('withdrawal_model');
    }

    public function fiatDeposits() {
        $depositIds = $this->redis->smembers('deposits:complete');

        $data = array();
        foreach ($depositIds as $depositId) {
            $depositId = _numeric($depositId);
            $deposit   = $this->deposit->get($depositId);

            if ($deposit && $deposit->currency != 'btc' && in_array($deposit->client, $this->exclusion) === FALSE) {
                $userData = $this->flatten($this->redis->hgetall('user:' . $deposit->client));

                $data[] = array(
                    'date'     => date('m/d/Y H:i:s', $deposit->_updated / 1000),
                    'client'   => $userData->first_name . ' ' . $userData->last_name,
                    'amount'   => $deposit->amount,
                    'currency' => $deposit->currency,
                    'method'   => code2Name($deposit->method)
                );
            }
        }

        return $data;
    }

    public function fiatWithdrawals() {
        $withdrawalIds = $this->redis->smembers('withdrawals:complete');

        $data = array();
        foreach ($withdrawalIds as $withdrawalId) {
            $withdrawalId = _numeric($withdrawalId);
            $withdrawal   = $this->withdrawal->get($withdrawalId);

            if ($withdrawal && $withdrawal->currency != 'btc' && in_array($withdrawal->client, $this->exclusion) === FALSE) {
                $userData = $this->flatten($this->redis->hgetall('user:' . $withdrawal->client));

                $data[] = array(
                    'date'     => date('m/d/Y H:i:s', $withdrawal->_updated / 1000),
                    'client'   => $userData->first_name . ' ' . $userData->last_name,
                    'amount'   => $withdrawal->amount,
                    'currency' => $withdrawal->currency,
                    'method'   => code2Name($withdrawal->method)
                );
            }
        }

        return $data;
    }
}