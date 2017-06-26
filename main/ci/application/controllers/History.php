<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class History extends MY_Controller {

    public function __construct() {
        parent::__construct();
        show_404();
        $this->load->model('deposit_model');
        $this->load->model('withdrawal_model');
        $this->load->model('exchange_model');
        $this->load->model('referral_model');
        $this->load->model('merchant_model');
        $this->load->model('redis_model');
    }

	public function index() {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_login_to_access_page'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        $data['isMerchant'] = $this->redis_model->keyExists('user:' . $this->userId . ':merchant-sales');

        $this->layout->setTitle('History')->view('user/history', $data);
    }

    public function trades($page = 1, $perPage = 30) {
        if ($this->user === 'guest')
            echo $this->layout->partialView('user/history/guest');
        else {
            $count = $this->trade_model->getCountForUser($this->userId);

            $data['count']   = $count;
            $data['entries'] = $this->trade_model->getSubsetForUser($page, $perPage);
            $data['pages']   = generateNewPagination('/history/trades', $count, $page, $perPage);

            echo $this->layout->partialView('user/history/trades', $data);
        }
    }

    public function fundings($page = 1, $perPage = 30) {
        if ($this->user === 'guest')
            echo $this->layout->partialView('user/history/guest');
        else {
            $count = $this->deposit_model->getCountForUser($this->userId);

            $data['count']   = $count;
            $data['entries'] = $this->deposit_model->getSubsetForUser($page);
            $data['pages']   = generateNewPagination('/history/fundings', $count, $page, $perPage);

            echo $this->layout->partialView('user/history/fundings', $data);
        }
    }

    public function withdrawals($page = 1, $perPage = 30) {
        if ($this->user === 'guest')
            echo $this->layout->partialView('user/history/guest');
        else {
            $count = $this->withdrawal_model->getCountForUser($this->userId);

            $data['count']   = $count;
            $data['entries'] = $this->withdrawal_model->getSubsetForUser($page);
            $data['pages']   = generateNewPagination('/history/withdrawals', $count, $page, $perPage);

            echo $this->layout->partialView('user/history/withdrawals', $data);
        }
    }

    public function exchanges($page = 1, $perPage = 30) {
        if ($this->user === 'guest')
            echo $this->layout->partialView('user/history/guest');
        else {
            $count = $this->exchange_model->getCount($this->userId);

            $data['count']   = $count;
            $data['entries'] = $this->exchange_model->getSubset($page);
            $data['pages']   = generateNewPagination('/history/exchanges', $count, $page, $perPage);

            echo $this->layout->partialView('user/history/exchanges', $data);
        }
    }

    public function referrals($page = 1, $perPage = 30) {
        if ($this->user === 'guest')
            echo $this->layout->partialView('user/history/guest');
        else {
            $count = $this->referral_model->getCount($this->userId);
            $countReferrals = $this->referral_model->getCountReferals($this->userId);
            
            $data['countReferrals'] = $countReferrals;
            $data['count']   = $count;
            $data['entries'] = $this->referral_model->getSubset($page);
            $data['pages']   = generateNewPagination('/history/referrals', $count, $page, $perPage);

            echo $this->layout->partialView('user/history/referrals', $data);
        }
    }

    public function merchant($page = 1, $perPage = 30) {
        if ($this->user === 'guest')
            echo $this->layout->partialView('user/history/guest');
        else {
            $count = $this->merchant_model->getCount($this->userId);

            $data['count']   = $count;
            $data['entries'] = $this->merchant_model->getSubset($page);
            $data['pages']   = generateNewPagination('/history/merchant', $count, $page, $perPage);

            echo $this->layout->partialView('user/history/merchant', $data);
        }
    }

    public function exportTrades() {
        if ($this->user === 'guest') show_404();

        $count   = $this->trade_model->getCountForUser($this->userId);
        $entries = $this->trade_model->getSubsetForUser(1, $count);

        $headers = array(
            'type'           => 'type',
            'major_currency' => 'major',
            'minor_currency' => 'minor',
            'amount'         => 'amount',
            'rate'           => 'rate',
            'value'          => 'value',
            'fee'            => 'fee',
            'total'          => 'total',
            '_created'       => 'timestamp',
            'dummy'          => 'datetime'
        );

        $history = implode(',', array_values($headers));

        foreach ($entries as $fields) {
            $data = array();
            foreach ($headers as $key=>$value)
                $data[$value] = isset($fields->{$key}) ? $fields->{$key} : '';

            $data['timestamp'] /= 1000;
            $data['datetime']   = date('m/d/Y H:i:s', $data['timestamp']);

            $history .= "\r\n" . implode(',', $data);
        }

        $name = 'tex-trades-' . date('mdY-Hi') . '.csv';

        $this->_exportFile($history, $name);
    }

    public function exportDeposits() {
        if ($this->user === 'guest') show_404();

        $count   = $this->deposit_model->getCountForUser($this->userId);
        $entries = $this->deposit_model->getSubsetForUser(1, $count);

        $headers = array(
            'method'   => 'method',
            'currency' => 'currency',
            'gross'    => 'gross',
            'fee'      => 'fee',
            'amount'   => 'net amount',
            '_created' => 'datetime'
        );

        $fundings = implode(',', array_values($headers));

        foreach ($entries as $fields) {
            $data = array();
            foreach ($headers as $key=>$value)
                $data[$value] = isset($fields->{$key}) ? $fields->{$key} : '';

            $data['method']   = code2Name($data['method']);
            $data['datetime'] = date('m/d/Y H:i:s', $data['datetime'] / 1000);

            $fundings .= "\r\n" . implode(',', $data);
        }

        $name = 'tex-deposits-' . date('mdY-Hi') . '.csv';

        $this->_exportFile($fundings, $name);
    }

    public function exportWithdrawals() {
        if ($this->user === 'guest') show_404();

        $count   = $this->withdrawal_model->getCountForUser($this->userId);
        $entries = $this->withdrawal_model->getSubsetForUser(1, $count);

        $headers = array(
            'method'   => 'method',
            'currency' => 'currency',
            'amount'   => 'amount',
            '_created' => 'datetime'
        );

        $withdrawals = implode(',', array_values($headers));

        foreach ($entries as $fields) {
            $data = array();
            foreach ($headers as $key=>$value)
                $data[$value] = isset($fields->{$key}) ? $fields->{$key} : '';

            $data['method']   = code2Name($data['method']);
            $data['datetime'] = date('m/d/Y H:i:s', $data['datetime'] / 1000);

            $withdrawals .= "\r\n" . implode(',', $data);
        }

        $name = 'tex-withdrawals-' . date('mdY-Hi') . '.csv';

        $this->_exportFile($withdrawals, $name);
    }

    public function exportExchanges() {
        if ($this->user === 'guest') show_404();

        $count   = $this->exchange_model->getCount($this->userId);
        $entries = $this->exchange_model->getSubset(1, $count);

        $headers = array(
            'from'   => 'from',
            'amount' => 'in',
            'to'     => 'to',
            'value'  => 'out',
            'rate'   => 'rate',
            'date'   => 'datetime'
        );

        $exchanges = implode(',', array_values($headers));

        foreach ($entries as $fields) {
            $data = array();
            foreach ($headers as $key=>$value)
                $data[$value] = isset($fields->{$key}) ? $fields->{$key} : '';

            $data['datetime'] = date('m/d/Y H:i:s', $data['datetime'] / 1000);

            $exchanges .= "\r\n" . implode(',', $data);
        }

        $name = 'tex-exchanges-' . date('mdY-Hi') . '.csv';

        $this->_exportFile($exchanges, $name);
    }

    public function exportReferrals() {
        if ($this->user === 'guest') show_404();

        $count   = $this->referral_model->getCount($this->userId);
        $entries = $this->referral_model->getSubset(1, $count);

        $headers = array(
            'name'     => 'name',
            'currency' => 'currency',
            'amount'   => 'amount',
            '_created' => 'datetime'
        );

        $referrals = implode(',', array_values($headers));

        foreach ($entries as $fields) {
            $data = array();
            foreach ($headers as $key=>$value)
                $data[$value] = isset($fields->{$key}) ? $fields->{$key} : '';

            $data['datetime'] = date('m/d/Y H:i:s', $data['datetime'] / 1000);

            $referrals .= "\r\n" . implode(',', $data);
        }

        $name = 'tex-referrals-' . date('mdY-Hi') . '.csv';

        $this->_exportFile($referrals, $name);
    }

    private function _exportFile($data, $filename) {
        $this->load->helper('download');

        if(ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');

        header("Content-type: application/octet-stream");
        if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
            header('Content-Type: application/force-download'); //IE HEADER

        header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . strlen($data));
        header('Accept-Ranges: bytes');

        header("Cache-control: no-cache, pre-check=0, post-check=0");
        header("Cache-control: private");
        header("Pragma: private");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        echo $data;
    }
}