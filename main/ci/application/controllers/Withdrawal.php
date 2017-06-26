<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Withdrawal extends MY_Controller {

    public function __construct() {
        parent::__construct();
        show_404();
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in_withdrawal'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        if ($this->user_model->isPINLockedOut($this->userId)) {
            $this->session->set_flashdata('error', 'Your withdrawal ability has been temporary suspended due to too many failed attempts - please contact support if you need more information');
            redirect('/trade');
        }
        
        unset($data['errors']);

        $this->load->model('withdrawal_model');
    }

	public function index() {
	   
        $data['errors'] = $this->checkBitcoinWithdrawalsData();

        $page = $this->input->get('page');
        if (!$page)
            $page = 1;

        $count = $this->withdrawal_model->getCountForUser($this->userId, 'all');
        //$refresh = $this->withdrawal_model->getExpireCouponWithdrawal($this->userId, 'cou', 'complete');
        if($refresh) {
            redirect('/withdraw');
        }
        
        $data['items'] = $this->withdrawal_model->getSubsetForUser($page, 20);

        $data['pages'] = generateNewPagination('/withdrawal?page=%d', $count, $page, 20);

        $this->layout->setTitle(_l('heading_withdrawal'))->view('withdrawal/index', $data);
    }

	public function bankwire() {
       
        if ($this->user->verified == 0) {
            $this->session->set_flashdata('error', _l('fund_unverified_user', _l('bank_wire')));
            redirect('/withdraw');
        }
        
        $allowed = array(
            'cad' => array('min' => 100, 'max' => 500000)
        );

        $twoFASecured = $this->user->twofa_status == '1';// && isset($this->user->secure_withdrawals) && $this->user->secure_withdrawals == '1';
        $currency     = 'cad';
        $withdrawalDetails = $this->user_model->getUserWithdrawalDetails($this->userId);
        
        $min = $allowed[$currency]['min'];
        $max = $allowed[$currency]['max'];
        $balances = $this->user_balance_model->get($this->userId);
        
        if($this->input->post('btn') || $this->input->post('btn_secure')){
            
            

            $this->form_validation->set_rules('amount',       _l('l_amount'),          'required|valid_currency_format[' . $currency . ']|less_than[' . $max . ']|greater_than[' . $min . ']|callback_has_enough_balance[' . $currency . ']');
            $this->form_validation->set_rules('address',      _l('l_address'),         'required|multiline');
            $this->form_validation->set_rules('bank_name',    _l('l_bank_name'),       'required|alpha_dash_space');
            $this->form_validation->set_rules('bank_address', _l('l_bank_address'),    'required|multiline');
            $this->form_validation->set_rules('account',      _l('l_account_number'),  'required|alpha_dash_space');
            $this->form_validation->set_rules('swift',        _l('l_swift'),           'required|alpha_dash_space');
            $this->form_validation->set_rules('comments',     _l('l_comments'),        'max_length[250]');
            $newBalance = bcsub($balances->$currency, $this->input->post('amount'), getPrecision($currency));
            
            if($this->input->post('btn_secure')){
                if ($twoFASecured){
                    if(property_exists($this->user, 'twofa_type')){
                        
                        $str = "amount=".$this->input->post('amount').":::".
                                "address=".$this->input->post('address').":::".
                                "bank_name=".$this->input->post('bank_name').":::".
                                "bank_address=".$this->input->post('bank_address').":::".
                                "account=".$this->input->post('account').":::".
                                "swift=".$this->input->post('swift').":::".
                                "ip=".getIp().":::".
                                "userId=".$this->userId.":::".
                                "new_balance=".$newBalance;
                        //print_r($this->validatePMail($this->input->post('code'), true, $str));
                        switch($this->user->twofa_type){
                            case '2fauth':
                                $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                                break;
                            case 'psmart':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'pmail':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'psms':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                        } 
                    } else {
                            $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                    }     
                }
            }
                
            if (!$this->form_validation->run()) {
                echo json_encode($this->form_validation->errors());
            } else {
                echo json_encode(array("display" => "ok",
                                       "ip" => getIp(), 
                                       "userId" => $this->userId, 
                                       "new_balance" => $newBalance));
            }
        } else {
            if($this->input->post()) {
                if (!isset($allowed[$currency])) {
                    $this->session->set_flashdata('error', _l('cannot_withdraw_currency'));
                    redirect('/withdraw');
                }
                
                $newBalance = bcsub($balances->$currency, $this->input->post('amount'), getPrecision($currency));
    
                if ($twoFASecured){
                    if(property_exists($this->user, 'twofa_type')){
                        

                        
                        $str = "amount=".$this->input->post('amount').":::"."address=".$this->input->post('address').":::".
                                "bank_name=".$this->input->post('bank_name').":::".
                                "bank_address=".$this->input->post('bank_address').":::".
                                "account=".$this->input->post('account').":::".
                                "swift=".$this->input->post('swift').":::".
                                "ip=".getIp().":::".
                                "userId=".$this->userId.":::".
                                "new_balance=".$newBalance;
                        switch($this->user->twofa_type){
                            case '2fauth':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                                break;
                            case 'psmart':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'pmail':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'psms':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                        }
                    } else {
                        $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                    }
                } else {
                    $this->form_validation->set_rules('pin', _l('l_transaction_pin'), 'required|callback_valid_pin');
                }
                
                
    
                $this->form_validation->set_rules('amount',       _l('l_amount'),          'required|valid_currency_format[' . $currency . ']|less_than[' . $max . ']|greater_than[' . $min . ']|callback_has_enough_balance[' . $currency . ']');
                $this->form_validation->set_rules('address',      _l('l_address'),         'required|multiline');
                $this->form_validation->set_rules('bank_name',    _l('l_bank_name'),       'required|alpha_dash_space');
                $this->form_validation->set_rules('bank_address', _l('l_bank_address'),    'required|multiline');
                $this->form_validation->set_rules('account',      _l('l_account_number'),  'required|alpha_dash_space');
                $this->form_validation->set_rules('swift',        _l('l_swift'),           'required|alpha_dash_space');
                $this->form_validation->set_rules('comments',     _l('l_comments'),        'max_length[250]');
    
                if ($this->form_validation->run()) {
                    $data = array(
                        'client'   => $this->userId,
                        'method'   => 'bw',
                        'currency' => $currency,
                        'amount'   => abs($this->input->post('amount'))
                    );
                    
                    $details = array(
                        'address'      => $this->input->post('address'),
                        'bank_name'    => $this->input->post('bank_name'),
                        'bank_address' => $this->input->post('bank_address'),
                        'account'      => $this->input->post('account'),
                        'swift'        => $this->input->post('swift'),
                        'instructions' => $this->input->post('comments')
                    );
                    
                    if(isset($this->user->referrer_id)){
                        $refererInfo = $this->referral_model->getReferrerUserInfo($this->user->referrer_id);
                        $fee = abs($this->input->post('amount')) * 0.01;
                        $correctFee = $fee > 50 ? $fee : 50;
                        
                        $details['referrerFee'] = $correctFee * $refererInfo['comission'];
                    }
    
                    
    
                    if ($withdrawal = $this->withdrawal_model->add($data, $details)) {
                        $details['comments'] = $this->input->post('comments');
                        $this->user_model->setUserWithdrawalDetails($this->userId, $details);
                        // All good
                        $this->session->set_flashdata('success', _l('request_received'));
    
                        systemEmail("Bank Wire withdrawal request made by User #".$this->userId." (".abs($this->input->post('amount'))." ".$currency.")");
    
                        redirect('/withdraw');
                    }
                    else $data['errors'] = _l('withdrawal_issue');
                }
            }
            
            $data['amount'] = array(
                'name'        => 'amount',
                'id'          => 'amount',
                'type'        => 'text',
                'class'       => 'form-control',
                'value'       => $this->form_validation->set_value('amount'),
                'placeholder' => 'Amount',
                'autofocus'   => 'autofocus'
            );
    
            $data['address'] = array(
                'name'        => 'address',
                'id'          => 'address',
                'class'       => 'form-control',
                'rows'        => 5,
                'placeholder' => 'Your Address',
                'value'       => $withdrawalDetails->address
            );
    
            $data['bankName'] = array(
                'name'        => 'bank_name',
                'id'          => 'bank_name',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => 'Bank Name',
                'value'       => $withdrawalDetails->bank_name
            );
    
            $data['bankAddress'] = array(
                'name'        => 'bank_address',
                'id'          => 'bank_address',
                'class'       => 'form-control',
                'rows'        => 5,
                'placeholder' => 'Full Bank Address including country and postcode',
                'value'       => $withdrawalDetails->bank_address
            );
    
            $data['account'] = array(
                'name'        => 'account',
                'id'          => 'account',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => 'Your Bank Account Number',
                'value'       => $withdrawalDetails->account
            );
    
            $data['swift'] = array(
                'name'        => 'swift',
                'id'          => 'swift',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => 'BIC/SWIFT Code',
                'value'       => $withdrawalDetails->swift
            );
    
            $data['comments'] = array(
                'name'        => 'comments',
                'id'          => 'comments',
                'class'       => 'form-control',
                'maxlength'   => 250,
                'rows'        => 5,
                'placeholder' => 'Any specific instructions like correspondent bank',
                'value'       => $withdrawalDetails->comments
            );
    
            if ($twoFASecured) {
                $data['code'] = array(
                    'name'         => 'code',
                    'id'           => 'code',
                    'type'         => 'text',
                    'value'        => '',
                    'class'        => 'form-control',
                    'placeholder'  => '123456',
                    'autocomplete' => 'off',
                    'autofocus'    => 'autofocus'
                );
                
                if(property_exists($this->user, 'twofa_type')){
                    switch($this->user->twofa_type){
                        case '2fauth':
                            $data['twofaType'] = 0;
                            break;
                        case 'psmart':
                            $data['code']['form'] = 'settings-form';
                            $data['twofaType'] = 4;
                            break;
                        case 'pmail':
                            $data['twofaType'] = 1;
                            break;
                        case 'psms':
                            $data['twofaType'] = 2;
                            break;
                    }
                } else {
                    $data['twofaType'] = 0;
                }
            }
            else {
                $data['pin'] = array(
                    'name'        => 'pin',
                    'id'          => 'pin',
                    'type'        => 'password',
                    'class'       => 'form-control',
                    'placeholder' => _l('p_transaction_pin'),
                    'value'       => $this->form_validation->set_value('pin')
                );
            }
    
            $data['twoFA']    = $twoFASecured;
            $data['user']     = $this->user;
            $data['currency'] = $currency;
            $data['available'] = $balances->{$currency.'_available'};
    
            $this->layout->setTitle(_l('heading_withdrawal_bankwire'))->view('withdrawal/method/bankwire', $data);
        }
    }



    /* NON-FIAT Methods */
    public function bitcoin() {
        
        /*
        $this->load->library('coinkitelibrary');
        $account = $this->coinkitelibrary->getP2SHAccount();
        sleep(3);
        $accDetails = $this->coinkitelibrary->send("/v1/account/".$account->CK_refnum);
        */
        
        $this->checkBitcoinWithdrawalsData();    
        $currency = 'btc';
        $twoFASecured = $this->user->twofa_status == '1';// && isset($this->user->secure_withdrawals) && $this->user->secure_withdrawals == '1';
        $balances = $this->user_balance_model->get($this->userId);
        
        if($this->input->post('btn') || $this->input->post('btn_secure')){
            $this->form_validation->set_rules('amount',  _l('l_amount'),          'required|valid_currency_format[btc]|greater_than[0.0001]|callback_has_enough_balance[btc]');
            $this->form_validation->set_rules('address', _l('l_address'),         'required|callback_valid_bitcoin_address');
            $newBalance = bcsub($balances->$currency, $this->input->post('amount'), getPrecision($currency));

            if($this->input->post('btn_secure')){
                if ($twoFASecured){
                    if(property_exists($this->user, 'twofa_type')){
                        $str = "amount=".$this->input->post('amount').":::".
                               "address=".$this->input->post('address').":::".
                                "ip=".getIp().":::".
                                "userId=".$this->userId.":::".
                                "new_balance=".$newBalance;
                        //print_r($this->validatePMail($this->input->post('code'), true, $str));
                        switch($this->user->twofa_type){
                            case '2fauth':
                                $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                                break;
                            case 'psmart':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'pmail':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'psms':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                        } 
                    } else {
                        $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                    }     
                }
            }
                
            if (!$this->form_validation->run()) {
                echo json_encode($this->form_validation->errors());
            } else {
                echo json_encode(array("display" => "ok",
                                       "ip" => getIp(), 
                                       "userId" => $this->userId, 
                                       "new_balance" => $newBalance));
            }
        } else {
            if ($this->input->post()) {
                if ($twoFASecured){
                    if(property_exists($this->user, 'twofa_type')){
                        $newBalance = bcsub($balances->$currency, $this->input->post('amount'), getPrecision($currency));
                        $str = "amount=".$this->input->post('amount').":::".
                               "address=".$this->input->post('address').":::".
                                "ip=".getIp().":::".
                                "userId=".$this->userId.":::".
                                "new_balance=".$newBalance;
                        //print_r($this->validatePMail($this->input->post('code'), true, $str));
                        switch($this->user->twofa_type){
                            case '2fauth':
                                $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                                break;
                            case 'psmart':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'pmail':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'psms':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                        } 
                    } else {
                            $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                    }     
                } else {
                    $this->form_validation->set_rules('pin', _l('l_transaction_pin'), 'required|callback_valid_pin');
                }
                
                $this->form_validation->set_rules('amount',  _l('l_amount'),          'required|valid_currency_format[btc]|greater_than[0.0001]|callback_has_enough_balance[btc]');
                $this->form_validation->set_rules('address', _l('l_address'),         'required|callback_valid_bitcoin_address');
    
                if ($this->form_validation->run()) {
                    $data = array(
                        'client'   => $this->userId,
                        'method'   => 'btc',
                        'currency' => 'btc',
                        'amount'   => abs($this->input->post('amount'))
                    );
    
                    $details = array(
                        'address' => $this->input->post('address'),
                        'confirmations' => 0
                    );
    
                    if ($withdrawal = $this->withdrawal_model->add($data, $details)) {
                        // All good
                        $this->session->set_flashdata('success', _l('received_bitcoin'));
    
                        //systemEmail("Bitcoin withdrawal request made by User #".$this->userId." (".abs($this->input->post('amount'))." BTC)");
                        redirect($this->config->item('default_redirect'));
                    }
                    else $data['errors'] = _l('withdrawal_issue');
                } else {
                    echo json_encode($this->form_validation->errors());
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
    
            $data['address'] = array(
                'name'        => 'address',
                'id'          => 'address',
                'class'       => 'form-control',
                'placeholder' => _l('p_bitcoin_address'),
                'value'       => $this->form_validation->set_value('address')
            );
    
            if ($twoFASecured) {
                $data['code'] = array(
                    'name'         => 'code',
                    'id'           => 'code',
                    'type'         => 'text',
                    'class'        => 'form-control',
                    'placeholder'  => '123456',
                    'autocomplete' => 'off',
                    'maxlength'    => '6',
                    'autofocus'    => 'autofocus',
                    'value'       => $this->form_validation->set_value('code')
                );
                
                if(property_exists($this->user, 'twofa_type')){
                    switch($this->user->twofa_type){
                        case '2fauth':
                            $data['twofaType'] = 0;
                            break;
                        case 'psmart':
                            $data['code']['form'] = 'settings-form';
                            $data['twofaType'] = 4;
                            break;
                        case 'pmail':
                            $data['twofaType'] = 1;
                            break;
                        case 'psms':
                            $data['twofaType'] = 2;
                            break;
                    }
                } else {
                    $data['twofaType'] = 0;
                }
                
            } else {
                $data['pin'] = array(
                    'name'        => 'pin',
                    'id'          => 'pin',
                    'type'        => 'password',
                    'class'       => 'form-control',
                    'placeholder' => _l('p_transaction_pin'),
                    'value'       => $this->form_validation->set_value('pin')
                );
            }
    
            $data['avail']    = $this->user->balances->{'btc_available'}.' '.strtoupper('XBT');
            $data['twoFA']    = $twoFASecured;
            $data['currency'] = 'btc';
            $data['available'] = $balances->{$currency.'_available'};
            $data['user']     = $this->user;
            $data['withdrawalStatus'] = $this->admin_model->getBitcoinWithdrawalsData('status');
    
            $this->layout->setTitle(_l('heading_withdrawal_bitcoin'))->view('withdrawal/method/bitcoin', $data);
        }
    }
    
    public function coupon(){

        if ($this->user->verified == 0) {
            $this->session->set_flashdata('error', _l('fund_unverified_user', _l('coupon')));
            redirect('/withdraw');
        }
        
        $data['verified'] = $this->user_model->verified;
        $this->load->model('voucher_model');
        $currency = 'cad';
        $twoFASecured = $this->user->twofa_status == '1';
        $balances = $this->user_balance_model->get($this->userId);
        $phase = 1;
        
        if($this->input->post('btn') || $this->input->post('btn_secure')){
            $this->form_validation->set_rules('amount', 'Amount', 'required|is_numeric|less_than['.$balances->{$currency.'_available'}.']|less_than[999.99]|greater_than[5]');
            $newBalance = bcsub($balances->$currency, $this->input->post('amount'), getPrecision($currency));
            if($this->input->post('btn_secure')){
                if ($twoFASecured){
                    if(property_exists($this->user, 'twofa_type')){
                        $str = "amount=".$this->input->post('amount').":::".
                                "ip=".getIp().":::".
                                "userId=".$this->userId.":::".
                                "new_balance=".$newBalance;
                        //print_r($this->validatePMail($this->input->post('code'), true, $str));
                        switch($this->user->twofa_type){
                            case '2fauth':
                                $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                                break;
                            case 'psmart':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'pmail':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'psms':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                        }
                    } else {
                        $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                    }         
                }
            }
            
            if (!$this->form_validation->run()) {
                echo json_encode($this->form_validation->errors());
            } else {
                echo json_encode(array("display" => "ok",
                                       "ip" => getIp(), 
                                       "userId" => $this->userId, 
                                       "new_balance" => $newBalance));
            }
        } else {
            if ($this->input->post()) {
                
                $this->form_validation->set_rules('amount', 'Amount', 'required|is_numeric|less_than['.$balances->{$currency.'_available'}.']|less_than[999.99]|greater_than[5]');
                //echo "Hello1";
                if ($twoFASecured){
                    if(property_exists($this->user, 'twofa_type')){
                        $newBalance = bcsub($balances->$currency, $this->input->post('amount'), getPrecision($currency));
                        
                        $str = "amount=".$this->input->post('amount').":::".
                                "ip=".getIp().":::".
                                "userId=".$this->userId.":::".
                                "new_balance=".$newBalance;
                        //print_r($this->validatePMail($this->input->post('code'), true, $str));
                        switch($this->user->twofa_type){
                            case '2fauth':
                                $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                                break;
                            case 'psmart':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'pmail':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'psms':
                                $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                        }
                    } else {
                        $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                    } 
                         
                } else {
                    $this->form_validation->set_rules('pin', _l('l_transaction_pin'), 'required|callback_valid_pin');
                }
                //echo "Hello2";
                if ($this->form_validation->run()) {
                    //echo "Hello";
                    $phase = 0;
                    
                    $code = $this->voucher_model->generateCode();
                    
                    $dataWidthdrawal = array(
                        'client'   => $this->userId,
                        'method'   => 'cou',
                        'currency' => 'cad',
                        'amount'   => abs($this->input->post('amount')),
                        'couponCode' => $code
                    );
                    
                    $details = array();
                    $withdrawal = $this->withdrawal_model->addComplete($dataWidthdrawal, $details);
                    
                    $data = array(
                        'value'    => $this->input->post('amount'),
                        'currency' => $currency,
                        'expiry'   => $this->input->post('expiry'),
                        'user_id'  => $this->userId,
                        'referrer' => $this->userId,
                        'code'     => $code,
                        'withdrawal' => $withdrawal
                    );
                    
                    $data['voucher'] = $this->voucher_model->userGenerate($data);
                    $user = $this->user_model->getUser($this->userId);
                    $data['name']     = $user->first_name;
                    
                    $this->load->library('Mandrilllibrary');
                    $api = $this->mandrilllibrary->getApi();
                    
                    $name = 'withdrawal_coupon';
                    $template = $api->templates->info($name);
                    $templateContent = array(
                        array(
                            'name' => 'editable',
                            'content' => $template['code']
                        )
                    );
                    
                    $mergeVars = array(
                        array(
                            'name' => 'name',
                            'content' => $data['name']
                        ),
                        array(
                            'name' => 'value',
                            'content' => $data['value']
                        ),
                        array(
                            'name' => 'voucher',
                            'content' => $data['voucher']
                        )
                    );
                    
                    $resultRender = $api->templates->render($name, $templateContent, $mergeVars);
                    
                    $htmlContent = $resultRender['html'];
                    
                    $pgpData = array();
                    $pgpData['content'] = $resultRender['html'];
                    if(isset($user->pgp_status) && $user->pgp_status == 1) {
                        $pgpData['key'] = $user->pgp_key;
                        $pgpData['content'] = strip_tags($resultRender['html']);
                        $encryptMessage = $this->mandrilllibrary->send("/pgpEncrypt","POST",$pgpData);
                        
                        if(isset($encryptMessage->message) && $encryptMessage->message != ''){
                            $htmlContent = $this->mandrilllibrary->formatMessage($encryptMessage->message);
                        }
                    } else {
                        $encryptMessage = $this->mandrilllibrary->send("/pgpSign","POST",$pgpData);
                        $htmlContent = $this->mandrilllibrary->formatSign($encryptMessage->message);
                    }
                    
                    
                    $message = array(
                        'html' => $htmlContent,
                        'subject' => $this->config->item('site_full_name') . ' - ' . _l('coupon_email_subject'),
                        'from_email' => 'support@taurusexchange.com',
                        'from_name' => 'whitebarter Bitcoin Exchange',
                        'to' => array(
                            array(
                                'email' => $user->email,
                                'name' => 'Recipient Name',
                                'type' => 'to'
                            )
                        ),
                        'headers' => array('Reply-To' => $user->email),
                        
                    );
                    
                    $result = $api->messages->send($message);
                    
                } else {
                    print_r($this->form_validation->errors());
                }
            }
            
            $data['expiries'] = array(0 => 'Never', 1 => 'One Day', 3 => 'Three Days', 7 => 'Seven Days', 30 => 'Thirty Days', 60 => 'Sixty Days');
            $data['expiry']   = $this->form_validation->set_value('expiry');
            
            $data['phase'] = $phase;
            $data['available'] = $balances->{$currency.'_available'};
            $data['amount'] = array(
                'name'        => 'amount',
                'id'          => 'amount',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => _l('p_amount'),
                'value'       => $this->form_validation->set_value('amount'),
                'autofocus'   => 'autofocus'
            );
            
            if ($twoFASecured) {
                $data['code'] = array(
                    'name'         => 'code',
                    'id'           => 'code',
                    'type'         => 'text',
                    'class'        => 'form-control',
                    'placeholder'  => '123456',
                    'autocomplete' => 'off',
                    'maxlength'    => '6',
                    'autofocus'    => 'autofocus',
                    'value'       => $this->form_validation->set_value('code')
                );
                
                if(property_exists($this->user, 'twofa_type')){
                    switch($this->user->twofa_type){
                        case '2fauth':
                            $data['twofaType'] = 0;
                            break;
                        case 'psmart':
                            $data['code']['form'] = 'settings-form';
                            $data['twofaType'] = 4;
                            break;
                        case 'pmail':
                            $data['twofaType'] = 1;
                            break;
                        case 'psms':
                            $data['twofaType'] = 2;
                            break;
                    }
                } else {
                    $data['twofaType'] = 0;
                }
                
            } else {
                $data['pin'] = array(
                    'name'        => 'pin',
                    'id'          => 'pin',
                    'type'        => 'password',
                    'class'       => 'form-control',
                    'placeholder' => _l('p_transaction_pin'),
                    'value'       => $this->form_validation->set_value('pin')
                );
            }
            
            $data['user']     = $this->user;
            $data['twoFA']    = $twoFASecured;
            
            $this->layout->setTitle(_l('heading_withdrawal_coupon'))->view('withdrawal/method/coupon', $data);
        }
    }
    
    public function cheque() {
        
        $this->load->model('referral_model');
        $this->load->model('trade_model');
        
        if ($this->user->verified == 0) {
            $this->session->set_flashdata('error', _l('fund_unverified_user', _l('bank_wire')));
            redirect('/withdraw');
        }
        
        $allowed = array(
            'cad' => array('min' => 100, 'max' => 500000)
        );

        $twoFASecured = $this->user->twofa_status == '1';// && isset($this->user->secure_withdrawals) && $this->user->secure_withdrawals == '1';
        $currency     = 'cad';
        $balances = $this->user_balance_model->get($this->userId);
        $withdrawalDetails = $this->user_model->getUserWithdrawalDetails($this->userId);
        
        $min = $allowed[$currency]['min'];
        $max = $allowed[$currency]['max'];

        if($this->input->post('btn') || $this->input->post('btn_secure')){
            $this->form_validation->set_rules('amount',       _l('l_amount'),          'required|valid_currency_format[' . $currency . ']|less_than[' . $max . ']|greater_than[' . $min . ']|callback_has_enough_balance[' . $currency . ']');
            $this->form_validation->set_rules('address',      _l('l_address'),         'required|multiline');
            
            $newBalance = bcsub($balances->$currency, $this->input->post('amount'), getPrecision($currency));
            
            if($this->input->post('btn_secure')){
                if ($twoFASecured){
                    if(property_exists($this->user, 'twofa_type')){
                        $str = "amount=".$this->input->post('amount').":::".
                               "address=".$this->input->post('address').":::".
                                "ip=".getIp().":::".
                                "userId=".$this->userId.":::".
                                "new_balance=".$newBalance;
                        switch($this->user->twofa_type){
                            case '2fauth':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                                break;
                            case 'psmart':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'pmail':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'psms':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                        }
                    } else {
                        $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                    }
                }
            }
            
            if (!$this->form_validation->run()) {
                echo json_encode($this->form_validation->errors());
            } else {
                echo json_encode(array("display" => "ok",
                                       "ip" => getIp(), 
                                       "userId" => $this->userId, 
                                       "new_balance" => $newBalance));
            }
        } else {
            if ($this->input->post()) {
                if (!isset($allowed[$currency])) {
                    $this->session->set_flashdata('error', _l('cannot_withdraw_currency'));
                    redirect('/withdraw');
                }
    
                if ($twoFASecured){
                    if(property_exists($this->user, 'twofa_type')){
                        $newBalance = bcsub($balances->$currency, $this->input->post('amount'), getPrecision($currency));
                        
                        $str = "amount=".$this->input->post('amount').":::".
                               "address=".$this->input->post('address').":::".
                                "ip=".getIp().":::".
                                "userId=".$this->userId.":::".
                                "new_balance=".$newBalance;
                        switch($this->user->twofa_type){
                            case '2fauth':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                                break;
                            case 'psmart':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'pmail':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                            case 'psms':
                                $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail['.$str.']');
                                break;
                        }
                    } else {
                        $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                    }
                } else {
                    $this->form_validation->set_rules('pin', _l('l_transaction_pin'), 'required|callback_valid_pin');
                }
    
                $this->form_validation->set_rules('amount',       _l('l_amount'),          'required|valid_currency_format[' . $currency . ']|less_than[' . $max . ']|greater_than[' . $min . ']|callback_has_enough_balance[' . $currency . ']');
                $this->form_validation->set_rules('address',      _l('l_address'),         'required|multiline');
    
                if ($this->form_validation->run()) {
                    $data = array(
                        'client'   => $this->userId,
                        'method'   => 'ch',
                        'currency' => $currency,
                        'amount'   => abs($this->input->post('amount'))
                    );
                    
                    $details = array(
                        'address'      => $this->input->post('address'),
                    );
                    
                    if(isset($this->user->referrer_id)){
                        $refererInfo = $this->referral_model->getReferrerUserInfo($this->user->referrer_id);
                        $fee = abs($this->input->post('amount')) * 0.01;
                        $correctFee = $fee > 40 ? $fee : 40;
                        $details['referrerFee'] = $correctFee * $refererInfo['comission'];
                    }
    
                    if ($withdrawal = $this->withdrawal_model->add($data, $details)) {
                        
                        // All good
                        $this->session->set_flashdata('success', _l('cheque_received'));
    
                        redirect('/withdraw');
                    }
                    else $data['errors'] = _l('withdrawal_issue');
                }
            }
    
            $data['amount'] = array(
                'name'        => 'amount',
                'id'          => 'amount',
                'type'        => 'text',
                'class'       => 'form-control',
                'value'       => $this->form_validation->set_value('amount'),
                'placeholder' => 'Amount',
                'autofocus'   => 'autofocus'
            );
    
            $data['address'] = array(
                'name'        => 'address',
                'id'          => 'address',
                'class'       => 'form-control',
                'rows'        => 5,
                'placeholder' => 'Full address including City, Country and Postal Code',
                'value'       => $this->form_validation->set_value('address')
            );
    
            if ($twoFASecured) {
                $data['code'] = array(
                    'name'         => 'code',
                    'id'           => 'code',
                    'type'         => 'text',
                    'value'        => '',
                    'class'        => 'form-control',
                    'placeholder'  => '123456',
                    'autocomplete' => 'off',
                    'autofocus'    => 'autofocus'
                );
                
                if(property_exists($this->user, 'twofa_type')){
                    switch($this->user->twofa_type){
                        case '2fauth':
                            $data['twofaType'] = 0;
                            break;
                        case 'psmart':
                            $data['code']['form'] = 'settings-form';
                            $data['twofaType'] = 4;
                            break;
                        case 'pmail':
                            $data['twofaType'] = 1;
                            break;
                        case 'psms':
                            $data['twofaType'] = 2;
                            break;
                    }
                } else {
                    $data['twofaType'] = 0;
                }
            }
            else {
                $data['pin'] = array(
                    'name'        => 'pin',
                    'id'          => 'pin',
                    'type'        => 'password',
                    'class'       => 'form-control',
                    'placeholder' => _l('p_transaction_pin'),
                    'value'       => $this->form_validation->set_value('pin')
                );
            }
    
            $data['twoFA']    = $twoFASecured;
            $data['user']     = $this->user;
            $data['currency'] = $currency;
            $data['available'] = $balances->{$currency.'_available'};
    
            $this->layout->setTitle(_l('heading_withdrawal_bankwire'))->view('withdrawal/method/cheque', $data);
        }
    }

    public function valid_pin($pin) {
        if (sha1($pin) !== $this->user->pin) {
            $this->user_model->increasePINLockedOut($this->userId);

            $this->form_validation->set_message('valid_pin', _l('e_incorrect_pin'));
            return false;
        }

        return true;
    }

    public function validateTwoFACode($code) {
        $secret = $this->input->post('two_factor_secret');
        
        if (!$secret)
            $secret = $this->user->twofa_secret;
            
        $this->load->helper('GoogleAuthenticator');
        $ga = new PHPGangsta_GoogleAuthenticator();

        if (!$ga->verifyCode($secret, $code)) {
            $this->form_validation->set_message('validateTwoFACode', _l('e_incorrect'));
            return false;
        }

        return true;
    }
    
    public function validatePMail($code, $str = null) {
        if(!is_null($str)){
            $type = true;
        } else {
            $type = false;
        }
        
        $this->load->library('Protectimus');
        $api = $this->protectimus->getApi();
        $str = str_replace(":::","|",$str);

        if($type){
            $response = $api->verifySignedTransaction($this->user->token_id, $str, hash_hmac("sha256", $str, $this->protectimus->getApiKey()), $code);
        } else {
            $response = $api->authenticateToken($this->protectimus->getResourceId(), $this->user->token_id, $code, null);
        }
        
        $authenticationResult = $response->response->result;
        
        if(!$authenticationResult || $authenticationResult == '') {
            $this->form_validation->set_message('validatePMail', _l('e_incorrect_code'));
            return false;
        }
        
        return true;
    }
    
    public function checkBitcoinWithdrawalsData() {
        if($this->admin_model->getBitcoinWithdrawalsData('status') == "disabled") {

            $errors = _l('disabled_withdraw');
            
            if(uri_string() != "withdraw"){
                redirect('/withdraw');
            }
            
            return $errors;
        }
    }
    //todo payza code here aswell - removed 
    
    public function has_enough_balance($amount, $currency) {
        if (bccomp($this->user->balances->{$currency . '_available'}, $amount, getPrecision($currency)) < 0) {
            $this->form_validation->set_message('has_enough_balance', _l('insufficient_balance', strtoupper($currency)));
            return false;
        }

        return TRUE;
    }

    public function valid_bitcoin_address($address) {
        if (defined('ENVIRONMENT'))
        {
            switch (ENVIRONMENT)
            {
                case 'development':
                    $reg_ex = '/^[2mn][1-9A-HJ-NP-Za-km-z]{20,40}$/';
                    break;
        
                case 'production':
                    $reg_ex = '/^[13][1-9A-HJ-NP-Za-km-z]{20,40}$/';
                    break;
        
                default:
                    exit('The application environment is not set correctly.');
            }
        }

        if (!preg_match ($reg_ex, $address)) {
            $this->form_validation->set_message('valid_bitcoin_address', _l('e_appears_invalid'));
            return false;
        }

        return true;
    }
}
