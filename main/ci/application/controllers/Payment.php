<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Payment extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('redis');

        $this->load->helper('engine');

        $this->load->model('deposit_model');
        $this->load->model('admin_model');
        $this->load->model('logging_model');
        $this->load->model('user_model');
        $this->load->model('user_balance_model');
        $this->load->model('caching_model');
    }

    public function interac() {
        $this->logging_model->log($this->input->post(), 'interac');

        $transaction_id      = $this->input->post('transaction_id');
        $identifier          = $this->input->post('identifier');
        $item_code           = $this->input->post('item_code');
        $issuer_name         = $this->input->post('issuer_name');
        $issuer_confirmation = $this->input->post('issuer_confirmation');

        if ($identifier == 'HGS0284353nx842') {
            echo 'RECEIVED';

            $depositId = _numeric($item_code);
            $deposit   = $this->deposit_model->getFull($depositId);

            // Verify the payment
            $postFields = array(
                'transaction_id' => $transaction_id,
                'action'         => 'verify',
                'identifier'     => 'HGS0284353nx842',
                'vericode'       => 'VeriMQyM326lWS'
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.debitway.com/integration/index.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $result = curl_exec($ch);
            curl_close($ch);

            $callbackData = parseReturn($result);

            $result = $callbackData['result'];

            if ($result == 'success') {
                $gross              = $callbackData['gross'];
                $amount             = $callbackData['amount'];
                $net                = $callbackData['net'];
                $item_code          = $callbackData['item_code'];
                $transaction_status = $callbackData['transaction_status'];
                $transaction_type   = $callbackData['transaction_type'];
                $processing_rate    = $callbackData['processing_rate'];
                $transaction_date   = $callbackData['transaction_date'];
                $discount_fee       = $callbackData['discount_fee'];
                $additional_fee     = $callbackData['additional_fee'];

                if ($transaction_type == 'payment' && $transaction_status == 'approved') {
                    // Make sure this deposit has not already been processed
                    $depositId = _numeric($item_code);

                    if ($this->deposit_model->isPending($depositId)) {
                        // Save the details
                        $data = array(
                            'transaction_id'      => $transaction_id,
                            'gross'               => $gross,
                            'amount'              => $amount,
                            'net'                 => $net,
                            'transaction_type'    => $transaction_type,
                            'processing_rate'     => $processing_rate,
                            'transaction_date'    => $transaction_date,
                            'discount_fee'        => $discount_fee,
                            'additional_fee'      => $additional_fee,
                            'issuer_name'         => $issuer_name,
                            'issuer_confirmation' => $issuer_confirmation
                        );

                        $this->deposit_model->updateDetails($depositId, $data);

                        $this->deposit_model->receive($depositId, $transaction_id);
                    }
                }
            }
            else if ($result == 'failed') {
                $errors         = $callbackData['errors'];
                $errors_meaning = $callbackData['errors_meaning'];

                if ($this->deposit_model->isPending($depositId)) {
                    // Save the failed details
                    $data = array(
                        'transaction_id' => $transaction_id,
                        'errors'         => $errors,
                        'errors_meaning' => $errors_meaning
                    );

                    $this->deposit_model->updateDetails($depositId, $data);

                    $this->deposit_model->fail($depositId, $deposit->client);
                }
            }
        }

        return;
    }

    public function payza() {
        if (!$this->input->post() || $this->input->get())
            show_404();

        $this->logging_model->log($this->input->post(), 'payza');

        $method = 'pz';

        $token = $this->input->post('token');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://secure.payza.com/ipn2.ashx');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'token=' . $token);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = curl_exec($ch);
        curl_close($ch);

        if (strlen($response) > 0) {
            if (urldecode($response) == "INVALID TOKEN")
                return false;
            else {
                $response = urldecode($response);

                $aps  = explode("&", $response);
                $post = array();

                foreach ($aps as $ap) {
                    $ele           = explode("=", $ap);
                    $post[$ele[0]] = $ele[1];
                }

                $depositId = _numeric($post['ap_itemcode']);

                $deposit = $this->deposit_model->get($depositId);

                if (!$deposit || $deposit->method != $method)
                    return false;

                if ($deposit->status != 'pending') {
                    $data = array(
                        'error' => 'Status is ' . $deposit->status
                    );

                    $this->deposit_model->updateDetails($depositId, $data);

                    $this->deposit_model->fail($depositId, $deposit->client);

                    return false;
                }

                $this->load->config('merchant');

                if ($post['ap_merchant'] != $this->config->item('pz_merchant')) {
                    $data = array(
                        'error' => 'Wrong merchant ' . $post['ap_merchant']
                    );

                    $this->deposit_model->updateDetails($depositId, $data);

                    $this->deposit_model->fail($depositId, $deposit->client);

                    return false;
                }

                if ($post['ap_test'] == 1) {
                    $data = array(
                        'error' => 'Test mode is ON'
                    );

                    $this->deposit_model->updateDetails($depositId, $data);

                    $this->deposit_model->fail($depositId, $deposit->client);

                    return false;
                }

                $reference = $post['ap_referencenumber'];

                if ($this->deposit_model->referenceExists($method, $reference)) {
                    $data = array(
                        'error' => 'Reference ' . $reference . ' already processed'
                    );

                    $this->deposit_model->updateDetails($depositId, $data);

                    $this->deposit_model->fail($depositId, $deposit->client);

                    return false;
                }

                if (strcasecmp($post['ap_status'], 'Success') != 0) {
                    $data = array(
                        'error' => 'Payment Status is ' . $post['ap_status']
                    );

                    $this->deposit_model->updateDetails($depositId, $data);

                    $this->deposit_model->fail($depositId, $deposit->client);

                    return false;
                }

                // Is it the correct amount
                if ($deposit->amount != $post['ap_totalamount']) {
                    $data = array(
                        'error' => 'Amount ' . $post['ap_totalamount'] . ' different from ' . $deposit->amount
                    );

                    $this->deposit_model->updateDetails($depositId, $data);

                    $this->deposit_model->fail($depositId, $deposit->client);

                    return false;
                }

                if (strcasecmp($post['ap_currency'], strtoupper($deposit->currency)) != 0) {
                    $data = array(
                        'error' => 'Currency ' . $post['ap_currency'] . ' not ' . strtoupper($deposit->currency)
                    );

                    $this->deposit_model->updateDetails($depositId, $data);

                    $this->deposit_model->fail($depositId, $deposit->client);

                    return false;
                }

                $this->deposit_model->updateDetails($depositId, $post);
                $this->deposit_model->receive($depositId, $reference);
            }
        }
        else return false;
    }
    //todo admeris does it get used? - No remove - Removed but there probably is going to be issues
   
    public function success($method, $depositId = null) {
        $this->session->set_flashdata('success', 'We have received your ' . code2Name($method) . ' deposit');
        redirect('/account', 'refresh');
    }

    public function cancel($method, $depositId = null) {
        $this->session->set_flashdata('error', 'There was a problem with your ' . code2Name($method) . ' deposit. Please contact support');
        redirect('/deposit', 'refresh');
    }
}
