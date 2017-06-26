<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Report extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('report_model');

        $this->load->helper('download');
    }

    private function _displayOutput($name, $data) {
        if(ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');

        header("Content-type: application/octet-stream");
        if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
            header('Content-Type: application/force-download'); //IE HEADER

        header('Content-Disposition: attachment; filename="'.basename($name).'"');
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . strlen($data));
        header('Accept-Ranges: bytes');

        header("Cache-control: no-cache, pre-check=0, post-check=0");
        header("Cache-control: private");
        header("Pragma: private");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        echo $data;
    }

    public function exportDeposits() {
        $headers = array(
            'date'     => 'date',
            'client'   => 'client',
            'amount'   => 'amount',
            'currency' => 'currency',
            'method'   => 'method'
        );

        $history = implode(',', array_values($headers));

        $entries = $this->report_model->fiatDeposits();

        foreach ($entries as $fields) {
            $data = array();
            foreach ($headers as $key=>$value)
                $data[$value] = isset($fields[$key]) ? $fields[$key] : '';

            $history .= "\r\n" . implode(',', $data);
        }

        $this->_displayOutput('tex-deposits-' . date('mdY-Hi') . '.csv', $history);
    }

    public function exportWithdrawals() {
        $headers = array(
            'date'     => 'date',
            'client'   => 'client',
            'amount'   => 'amount',
            'currency' => 'currency',
            'method'   => 'method'
        );

        $history = implode(',', array_values($headers));

        $entries = $this->report_model->fiatWithdrawals();

        foreach ($entries as $fields) {
            $data = array();
            foreach ($headers as $key=>$value)
                $data[$value] = isset($fields[$key]) ? $fields[$key] : '';

            $history .= "\r\n" . implode(',', $data);
        }

        $this->_displayOutput('tex-withdrawals-' . date('mdY-Hi') . '.csv', $history);
    }
}