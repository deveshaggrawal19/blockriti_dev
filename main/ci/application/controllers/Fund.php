<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fund extends MY_Controller {

    public function __construct() {
        parent::__construct();
        show_404();
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_login_before_funding'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        $this->load->model('deposit_model');
    }

	public function index() {
       if($this->session->flashdata('error') == ''){
        $data['errors'] = $this->checkBitcoinDepositData();
       }
	   
	  
        $page = $this->input->get('page');
        if (!$page)
            $page = 1;
        //$this->bitcoin_model->checkBitcoind(true);
        $count = $this->deposit_model->getCountForUser($this->userId, 'all');

        $data['items'] = $this->deposit_model->getSubsetForUser($page, 20);
        $data['pages'] = generateNewPagination('/deposit?page=%d', $count, $page, 20);
        

        $this->layout->setTitle(_l('account_funding'))->view('deposit/index', $data);
    }

    public function bankwire() {
        if ($this->user->verified == 0) {
            $this->session->set_flashdata('error', _l('fund_unverified_user', _l('bank_wire')));
            redirect('/deposit');
        }

        $data = array(
            'user'       => $this->user,
            'currencies' => $this->meta_model->getFiatCurrencies()
        );

        $this->layout->setTitle(_l('heading_bank_wire'))->view('deposit/method/bankwire', $data);
    }

    public function inperson() {
        if ($this->user->verified == 0) {
            $this->session->set_flashdata('error', _l('fund_unverified_user', _l('cash-in-person')));
            redirect('/deposit');
        }
        
        $this->layout->setTitle('Account Funding - In Person')->view('deposit/method/inperson');
    }
//todo do we use payza - remove - removed payza
   
    public function interac($currency = 'cad') {
        
        //$this->deposit_model->getPending('io');

        if ($this->user->verified == 0) {
            $this->session->set_flashdata('error', _l('fund_unverified_user', 'INTERAC<sup>&reg;</sup> Online'));
            redirect('/fund/' . $currency);
        }

        $interacBan = $this->deposit_model->userBannedFromInterac($this->userId);
        if (!$this->deposit_model->userBannedFromInterac($this->userId)) {
            $this->session->set_flashdata('error', _l('not_authorized_to_use', 'INTERAC<sup>&reg;</sup> Online'));

            redirect('/fund/' . $currency);
        }
        

        // Count how many interac deposits were made by this user:
        $max = 2000;
        if($this->user->interac_limit && $this->user->interac_limit != ''){
            $max = $this->user->interac_limit;
        } else {
            $count = $this->deposit_model->getCountForUser($this->userId, 'complete', 'io');
            if ($count < 3)
                $max = 250;
            else if ($count < 6)
                $max = 500;
        }

        $allowed = array(
            'cad' => array('min' => 50, 'max' => $max)
        );

        if (!isset($allowed[$currency])) {
            $this->session->set_flashdata('error', _l('cannot_fund_with_currency'));
            redirect('/fund/' . $currency);
        }

        $min = $allowed[$currency]['min'];
        $max = $allowed[$currency]['max'];

        $userDetails = $this->user_model->getDetails($this->userId);

        if ($this->input->post() && $userDetails) {
            $this->form_validation->set_message('less_than', 'Exceeds your Interac Funding Limit of ' . displayCurrency('cad', $allowed['cad']['max']));

            $this->form_validation->set_rules('amount', _l('l_amount'), 'required|valid_currency_format[' . $currency . ']|greater_than[' . $min . ']|less_than[' . $max . ']');

            if ($this->form_validation->run()) {
                $data = array(
                    'client'   => $this->userId,
                    'method'   => 'io',
                    'currency' => $currency,
                    'amount'   => abs($this->input->post('amount'))
                );
                
                $deposit = $this->deposit_model->add($data);
                if ($deposit) {
                    $this->load->config('creds_admeris', TRUE);
                    $this->load->config('creds_psigate', TRUE);
                    $userClient = $this->user_model->getUser($deposit->client);
                    if(!isset($userClient->automatic_interact) || $userClient->automatic_interact != 1){
                        $this->deposit_model->toVerify(_numeric($deposit->_id), $this->userId);
                    }

                    $data['user']        = $this->user;
                    $data['userDetails'] = $userDetails;
                    $data['depositId']   = $this->deposit_model->_id;
                    $data['config']      = $this->config->item('creds_psigate');//$this->config->item('creds_admeris');
                    
                    $this->layout->setTitle('Account Funding - Interac Online')->view('deposit/method/interac_complete', $data);

                    return;
                }
                else $data['errors'] = _l('issue_with_funding');
            }
        }

        $data['amount'] = array(
            'name'        => 'amount',
            'id'          => 'amount',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => _l('p_amount'),
            'value'       => $this->form_validation->set_value('amount'),
            'autofocus'   => 'autofocus'
        );

        $data['limits'] = $allowed;

        $data['currency'] = $currency;

        $data['userDetails'] = $userDetails;
        
        $data['interacBan'] = $interacBan;

        $this->layout->setTitle('Account Funding - Interac Online')->view('deposit/method/interac', $data);
    }

    /* NON-FIAT Methods */
    public function bitcoin() {

        $data = array(
            'address' => $this->bitcoin_model->getFromBitcoind($this->userId)
        );
        
        $data['errors'] = $this->checkBitcoinDepositData();

        $this->layout->setTitle(_l('heading_bitcoin'))->view('deposit/method/bitcoin', $data);
    }

    public function details($depositId) {
        // TODO: Check whether that function could be used for ALL deposits
        
        if(!$depositId) {
            $count = $this->deposit_model->getCountForUser($this->userId, 'all');

            $items = $this->deposit_model->getSubsetForUser(1, 1);
            if($items[0]->method == 'io'){
                $depositId = $items[0]->id;
            }
        }
        
        $deposit = $this->deposit_model->getFull($depositId);

        if ($deposit && $deposit->client == $this->userId && $deposit->status != 'pending') {
            $data = array(
                'deposit' => $deposit
            );

            $this->layout->setTitle('Deposit Details')->view('deposit/details', $data);
        }
        else {
            $this->session->set_flashdata('error', 'Sorry there is no deposit with that ID');
            redirect($this->config->item('default_redirect'), 'refresh');
        }
    }
    
    public function checkBitcoinDepositData() {
        if($this->admin_model->getBitcoinDepositData('status') == "disabled") {
            
            if(uri_string() != "deposit"){
                redirect('/deposit');
            }
            return _l('disabled_deposit');
        }
    }
    
    public function coupon() {
        
        if ($this->user->verified == 0) {
            $this->session->set_flashdata('error', _l('fund_unverified_user', _l('coupon')));
            redirect('/deposit');
        }
        
        $this->load->model('voucher_model');
        
        if ($this->input->post()) {
            $this->form_validation->set_rules('code', 'Code', 'required|callback_validateCouponCode|callback_validateUsedCouponCode');
            if ($this->form_validation->run()) {
                $voucher = $this->voucher_model->get($this->input->post('code'));
                $data = array(
                    'client'   => $this->userId,
                    'method'   => 'cou',
                    'currency' => 'cad',
                    'amount'   => abs($voucher->value)
                );

                $details = null;

                if ($depositId = $this->deposit_model->addComplete($data, $details)) {
                    $voucherArray = (array)$voucher;
                    $voucherArray['client'] = $this->userId;
                    $this->voucher_model->update($this->input->post('code'), $voucherArray);
                    $this->session->set_flashdata('success', 'You have successfully used the coupon');

                    redirect('/deposit', 'refresh');
                }
                else $data['error_message'] = 'Incorrect use of coupon';
            }
        }
        
        $data['code'] = array(
            'name'        => 'code',
            'id'          => 'code',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => _l('voucher_code'),
            'value'       => $this->form_validation->set_value('code'),
            'autofocus'   => 'autofocus'
        );
        
        $this->layout->setTitle(_l('heading_withdrawal_coupon'))->view('deposit/method/coupon', $data);
    }
    
    public function validateCouponCode($code) {
        if(!$this->voucher_model->existCoupon($code)){
            
            $this->form_validation->set_message('validateCouponCode', _l('e_incorrect_coupon_code'));
            return false;
        }
        return true;
    }
    
    public function validateUsedCouponCode($code) {
        if(!$this->voucher_model->unusedCoupon($code)){
            $this->form_validation->set_message('validateUsedCouponCode', _l('e_used_expired_coupon_code'));
            return false;
        }
        return true;
    }
}
