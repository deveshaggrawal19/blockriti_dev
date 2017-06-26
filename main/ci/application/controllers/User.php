<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends MY_Controller {

    public function __construct() {
        parent::__construct();
        show_404();
		//Load Google auth helper for 2 step login and settings page
		$this->load->helper('GoogleAuthenticator');
        $this->load->model('event_model');
        $this->load->model('user_document_model');
    }

	public function register() {
        if ($this->user !== 'guest')
            redirect($this->config->item('default_redirect'));

        $phase = 1;

        $referrerCode = get_cookie('referrer');
        $referrerData = $referrerCode ? $this->referral_model->findByCode($referrerCode) : null;

        // if the referrer is fake we wipe it out
        if (!$referrerData && $referrerCode) {
            delete_cookie('referrer');
            $referrerData = null;
        }

        if ($this->input->post('continue')) {
            $this->form_validation->set_rules('email',      _l('email_address'), 'required|xss_clean|valid_email|callback_unique_email');
            $this->form_validation->set_rules('first_name', _l('first_name'),    'required|xss_clean|alpha_dash_space');
            $this->form_validation->set_rules('last_name',  _l('last_name'),     'required|xss_clean|alpha_dash_space');
            $this->form_validation->set_rules('dob',        _l('dob'),           'required|xss_clean|callback_check_date');
			$this->form_validation->set_rules('country',    _l('country'),       'required|xss_clean|valid_country_no_us');

            if ($this->form_validation->run()) {
                $userData = array(
                    'email'      => $this->input->post('email'),
                    'first_name' => $this->input->post('first_name'),
                    'last_name'  => $this->input->post('last_name'),
                    'dob'        => $this->input->post('dob'),
                    'country'    => $this->input->post('country'),
                    'language'   => $this->config->item('default_lang')
                );

                if ($userId = $this->user_model->save($userData, null, $phase)) {
                    $data['userId'] = $userId;

                    $phase = 2;
                }
                else $data['errors'] = _l('unexpected_error');
            }
        }
        else if ($this->input->post() && count($this->input->post()) > 0){

            $phase = 2;

            if (!$this->input->post('_id'))
                show_404();

            $userId = $this->input->post('_id');

            $user = $this->user_model->get($userId);
            // If the user is not found or if there is already a password set, bail out
            if (!$user || !empty($user->password))
                show_404();

            $data['userId'] = $userId;

            $this->form_validation->set_rules('password',             _l('l_password'),         'required');
            $this->form_validation->set_rules('confirm_password',     _l('l_confirm_password'), 'required|matches[password]');
            $this->form_validation->set_rules('pin',                  _l('l_transaction_pin'),  'required|integer');
            $this->form_validation->set_rules('g-recaptcha-response', _l('l_captcha'),          'required|callback_valid_recaptcha');

            if ($this->form_validation->run()) {
                $salt     = generateRandomString(20);
                $password = $this->input->post('password');

                $userData = array(
                    'salt'     => $salt,
                    'password' => $this->api_security->hash($password, $salt),
                    'pin'      => sha1($this->input->post('pin')),
                    'active'   => 1
                );

                if ($this->user_model->save($userData, $userId, $phase)) {
                    // This block is used to grab a new btc address for that user if bitcoind is enabled
                    $status = $this->bitcoin_model->getStatus('bitcoind');
                    if ($status != 'disabled') {
                        //$this->load->library('easybitcoin');
                        //$this->easybitcoin->getaccountaddress('user:' . $userId);
                    }

                    // All good
                    $data['clientId'] = $userId;
                    $data['name']     = $user->first_name;
                    $data['link'] = site_url('/verify_email/'.urlencode($user->email).'/'.
                                             md5(urlencode($user->email).":::".date("Y-m-d")));
                    
                    $this->load->library('Mandrilllibrary');
                    $api = $this->mandrilllibrary->getApi();
                    
                    $name = 'welcome';
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
                            'name' => 'clientid',
                            'content' => $data['clientId']
                        ),
                        array(
                            'name' => 'link',
                            'content' => $data['link']
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
                        'subject' =>  _l('welcome_to') . ' ' . $this->config->item('site_full_name'),
                        'from_email' => 'support@taurusexchange.com',
                        'from_name' => 'Taurus Exchange',
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
                    
                    /*
                    $this->email_queue_model->email   = $user->email;
                    $this->email_queue_model->message = $this->layout->partialView('emails/welcome_'.$this->language, $data);
                    $this->email_queue_model->subject = _l('welcome_to') . ' ' . $this->config->item('site_full_name');

                    $this->email_queue_model->store();
                    */
                    if ($referrerData) {
                        $this->referral_model->addToUser($userId, $referrerData->id);
                        delete_cookie('referrer');
                    }

                    // Log registration event
                    $this->event_model->add($userId,'register');

                    $this->layout->setTitle(_l('registration_complete'))->view('user/register/complete', $data);
                    return;
                }
                else $data['errors'] = _l('unexpected_error');
            }
        }

        $data['email'] = array(
            'name'        => 'email',
            'id'          => 'email',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('email'),
            'class'       => 'form-control',
            'placeholder' => _l('enter_valid_email'),
            'autofocus'   => 'autofocus'
        );

        $data['first_name'] = array(
            'name'        => 'first_name',
            'id'          => 'first_name',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('first_name'),
            'class'       => 'form-control',
            'placeholder' => _l('your_first_name')
        );

        $data['last_name'] = array(
            'name'        => 'last_name',
            'id'          => 'last_name',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('last_name'),
            'class'       => 'form-control',
            'placeholder' => _l('your_last_name')
        );

        $data['dob'] = $this->input->post('dob');

        $country = $this->input->post('country');

        $data['countries'] = countriesLocalised($this->language);
        $data['country']   = $country ? $country : $this->config->item('default_country');

        $data['password'] = array(
            'name'        => 'password',
            'id'          => 'password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('p_password'),
            'autofocus'   => 'autofocus'
        );

        $data['confirm_password'] = array(
            'name'        => 'confirm_password',
            'id'          => 'confirm_password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('p_confirm_password')
        );

        $data['pin'] = array(
            'name'        => 'pin',
            'id'          => 'pin',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('transaction_pin_digits_only')
        );

        $data['referrer'] = $referrerData;

        $data['form'] = $this->layout->partialView('user/register/phase' . $phase, $data);

        $this->layout->setTitle(_l('heading_register'))->view('user/register', $data);
	}

    public function login() {
        echo "abc";
        exit;
        $now = time();

        if ($this->input->post()) {
            $this->form_validation->set_rules('client_id', _l('l_client_id'), 'required|validNumber');
            $this->form_validation->set_rules('password',  _l('l_password'),  'required');

            if ($this->form_validation->run()) {
                $clientId = $this->input->post('client_id');
                $password = $this->input->post('password');
                $time     = $this->input->post('time');
                $hash     = $this->input->post('hash');

                $error = null;
                if ($user = $this->user_model->login($clientId, $password, $time, $hash, $error)) {
                    $this->user_model->saveSession($user);

                    $redirect = $this->input->get('redirect');
                    if (!$redirect)
                        $redirect = $this->config->item('default_redirect');
                    //if ($user->twofa_status !== '0') {                   
                        redirect('/authenticate' . ($redirect ? '?redirect=' . $redirect : ''));
                    /*
                    }
                    else {
                        // All good
                        $this->session->set_flashdata('success', _l('you_are_logged_in'));

                        redirect($redirect);
                    }
                    */
                }
                else $data['errors'] = $error;
            }
        }

        $data['client_id'] = array(
            'name'        => 'client_id',
            'id'          => 'client_id',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => _l('p_client_id'),
            'value'       => $this->form_validation->set_value('client_id'),
            'autofocus'   => 'autofocus'
        );

        $data['password'] = array(
            'name'        => 'password',
            'id'          => 'password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('p_password')
        );

        $data['time'] = $now;
        $data['hash'] = $this->user_model->setLoginHash($now);

        $this->layout->setTitle(_l('heading_login'))->view('user/login', $data);
    }

    public function logout() {
        if ($this->user == 'guest') redirect("/");
        $this->user_model->clearSession($this->user->id);

        redirect('/');
    }

    public function forgot_password() {
        if ($this->input->post() && count($this->input->post()) > 0){
            $this->form_validation->set_rules('client_id',  _l('l_client_id'),       'required|xss_clean|integer');
            $this->form_validation->set_rules('email',      _l('l_email'),           'required|xss_clean|valid_email');
            $this->form_validation->set_rules('dob',        _l('l_date_of_birth'),   'required|xss_clean|callback_check_date');
            $this->form_validation->set_rules('country',    _l('l_country'),         'required|valid_country');

            if ($this->form_validation->run()) {
                $email = $this->input->post('email');

                $data = array(
                    'client_id' => $this->input->post('client_id'),
                    'email'     => $email,
                    'dob'       => $this->input->post('dob'),
                    'country'   => $this->input->post('country')
                );

                if ($data = $this->user_model->resetPassword($data)){
                    $data['timestamp'] = date('c', milliseconds() / 1000);
                    
                    $this->load->library('Mandrilllibrary');
                    $api = $this->mandrilllibrary->getApi();
                    $userData = $this->user_model->findUserByEmail($email);
                    
                    $name = 'forgot_password';
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
                            'name' => 'clientid',
                            'content' => $data['client']
                        ),
                        array(
                            'name' => 'ip',
                            'content' => $data['ip']
                        ),
                        array(
                            'name' => 'link',
                            'content' => $data['link']
                        ),
                        array(
                            'name' => 'timestamp',
                            'content' => $data['timestamp']
                        )
                    );
                    
                    $resultRender = $api->templates->render($name, $templateContent, $mergeVars);
                    
                    $htmlContent = $resultRender['html'];
                    
                    $pgpData = array();
                    $pgpData['content'] = $resultRender['html'];
                    if(isset($userData->pgp_status) && $userData->pgp_status == 1) {
                        $pgpData['key'] = $userData->pgp_key;
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
                        'subject' => $this->config->item('site_full_name') . ' - ' . _l('password_reset_request'),
                        'from_email' => 'support@taurusexchange.com',
                        'from_name' => 'Taurus Exchange',
                        'to' => array(
                            array(
                                'email' => $email,
                                'name' => 'Recipient Name',
                                'type' => 'to'
                            )
                        ),
                        'headers' => array('Reply-To' => $email),
                        
                    );
                    
                    $result = $api->messages->send($message);
                    /*
                    $this->email_queue_model->email   = $email;
                    $this->email_queue_model->message = $this->layout->partialView('emails/forgot_password_'.$this->language, $data);
                    $this->email_queue_model->subject = $this->config->item('site_full_name') . ' - ' . _l('password_reset_request');

                    $this->email_queue_model->store();
                    */
                    
                    $this->session->set_flashdata('success', _l('should_receive_pw_reset'));
                    redirect('/', 'refresh');
                }
                else $data['errors'] = _l('problem_locating_record');
            }
        }

        $data['client_id'] = array(
            'name'        => 'client_id',
            'id'          => 'client_id',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => _l('p_exchange_client_id', $this->config->item('site_name')),
            'value'       => $this->form_validation->set_value('client_id'),
            'autofocus'   => 'autofocus'
        );

        $data['email'] = array(
            'name'        => 'email',
            'id'          => 'email',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('email'),
            'class'       => 'form-control',
            'placeholder' => _l('p_exchange_email_addr', $this->config->item('site_name')),
            'autofocus'   => 'autofocus'
        );

        $country = $this->input->post('country');

        $data['countries'] = countriesLocalised($this->language);
        $data['country']   = $country ? $country : $this->config->item('default_country');

        $data['dob'] = $this->input->post('dob');

        $this->layout->setTitle(_l('forgotten_password'))->view('user/reminder/password', $data);
    }

    public function forgot_confirm($code) {
        if (!$requestData = $this->user_model->checkResetPasswordCode($code)) {
            $this->session->set_flashdata('error', _l('erroneous_code_entered'));
            redirect('/', 'refresh');
        }

        if ($this->input->post() && count($this->input->post()) > 0){
            $this->form_validation->set_rules('password',         _l('password'),         'required');
            $this->form_validation->set_rules('confirm_password', _l('confirm_password'), 'required|matches[password]');

            if ($this->form_validation->run()) {
                $salt     = generateRandomString(20);
                $password = $this->input->post('password');

                $userData = array(
                    'salt'     => $salt,
                    'password' => $this->api_security->hash($password, $salt)
                );

                if ($this->user_model->changePasswordAfterReset($code, $userData)) {
                    $this->session->set_flashdata('success', _l('password_has_been_reset'));
                    redirect('/login', 'refresh');
                }
                else $data['errors'] = _l('unexpected_error');
            }
        }

        $data['password'] = array(
            'name'        => 'password',
            'id'          => 'password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('password'),
            'autofocus'   => 'autofocus'
        );

        $data['confirm_password'] = array(
            'name'        => 'confirm_password',
            'id'          => 'confirm_password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('confirm_password')
        );

        $data['userId'] = $requestData->client;

        $this->layout->setTitle(_l('forgotten_password'))->view('user/reminder/complete', $data);
    }

    public function forgot_client_id() {
        if ($this->input->post() && count($this->input->post()) > 0){
            $this->form_validation->set_rules('email', _l('email_address'), 'required|xss_clean|valid_email');

            if ($this->form_validation->run()) {
                $email = $this->input->post('email');

                if ($userData = $this->user_model->findUserByEmail($email)){
                    
                    $this->load->library('Mandrilllibrary');
                    $api = $this->mandrilllibrary->getApi();
                    
                    $name = 'forgot_clientid';
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
                            'content' => $userData->first_name . ' ' . $userData->last_name
                        ),
                        array(
                            'name' => 'clientid',
                            'content' => $userData->id
                        ),
                        array(
                            'name' => 'ip',
                            'content' => getIp()
                        ),
                        array(
                            'name' => 'timestamp',
                            'content' => date('c', milliseconds() / 1000)
                        )
                    );
                    
                    
                    $resultRender = $api->templates->render($name, $templateContent, $mergeVars);
                    $htmlContent = $resultRender['html'];
                    
                    $pgpData = array();
                    $pgpData['content'] = $resultRender['html'];
                    
                    if(isset($userData->pgp_status) && $userData->pgp_status == 1) {
                        $pgpData['key'] = $userData->pgp_key;
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
                        'subject' => $this->config->item('site_full_name') . ' - ' . _l('client_id_reminder'),
                        'from_email' => 'support@taurusexchange.com',
                        'from_name' => 'Taurus Exchange',
                        'to' => array(
                            array(
                                'email' => $email,
                                'name' => 'Recipient Name',
                                'type' => 'to'
                            )
                        ),
                        'headers' => array('Reply-To' => $email),
                        
                    );
                    
                    $result = $api->messages->send($message);
                    
                    
                    /*
                    $this->email_queue_model->email   = $email;
                    $this->email_queue_model->message = $this->layout->partialView('emails/forgot_clientid_'.$this->language, $data);
                    $this->email_queue_model->subject = $this->config->item('site_full_name') . ' - ' . _l('client_id_reminder');

                    $this->email_queue_model->store();
                    */
                }

                $this->session->set_flashdata('success', _l('should_receive_email'));
                redirect('/', 'refresh');
            }
        }

        $data['email'] = array(
            'name'        => 'email',
            'id'          => 'email',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('email'),
            'class'       => 'form-control',
            'placeholder' => _l('p_exchange_email_addr', $this->config->item('site_name')),
            'autofocus'   => 'autofocus'
        );

        $this->layout->setTitle(_l('forgotten_client_id'))->view('user/reminder/client_id', $data);
    }

    public function verify() {
        if ($this->user == 'guest' || $this->user_model->verified)
            redirect("/");

        $data = array();

        $details = $this->user_model->getDetails($this->userId);
    
        if (!$details) {
            if ($this->input->post()) {
                $this->form_validation->set_rules('address', _l('address'), 'required|xss_clean|max_length[100]');
                $this->form_validation->set_rules('city',    _l('city'),    'required|alpha_dash_space|max_length[50]');
                $this->form_validation->set_rules('state',   _l('state'),   'valid_state');
                $this->form_validation->set_rules('country', _l('country'), 'required|valid_country');
                $this->form_validation->set_rules('zip',     _l('zip'),     'required|max_length[10]');
                $this->form_validation->set_rules('occupation',_l('occupation'),'required');
                $this->form_validation->set_rules('phone',   _l('phone'),   'required|numeric|max_length[30]');
                if($this->user->dob == '' && $this->input->post('dob') != ''){
                    $this->form_validation->set_rules('dob',      _l('dob'), 'required|xss_clean|callback_check_date');
                }
                

                if ($this->form_validation->run()) {
                    if($this->user->dob == '' && $this->input->post('dob') != ''){
                        $dataDob = array(
                            'dob' => $this->input->post('dob')
                        );
                        
                        $this->user_model->update($this->userId, $dataDob);
                    }
                    
                    $data = array(
                        'address' => $this->input->post('address'),
                        'city'    => $this->input->post('city'),
                        'state'   => $this->input->post('state'),
                        'country' => $this->input->post('country'),
                        'zip'     => $this->input->post('zip'),
                        'occupation'=> $this->input->post('occupation'),
                        'phone'   => $this->input->post('phone')
                    );

                    $this->user_model->updateDetails($this->userId, $data);

                    $this->session->set_flashdata('success', 'Thank you. Please proceed with Step 2 of the verification process.');
                    redirect('/verify', 'refresh');
                }
            }

            $data['address'] = array(
                'name'        => 'address',
                'id'          => 'address',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => 'Full Street Address',
                'rows'        => 2,
                'value'       => $this->form_validation->set_value('address')
            );

            $data['city'] = array(
                'name'        => 'city',
                'id'          => 'city',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => _l('p_city'),
                'value'       => $this->form_validation->set_value('city')
            );

            $data['zip'] = array(
                'name'        => 'zip',
                'id'          => 'zip',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => _l('p_zip'),
                'value'       => $this->form_validation->set_value('zip')
            );
            
            $data['occupation'] = array(
                'name'        => 'occupation',
                'id'          => 'occupation',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => _l('occupation'),
                'value'       => $this->form_validation->set_value('occupation')
            );

            $data['phone'] = array(
                'name'        => 'phone',
                'id'          => 'phone',
                'type'        => 'text',
                'class'       => 'form-control',
                'placeholder' => '1234567890',
                'value'       => $this->form_validation->set_value('phone')
            );

            $country = $this->input->post('country');
            $state   = $this->input->post('state');

            $data['countries'] = countriesLocalised($this->language);
            $data['country']   = $country ? $country : $this->config->item('default_country');
            $data['states']    = states($data['country']);
            $data['state']     = $state;
        }
        else {
            if ($this->input->post() && count($this->input->post()) > 0){
                $this->load->library('aws_s3');
                for ($i = 1; $i < 4; $i++) {
                    $name = 'document_' . $i;
                    $file = $_FILES[$name];

                    if ($file['error'] == 0) {
                        $uid = generateRandomString(10, true);

                        $config['upload_path']   = UPLOADPATH;
                        $config['allowed_types'] = 'gif|jpg|jpeg|png|pdf';
                        $config['max_size']      = '2048';
                        $config['file_name']     = $uid;
                        $config['remove_spaces'] = true;
                        $config['overwrite']     = false;
                        $config['max_width']     = '0';
                        $config['max_height']    = '0';

                        $this->load->library('upload', $config);

                        if ($this->upload->do_upload($name)) {
                            $uploadData = $this->upload->data();

                            $fileData = array(
                                'userid'   => $this->user->id,
                                'uid'      => $uid,
                                'filename' => $file['name'],
                                'mime'     => $uploadData['file_type'],
                                'size'     => $file['size']
                            );

                            try {
                                $this->aws_s3->putObject(array(
                                    'Bucket'      => 'documents-taurus',
                                    'Key'         => $this->user->id . '/' . $uid . '-' . strtolower($file['name']),
                                    'SourceFile'  => $uploadData['full_path'],
                                    'ContentType' => $uploadData['file_type']
                                ));

                                $this->load->model('user_document_model');

                                $this->user_document_model->save($this->user->id, $fileData);

                                $data['success'][] = $file['name'];
                            }
                            catch (Exception $e) {
                                log_message('error', $e->getMessage());
                            }

                            @unlink($uploadData['full_path']);
                        } else {
                            $data['errors'][] = array(
                                'file'    => $file['name'],
                                'message' => $this->upload->display_errors('', '')
                            );
                        }
                    } else {
                        if($file['error'] != 4){
                            $data['errors'][] = array(
                                'file'    => $file['name'],
                                'message' => "This file is too big"
                            );
                        }
                    }
                }

                if (isset($data['success'])) {
                    $message = 'Thank you, the ' . (count($data['success']) == 1 ? 'file' : 'files') . ' ' . implode(', ', $data['success']) . ' ' . (count($data['success']) == 1 ? 'has' : 'have') . ' been received. Our verification team will review them within 48 hours.';
                    $this->session->set_flashdata('success', $message);
                }

                if (isset($data['errors'])) {
                    $message = 'Sorry there was an issue with some of the files you have tried to upload: <ul>';
                    foreach ($data['errors'] as $error)
                        $message .= '<li>' . $error['file'] . ': ' . $error['message'] . '</li>';
                    $message .= '</ul>';

                    $this->session->set_flashdata('error', $message);
                }

                redirect('/verify', 'refresh');
            }

            $this->load->model('user_document_model');
            $data['documents'] = $this->user_document_model->getForUser($this->user->id);
        }
        
        if($this->user->dob == '') {
            $data['dob'] = '';
        }

        $this->layout->setTitle(_l('heading_verification'))->view('user/verify', $data);
    }

    public function unique_email($email) {
        if ($this->user_model->uniqueEmail(strtolower($email)))
            return TRUE;

        $this->form_validation->set_message('unique_email', _l('email_in_use'));
        return FALSE;
    }

    public function check_date($date) {
        list($month, $day, $year) = explode('/', $date);

        if (!checkdate($month, $day, $year)) {
            $this->form_validation->set_message('check_date', _l('invalid_date'));
            return FALSE;
        }

        // TODO: Add age verification?
/*
        $offset  = time() - mktime(0, 0, 0, $month, $day, $year);
        $offset /= (3600 * 24 * 352);

        if ($offset < 18) {
            $this->form_validation->set_message('check_date', 'You must be at least 18 years old');
            return FALSE;
        }
*/
        return TRUE;
    }
    
    public  function support() {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error',  _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }
        
        $this->load->library('Freshdesk');
        $tickets = $this->freshdesk->send('/helpdesk/tickets.json?email='.$this->user->email.'&filter_name=all_tickets', 'GET');
        
        $data = array();
        $data['tickets'] = $tickets;
        $this->layout->setTitle(_l('heading_profile_settings'))->view('user/support', $data);
    }
    
    
    public function new_ticket() {
        
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error',  _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }
        
        if ($this->input->post()) {
            $this->form_validation->set_rules('subject', _l('subject'), 'required|max_length[100]');
            $this->form_validation->set_rules('description', _l('description'), 'required');
            //print_r($_FILES['file']);
            /*
            $this->load->library('aws_s3');
            $name = 'file';
            $file = $_FILES[$name];

            if ($file['error'] == 0) {
                $uid = generateRandomString(10, true);

                $config['upload_path']   = UPLOADPATH;
                $config['allowed_types'] = 'gif|jpg|jpeg|png|pdf';
                $config['max_size']      = '3000';
                $config['file_name']     = $uid;
                $config['remove_spaces'] = true;
                $config['overwrite']     = false;
                $config['max_width']     = '0';
                $config['max_height']    = '0';

                $this->load->library('upload', $config);

                if ($this->upload->do_upload($name)) {
                    $uploadData = $this->upload->data();

                    $fileData = array(
                        'userid'   => $this->user->id,
                        'uid'      => $uid,
                        'filename' => $file['name'],
                        'mime'     => $uploadData['file_type'],
                        'size'     => $file['size']
                    );

                    //@unlink($uploadData['full_path']);
                } else {
                    $data['errors'][] = array(
                        'file'    => $file['name'],
                        'message' => $this->upload->display_errors('', '')
                    );
                }
            }
            */
            
            if ($this->form_validation->run()) {
                $this->load->library('Freshdesk');
                $user = $this->user_model->get($this->userId);
                
                $data = array(
                    "helpdesk_ticket" => array(
                        "description" => $this->input->post('description'),
                        "subject" => $this->input->post('subject'),
                        "email" => $user->email,
                        "priority" => 1,
                        "status" => 2
                    ),
                    "cc_emails" => $this->freshdesk->getEmail()
                );
                
                /*
                if(isset($uploadData['full_path'])){
                    $data['helpdesk_ticket']['attachments'][]['resource'] = curl_file_create($uploadData['full_path'],$file['type'], $file['name']);
                    $this->freshdesk->send('/helpdesk/tickets.json', 'POST', $data, true);
                } else {
                    $this->freshdesk->send('/helpdesk/tickets.json', 'POST', $data);
                }
                */
                $this->freshdesk->send('/helpdesk/tickets.json', 'POST', $data);
                
                $this->session->set_flashdata('success', _l('create_ticket'));
                redirect('/support');
                
            }
        }
        
        $data['subject'] = array(
            'name'        => 'subject',
            'id'          => 'subject',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => '',
            'maxlength'   => '100',
            'rows'        => 2,
            'value'       => $this->form_validation->set_value('subject')
        );
        
        $data['description'] = array(
            'name'        => 'description',
            'id'          => 'description',
            'type'        => 'textarea',
            'class'       => 'form-control',
            'placeholder' => '',
            'rows'        => 5,
            'value'       => $this->form_validation->set_value('description')
        );
        
        $data['file'] = array(
            'name'        => 'file',
            'id'          => 'file',
            'type'        => 'file',
            'class'       => 'custom-file-input',
            'placeholder' => '',
            'value'       => $this->form_validation->set_value('file')
        );
        
        $this->layout->setTitle(_l('heading_profile_settings'))->view('user/new_ticket', $data);
    }
    
    public function view_ticket($ticketId) {
        
        $this->load->library('Freshdesk');
        
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error',  _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }
        
        if ($this->input->post()) {
            $this->form_validation->set_rules('description', _l('description'), 'required');
            if ($this->form_validation->run()) {
                
                $data = array(
                    "helpdesk_note" => array(
                        "body" => $this->input->post('description'),
                        "private" => false
                    )
                );
                
                $note = $this->freshdesk->send('/helpdesk/tickets/'.$ticketId.'/conversations/note.json', 'POST', $data);

                $this->session->set_flashdata('success', _l('create_note'));
                redirect('/support');
            }
        }
        
        
        $tickets = $this->freshdesk->send('/helpdesk/tickets/'.$ticketId.'.json', 'GET');
        $userFresh = $this->freshdesk->send('/contacts/'.$tickets->helpdesk_ticket->requester_id.'.json', 'GET');
        if($this->user->email != $userFresh->user->email){
            $this->session->set_flashdata('error',  _l('incorrect_tiket'));
            redirect('/support');
        }
        
        $data['displayId'] = $tickets->helpdesk_ticket->display_id;
        $data['subject'] = $tickets->helpdesk_ticket->subject;
        $data['ticket'] = $tickets->helpdesk_ticket->description;
        $data['created_at'] = $tickets->helpdesk_ticket->created_at;
        $data['notes'] = $tickets->helpdesk_ticket->notes;
        
        $data['description'] = array(
            'name'        => 'description',
            'id'          => 'description',
            'type'        => 'textarea',
            'class'       => 'form-control',
            'placeholder' => '',
            'rows'        => 5,
            'value'       => $this->form_validation->set_value('description')
        );
        
        $this->layout->setTitle(_l('heading_profile_settings'))->view('user/view_ticket', $data);
    }

    public function settings() {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error',  _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }
        
        //print_r($this->user);
        if ($this->input->post()) {
            if($this->input->post('password')){  
                $this->form_validation->set_rules('email',            _l('email_address'),      'xss_clean|valid_email|callback_unique_email');
                $this->form_validation->set_rules('password',         _l('l_password'),         'required');
                $this->form_validation->set_rules('new_password',     _l('l_new_password'),     '');
                $this->form_validation->set_rules('confirm_password', _l('l_confirm_password'), 'matches[new_password]');
                $this->form_validation->set_rules('pgpkey','Public key','');
                
    
                if ($this->form_validation->run()) {
                    $email       = $this->input->post('email');
                    $password    = $this->input->post('password');
                    $newPassword = $this->input->post('new_password');
    
                    $data = array();
    
                    // Check if the password that has been provided matches the current one
                    $passwordCheck = $this->api_security->hash($password, $this->user->salt);
                    if ($passwordCheck != $this->user->password)
                        $data['errors'] = _l('incorrect_password');
                    else {
                        if ($email)
                            $data['email'] = $email;
    
                        if ($newPassword) {
                            $salt = generateRandomString(20); // Reseed the salt to add more fuzinees
    
                            $data['salt'] = $salt;
                            $data['password'] = $this->api_security->hash($newPassword, $salt);
                        }
    
                        if (!count($data))
                            $data['errors'] = _l('no_changes_were_made');
                        else {
                            if ($user = $this->user_model->save($data, $this->userId)) {
                                // All good
                                if ($newPassword) {
                                    $this->session->set_flashdata('success', _l('settings_updated_logged_out'));
    
                                    redirect('/login');
                                }
                                else {
                                    $this->session->set_flashdata('success',  _l('settings_updated'));
    
                                    redirect('/settings');
                                }
                            }
                            else $data['errors'] = _l('error_updating');
                        }
                    }
                }
            } else {
                $this->load->library('aws_s3');
                $file = $_FILES['public_key'];
                if ($file['error'] == 0 && $file['size'] > 0 || $this->input->post('pgpkey')) {
                    
                    $this->load->library('Mandrilllibrary');
                    
                    $dataUpdate = array();
                    $dataUpdate['pgp_key'] = $this->input->post('pgpkey');
                    $status = $this->mandrilllibrary->send('/pgpValidKey', "POST", $dataUpdate);
                    if($status->status){
                        $this->user_model->update($this->userId, $dataUpdate);
                        $this->user = $this->user_model->getUser($this->userId);
                        //echo "!Empty files";
                        $this->session->set_flashdata('success',  _l('update_pgp_key'));
                        redirect('/settings');
                    } else {
                        $this->session->set_flashdata('error',  _l('incorrect_pgp_key'));
                        redirect('/settings');
                    }
                } else {
                    $this->session->set_flashdata('error',  _l('incorrect_pgp_key'));
                    redirect('/settings');
                } 
            }
        }

        $data['clientId'] = $this->userId;
        $data['pgp_support'] = 0;
        $data['pgp_key_text'] = null;
        $data['disabled_pgp_key'] = 'disabled="disabled"';
        if(isset($this->user->pgp_key) && $this->user->pgp_key != ''){
            $data['pgp_key_text'] = $this->user->pgp_key;
            $data['disabled_pgp_key'] = '';
        }
        
        if(isset($this->user->pgp_status) && $this->user->pgp_status == 1){
            $data['pgp_status'] = $this->user->pgp_status;
            
        }

        $data['email'] = array(
            'name'        => 'email',
            'id'          => 'email',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('email'),
            'class'       => 'form-control',
            'placeholder' => _l('enter_valid_email'),
            'autofocus'   => 'autofocus'
        );

        $data['password'] = array(
            'name'        => 'password',
            'id'          => 'password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('p_old_password')
        );

        $data['newPassword'] = array(
            'name'        => 'new_password',
            'id'          => 'new_password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('p_password')
        );

        $data['confirmPassword'] = array(
            'name'        => 'confirm_password',
            'id'          => 'confirm_password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('p_confirm_password')
        );

        $data['avatar'] = $this->gravatar->get_gravatar($this->user->email, null, 50, null, true);
        $data['twofa']  = isset($this->user->twofa_status) ? $this->user->twofa_status : '0';
        $data['twofa_type'] = isset($this->user->twofa_type) ? $this->user->twofa_type : '2fauth';
        //echo $data['twofa_type'];
        $data['twofauth_btn'] = array();
        $data['pmail_btn'] = array();
        $data['psms_btn'] = array();
        
        if($data['twofa'] == '1') {
            switch($data['twofa_type']){
                case '2fauth':
                    $data['twofauth_btn']['enable'] = 'btn-primary active';
                    $data['twofauth_btn']['disable'] = 'btn-default';
                    $data['twofauth_btn']['disabledOpt'] = '';
                    
                    $data['psmart_btn']['enable'] = 'btn-default';
                    $data['psmart_btn']['disable'] = 'btn-default';
                    $data['psmart_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    $data['pmail_btn']['enable'] = 'btn-default';
                    $data['pmail_btn']['disable'] = 'btn-default';
                    $data['pmail_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    
                    $data['psms_btn']['enable'] = 'btn-default';
                    $data['psms_btn']['disable'] = 'btn-default';
                    $data['psms_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    break;
                case 'psmart':
                    $data['twofauth_btn']['enable'] = 'btn-default';
                    $data['twofauth_btn']['disable'] = 'btn-default';
                    $data['twofauth_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    $data['psmart_btn']['enable'] = 'btn-primary active';
                    $data['psmart_btn']['disable'] = 'btn-default';
                    $data['psmart_btn']['disabledOpt'] = '';
                    
                    $data['pmail_btn']['enable'] = 'btn-default';
                    $data['pmail_btn']['disable'] = 'btn-default';
                    $data['pmail_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    $data['psms_btn']['enable'] = 'btn-default';
                    $data['psms_btn']['disable'] = 'btn-default';
                    $data['psms_btn']['disabledOpt'] = 'disabled="disabled"';
                    break;
                case 'pmail':
                    $data['twofauth_btn']['enable'] = 'btn-default';
                    $data['twofauth_btn']['disable'] = 'btn-default';
                    $data['twofauth_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    $data['psmart_btn']['enable'] = 'btn-default';
                    $data['psmart_btn']['disable'] = 'btn-default';
                    $data['psmart_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    $data['pmail_btn']['enable'] = 'btn-primary active';
                    $data['pmail_btn']['disable'] = 'btn-default';
                    $data['pmail_btn']['disabledOpt'] = '';
                    
                    $data['psms_btn']['enable'] = 'btn-default';
                    $data['psms_btn']['disable'] = 'btn-default';
                    $data['psms_btn']['disabledOpt'] = 'disabled="disabled"';
                    break;
                case 'psms':
                    $data['twofauth_btn']['enable'] = 'btn-default';
                    $data['twofauth_btn']['disable'] = 'btn-default';
                    $data['twofauth_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    $data['psmart_btn']['enable'] = 'btn-default';
                    $data['psmart_btn']['disable'] = 'btn-default';
                    $data['psmart_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    $data['pmail_btn']['enable'] = 'btn-default';
                    $data['pmail_btn']['disable'] = 'btn-default';
                    $data['pmail_btn']['disabledOpt'] = 'disabled="disabled"';
                    
                    $data['psms_btn']['enable'] = 'btn-primary active';
                    $data['psms_btn']['disable'] = 'btn-default';
                    $data['psms_btn']['disabledOpt'] = '';
                    
                    break;
            }
        } else {
            $data['twofauth_btn']['enable'] = 'btn-default';
            $data['twofauth_btn']['disable'] = 'btn-primary active';
            $data['twofauth_btn']['disabledOpt'] = '';
            
            $data['psmart_btn']['enable'] = 'btn-default';
            $data['psmart_btn']['disable'] = 'btn-primary active';
            $data['psmart_btn']['disabledOpt'] = '';
            
            $data['pmail_btn']['enable'] = 'btn-default';
            $data['pmail_btn']['disable'] = 'btn-primary active';
            $data['pmail_btn']['disabledOpt'] = '';
            
            $data['psms_btn']['enable'] = 'btn-default';
            $data['psms_btn']['disable'] = 'btn-primary active';
            $data['psms_btn']['disabledOpt'] = '';
        }

        $data['secure_withdrawals'] = isset($this->user->secure_withdrawals) ? $this->user->secure_withdrawals : '1';
        $data['pgpkey'] = array(
            'name'        => 'pgpkey',
            'id'          => 'pgpkey',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => '',
            'rows'        => 10,
            'value'       => $this->form_validation->set_value('pgpkey')
        );

        // Show the APIs
        $this->load->model('api_model');
        $userApis = $this->api_model->getAPIs($this->userId);

        foreach ($userApis as $id=>$api) {
            $data['api'][$id]['name']   = $api->name;
            $data['api'][$id]['secret'] = md5($api->secret);
        }

        $data['referralCode'] = $this->referral_model->getReferralCode($this->userId);

        $this->layout->setTitle(_l('heading_profile_settings'))->view('user/settings', $data);
    }

    public function two_factor_authentication($status = null, $type = null) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }
        
        
        $this->load->library('Protectimus');
        $api = $this->protectimus->getApi();
        

        switch ($status) {
            
            case 'enable':
                if ($this->user->twofa_status !== '0') {
                    $this->session->set_flashdata('error', _l('problem'));
                    redirect('/settings');
                }
                
                $data = array();
                
                switch ($type) {
                    case '2fauth':
                        $this->load->helper('GoogleAuthenticator');
                        $ga = new PHPGangsta_GoogleAuthenticator();
        
                        if ($this->input->post()) {
                            $secret = $this->input->post('two_factor_secret');
        
                            $this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
        
                            if ($this->form_validation->run()) {
                                // Ok set up the TWO FA for that user
                                $reset = generateRandomString();
        
                                $data = array(
                                    'twofa_status'       => '1',
                                    'twofa_secret'       => $secret,
                                    'twofa_reset'        => $reset,
                                    'twofa_type'         => '2fauth'
                                    //'secure_withdrawals' => '1'
                                );
        
                                $this->user_model->save($data, $this->userId);
        
                                // Log 2fa enabled
                                $this->event_model->add($this->userId, '2faon');
        
                                echo $this->layout->partialView('user/twofa/done_2fauth', $data);
        
                                return;
                            }
                        } else {
                            $secret = $ga->createsecret();
                        }
        
                        $width = 150;
                        $height = 150;
        
                        $qrCodeUrl = $ga->getQRCodeGoogleUrl($this->config->item('site_name'), $secret, $width, $height);
        
                        $data['secret'] = $secret;
                        $data['qrUrl']  = $qrCodeUrl;
        
                        $data['code'] = array(
                            'name'         => 'code',
                            'id'           => 'code',
                            'type'         => 'text',
                            'value'        => '',
                            'class'        => 'form-control',
                            'placeholder'  => '6-digit code',
                            'autocomplete' => 'off',
                            'autofocus'    => 'autofocus'
                        );
                        break;
                    case 'psmart':
        
                        if ($this->input->post()) {
                            
                            $secret = $this->input->post('psmart_secret');
                            $otp = $this->input->post('code');
                            $this->form_validation->set_rules('code', 'Code', 'required|min_length[6]|xss_clean|integer|exact_length[6]');
                            //$this->form_validation->set_rules('code', 'Code', 'xss_clean|integer|exact_length[6]|callback_validatePMail');
        
                            if ($this->form_validation->run()) {
                            // Ok set up the TWO FA for that user
                                
                                try {
                                $response = $api->addSoftwareToken(null, null, "PROTECTIMUS_SMART",
    					                                           $this->user->email, "Smart token ".$secret, 
                                                                   $secret, $otp, 6, "TOTP", null, null);
                                                                   print_r($response);
                                $res = $api->assignTokenToResource($this->protectimus->getResourceId(),null,$response->response->id);
                                
                               
                                    $data = array(
                                        'twofa_status'       => '1',
                                        'twofa_secret'       => $secret,
                                        'token_id'           => $response->response->id,
                                        'twofa_type'         => 'psmart'
                                        //'secure_withdrawals' => '1'
                                    );
            
                                    $this->user_model->save($data, $this->userId);
            
                                    // Log 2fa enabled
                                    $this->event_model->add($this->userId, '2faon');
            
                                    echo $this->layout->partialView('user/twofa/done_psmart', $data);
            
                                    return;
                                } catch(Exception $e){
                                    $this->form_validation->setError('code', _l('e_incorrect_code'));
                                }
                            }
                        } else {
                            $response = $api->getProtectimusSmartSecretKey();
                            $secret = $response->response->key;
                        }
        
                        $width = 150;
                        $height = 150;
                        
                        //$secret = "bbm6tcmgp5d2wjw297";
                        
                        $chl = urlencode("otpauth://totp/".urlencode("Token".$this->user->id)."?secret=".substr($secret, 0, 16)."&digits=6&counter=1");
                        
                        
                        
                        $qrCodeUrl = "https://chart.googleapis.com/chart?cht=qr&chs=$widthx$height&chl=$chl&choe=UTF-8";
        
                        $data['secret'] = $secret;
                        $data['qrUrl']  = $qrCodeUrl;
        
                        $data['code'] = array(
                            'name'         => 'code',
                            'id'           => 'code',
                            'type'         => 'text',
                            'value'        => '',
                            'class'        => 'form-control',
                            'placeholder'  => '6-digit code',
                            'autocomplete' => 'off',
                            'autofocus'    => 'autofocus'
                        );
                        break;
                    case 'pmail':
                        
                        if ($this->input->post()) {
                            
                            
                            $response = $api->addSoftwareToken(null, null, "MAIL",
					                                           $this->user->email, "Mail token ".$this->user->email, 
                                                               null, null, 6, null, null, null);
                            $res = $api->assignTokenToResource($this->protectimus->getResourceId(),null,$response->response->id);
                            $data = array(
                                'twofa_status'  => '1',
                                'twofa_type'    => 'pmail',
                                'token_id'      => $response->response->id
                            );
                            
                            $this->user_model->save($data, $this->userId);
                        
                            // Log 2fa enabled
                            $this->event_model->add($this->userId, '2faon');
                            
                            echo $this->layout->partialView('user/twofa/done_pmail', $data);
                            
                            return;
                        }
                        
                        $data['email'] = $this->user->email;
                        break;
                    case 'psms':
                        if ($this->input->post()) {
                            
                            $this->form_validation->set_rules('phone',   _l('phone'),   'required|numeric|max_length[30]');
                            
                            if ($this->form_validation->run()) {
                                try {
                                    $response = $api->addSoftwareToken(null, null, "SMS",
    					                                           $this->input->post('phone'), 
                                                                   "Sms token ".$this->input->post('phone'), 
                                                                   null, null, 6, null, null, null);
                                    $res = $api->assignTokenToResource($this->protectimus->getResourceId(),null,$response->response->id);
                                    
                                    $data = array(
                                        'twofa_status'  => '1',
                                        'twofa_type'    => 'psms',
                                       // 'token_id'      => $response->response->id
                                    );
                                    
                                    $this->user_model->save($data, $this->userId);
                                
                                    // Log 2fa enabled
                                    $this->event_model->add($this->userId, '2faon');
                                    
                                    echo $this->layout->partialView('user/twofa/done_psms', $data);
                                    return;
                                } catch(Exception\ProtectimusApiException $e) {
                                    //print_r($e);
                                    switch($e->errorCode){
                                        case 'INVALID_PARAMETER':
                                            $this->form_validation->setError('phone', _l('e_incorrect_phone'));
                                            break;
                                        case 'ACCESS_RESTRICTION':
                                            $this->form_validation->setError('phone', _l('e_limit_at_sms_token'));
                                            break;
                                    }
                                    //print_r($e);
                                }
                            }
                        }
                        
                        $details = $this->user_model->getDetails($this->userId);
                        
                        $data['phone'] = array(
                            'name'         => 'phone',
                            'id'           => 'phone',
                            'type'         => 'text',
                            'value'        => $this->form_validation->set_value('phone', isset($details->phone) ? $details->phone : ''),
                            'class'        => 'form-control',
                            'placeholder'  => '',
                            'autocomplete' => 'off',
                            'autofocus'    => 'autofocus'
                        );
                        
                        break;
                }

                if ($this->input->is_ajax_request()){
                    echo $this->layout->partialView('user/twofa/enable_'.$type, $data);
                } else {
                    $this->layout->setTitle(_l('heading_2fa'))->view('user/twofa/enable_'.$type, $data);
                } 
                    
                break;
                
            case 'disable':
                if ($this->user->twofa_status === '0') {
                    $this->session->set_flashdata('error', _l('problem'));
                    redirect('/settings');
                }
                
                switch ($type) {
                    case '2fauth':
                        if ($this->input->post()) {
                            $data = array(
                                'twofa_status'       => '0',
                                'twofa_secret'       => '',
                                'twofa_reset'        => '',
                                'secure_withdrawals' => '0',
                                'twofa_type'    => '2fauth'
                            );
        
                            $this->user_model->save($data, $this->userId);
        
                            $this->session->set_flashdata('success', _l('2fa_disabled'));
        
                            // Log 2fa enabled
                            $this->event_model->add($this->userId, '2faoff');
        
                            redirect('/settings', 'refresh');
                        }
                        break;
                    case 'psmart':
                        if ($this->input->post()) {
                            
                            $res = $api->unassignTokenFromResource($this->protectimus->getResourceId(),
                                                                    null,$this->user->token_id);
                            $res = $api->deleteToken($this->user->token_id);
                            
                            
                            $data = array(
                                'twofa_status'       => '0',
                                'twofa_type'         => 'pmail'
                            );
        
                            $this->user_model->save($data, $this->userId);
        
                            $this->session->set_flashdata('success', _l('protectimus_psmart_disabled'));
        
                            // Log 2fa enabled
                            $this->event_model->add($this->userId, '2faoff');
        
                            redirect('/settings', 'refresh');
                        }
                        break;
                    case 'pmail':
                        if ($this->input->post()) {
                            
                            $res = $api->unassignTokenFromResource($this->protectimus->getResourceId(),
                                                                    null,$this->user->token_id);
                            $res = $api->deleteToken($this->user->token_id);
                            
                            
                            $data = array(
                                'twofa_status'       => '0',
                                'twofa_type'         => 'pmail'
                            );
        
                            $this->user_model->save($data, $this->userId);
        
                            $this->session->set_flashdata('success', _l('protectimus_pmail_disabled'));
        
                            // Log 2fa enabled
                            $this->event_model->add($this->userId, '2faoff');
        
                            redirect('/settings', 'refresh');
                        }
                        break;
                    case 'psms':
                        if ($this->input->post()) {
                            $res = $api->unassignTokenFromResource($this->protectimus->getResourceId(),
                                                                    null,$this->user->token_id);
                            $res = $api->deleteToken($this->user->token_id);
                            
                            $data = array(
                                'twofa_status'       => '0',
                                'twofa_type'         => 'psms'
                            );
        
                            $this->user_model->save($data, $this->userId);
        
                            $this->session->set_flashdata('success', _l('protectimus_psms_disabled'));
        
                            // Log 2fa enabled
                            $this->event_model->add($this->userId, '2faoff');
        
                            redirect('/settings', 'refresh');
                        }
                        break;
                }


                if ($this->input->is_ajax_request())
                    echo $this->layout->partialView('user/twofa/disable_'.$type);
                else $this->layout->setTitle(_l('heading_2fa'))->view('user/twofa/disable_'.$type);

                break;
        }
    }

    public function two_factor_withdrawals() {
        if ($this->user !== 'guest' && $this->input->post() && $this->input->is_ajax_request()) {
            $enabled = $this->input->post('toggle');

            if ($this->user->twofa_status !== '0' && $this->user->secure_withdrawals != $enabled) {
                $data = array(
                    'secure_withdrawals' => $enabled
                );

                $this->user_model->save($data, $this->userId);

                // Log 2fa enabled
                $this->event_model->add($this->userId, $enabled ? '2fawithdrawalon' : '2fawithdrawaloff');
            }
        }
    }

    public function authenticate() {
        
        // Let's check they still actually have a session!
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login');
        }
        
        $twoFASecured = $this->user->twofa_status == '1';
        

        if ($this->input->post() && count($this->input->post()) > 0) {
            if($this->user->twofa_status){
                if(property_exists($this->user, 'twofa_type')){
                    switch($this->user->twofa_type){
                        case '2fauth':
                            $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                            break;
                        case 'psmart':
                            $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validatePMail');
                            break;
                        case 'pmail':
                            $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validatePMail');
                            break;
                        case 'psms':
                            $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validatePMail');
                            break;
                    }
                } else {
                    $this->form_validation->set_rules('code', 'Code', 'required|xss_clean|integer|exact_length[6]|callback_validateTwoFACode');
                }
            } else {
                 $this->form_validation->set_rules('code', 'Code', 'required|callback_valid_pin');
            }

            if ($this->form_validation->run()) {
                // Update the session and carry on
                if ($this->user_model->authenticateSession()) {
                    $redirect = $this->input->get('redirect');
                    if (!$redirect)
                        $redirect = $this->config->item('default_redirect');

                    $this->session->set_flashdata('success', _l('you_are_logged_in'));
                    redirect($redirect, 'redirect');
                }
                else {
                    $this->session->set_flashdata('error', _l('2fa_problem'));
                    redirect('/', 'redirect');
                }
            }
        } else {
            if($this->user->twofa_status){
                if($this->user->twofa_type == 'pmail' || $this->user->twofa_type == 'psms') {
                    $data['success'] = _l('send_otp');
                    $this->user_model->sendOtp($this->userId);
                }
            }
        }

        $data['code'] = array(
            'name'         => 'code',
            'id'           => 'code',
            'type'         => 'text',
            'value'        => '',
            'class'        => 'form-control',
            'placeholder'  => '',
            'autocomplete' => 'off',
            'autofocus'    => 'autofocus'
        );
        
        
        if ($twoFASecured) {
            
            switch($this->user->twofa_type){
                case '2fauth':
                    $data['twofaType'] = 0;
                    break;
                case 'psmart':
                    $data['twofaType'] = 4;
                    break;
                case 'pmail':
                    $data['twofaType'] = 1;
                    //$this->user_model->sendOtp($this->userId);
                    break;
                case 'psms':
                    $data['twofaType'] = 2;
                    //$this->user_model->sendOtp($this->userId);
                    break;
            }
        } else {
            $data['twofaType'] = 5;
        }
        
        $data['user']     = $this->user;

        $this->layout->setTitle(_l('heading_2fa'))->view('user/twofa/authenticate', $data);
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
            $this->event_model->add($this->userId,'2faproblem');
            $this->form_validation->set_message('validateTwoFACode', _l('e_incorrect'));
            return false;
        }

        return true;
    }
    
    public function validatePMail($code, $str = null) {
        
        $this->load->library('Protectimus');
        $api = $this->protectimus->getApi();
        
        $response = $api->authenticateToken($this->protectimus->getResourceId(), $this->user->token_id, $code, null);
        $authenticationResult = $response->response->result;
        
        if(!$authenticationResult || $authenticationResult == '') {
            $this->form_validation->set_message('validatePMail', _l('e_incorrect_code'));
            return false;
        }
        
        return true;
    }

    public function api($action = '', $code = null) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        $userId = $this->userId;

        $this->load->model('api_model');

        if ($action == 'del') {
            if ($this->api_model->deleteApi($userId, $code))
                $this->session->set_flashdata('success', 'Your API was deleted');
            else $this->session->set_flashdata('error', 'There was an issue deleting your API. Please contact Customer Support for assistance.');

            redirect('/settings', 'refresh');
        }
        else if ($action == 'upd') {
            $data['secret'] = generateRandomString();
            if ($this->api_model->updateApi($userId, $code, $data)) {
                $data['secret'] = md5($data['secret']);

                echo json_encode($data);
            }
        }
    }

    public function api_edit($code = null) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        $userId = $this->user->id;
        $this->load->model('api_model');

        if (!$code && $this->api_model->countAPIs($userId) > 2) {
            // Not allowed more than 3 APIs
            $this->session->set_flashdata('error', 'Sorry but you are only allowed 3 APIs at any given time');
            redirect('/settings', 'refresh');
        }

        $apiData = $code ? $this->api_model->getApiFromKey($code) : null;

        if ($this->input->post() && count($this->input->post()) > 0){
            $this->form_validation->set_rules('name', _l('api_name_2'), 'required|xss_clean|alpha_dash_space|max_length[30]');
            if (!$code)
                $this->form_validation->set_rules('secret',   _l('api_secret'), 'required|xss_clean');
            $this->form_validation->set_rules('withdraw', 'Withdrawal Address', 'callback_valid_bitcoin_address');

            if ($this->form_validation->run()) {
                $name     = $this->input->post('name');
                $secret   = $this->input->post('secret');
                $withdraw = $this->input->post('withdraw');

                $data = array(
                    'name'               => $name,
                    'withdrawal_address' => $withdraw
                );

                if ($secret)
                    $data['secret'] = $secret;

                if (!$code) {
                    $this->api_model->addApi($userId, $data);

                    $this->session->set_flashdata('success', 'The API <strong>' . $name . '</strong> was successfully created');
                    redirect('/settings', 'refresh');
                }
                else {
                    $this->api_model->updateApi($userId, $code, $data);

                    $this->session->set_flashdata('success', 'The API <strong>' . $name . '</strong> was successfully updated');
                    redirect('/settings', 'refresh');
                }
            }
        }

        $data['name'] = array(
            'name'        => 'name',
            'id'          => 'name',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('name', $code ? $apiData->name : ''),
            'class'       => 'form-control',
            'placeholder' => _l('p_api_name')
        );

        $data['secret'] = array(
            'name'        => 'secret',
            'id'          => 'secret',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('secret', $code ? '' : generateRandomString()),
            'class'       => 'form-control',
            'placeholder' => $code ? 'Leave blank if unchanged' : _l('p_api_secret')
        );

        $data['withdraw'] = array(
            'name'        => 'withdraw',
            'id'          => 'withdraw',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('withdraw', $code && isset($apiData->withdrawal_address) ? $apiData->withdrawal_address : ''),
            'class'       => 'form-control',
            'placeholder' => 'All withdrawals will be sent to this address if set'
        );

        $data['api']  = $apiData;
        $data['code'] = $code;

        $this->layout->setTitle('API Setup')->view('user/api/setup', $data);
    }

    public function fund($depositId) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        $this->load->model('deposit_model');

        $deposit = $this->deposit_model->getFull($depositId);

        if ($deposit->_id && $deposit->client == $this->userId && $deposit->status != 'pending') {
            // All good, this order is from that user
            $outcome = $deposit->status == 'complete' ? _l('successful') : _l('unsuccessful');

            $data['outcome'] = $outcome;
            $data['deposit'] = $deposit;

            $this->layout->setTitle(_l('heading_transaction').' ' . $outcome)->view('user/transaction', $data);
        }
        else {
            $this->session->set_flashdata('error', _l('sorry_problem') );
            redirect($this->config->item('default_redirect'));
        }
    }

    public function autosell() {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        $addresses = $this->bitcoin_model->getAutoSellAddress($this->userId);

        if (!$addresses)
            $addresses = $this->bitcoin_model->setAutoSellAddress($this->userId);

        $data['addresses'] = $addresses;

        $this->layout->setTitle('Bitcoin Auto-Sell')->view('user/autosell', $data);
    }

    public function referral() {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }
        $this->load->model('referral_model');
        $data['referralCode'] = $this->referral_model->getReferralCode($this->userId);
        $data['hasReferrals'] = $this->referral_model->getSimpleCount($this->userId) > 0;
        $this->layout->setTitle('Referral Program')->view('user/referral/index', $data);
    }

    public function my_referrals() {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }
        $this->load->model('referral_model');
        $data['summary']      = $this->referral_model->getSummary($this->userId);
        $data['currencies']   = $this->meta_model->getAllCurrencies();
        $this->layout->setTitle('My Referrals')->view('user/referral/my_referrals', $data);
    }

    public function merchant($action = '', $code = null) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error', _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        $this->load->model('merchant_model');

        if ($action == '') {
            $userStores = $this->merchant_model->getStores($this->userId);

            $data = array();
            foreach ($userStores as $id=>$store) {
                $data['stores'][$id]['name']   = $store->name;
                $data['stores'][$id]['secret'] = $store->secret;
            }

            $this->layout->setTitle('Merchant')->view('user/merchant/index', $data);
        }
        else if ($action == 'del') {
            if ($this->merchant_model->deleteStore($this->userId, $code))
                $this->session->set_flashdata('success', 'Your Store was deleted');
            else $this->session->set_flashdata('error', 'There was an issue deleting your Store. Please contact Customer Support for assistance.');

            redirect('/merchant_setup', 'refresh');
        }
        else {
            $storeData = $code ? $this->merchant_model->get($code) : null;

            if ($storeData && $storeData->client != $this->userId) {
                $this->session->set_flashdata('error', 'There was a problem with your request');
                redirect('/merchant_setup', 'refresh');
            }

            if ($this->input->post() && count($this->input->post()) > 0){
                $this->form_validation->set_rules('name', 'Store Name', 'required|xss_clean|alpha_dash_space');
                if (!$storeData)
                    $this->form_validation->set_rules('secret', 'Store Secret', 'required|xss_clean');

                $this->form_validation->set_rules('currency', 'Payout Currency',    'xss_clean|callback_valid_currency');
                $this->form_validation->set_rules('callback', 'Callback URL',       'xss_clean|url');
                $this->form_validation->set_rules('method',   'Callback Method',    'xss_clean|callback_valid_method');
                $this->form_validation->set_rules('cancel',   'Cancel URL',         'xss_clean|url');
                $this->form_validation->set_rules('return',   'Return URL',         'xss_clean|url');
                $this->form_validation->set_rules('email',    'Notification Email', 'xss_clean|valid_email');

                if ($this->form_validation->run()) {
                    $name     = $this->input->post('name');
                    $secret   = $this->input->post('secret');
                    $currency = $this->input->post('currency');
                    $callback = $this->input->post('callback');
                    $method   = $this->input->post('method');
                    $cancel   = $this->input->post('cancel');
                    $return   = $this->input->post('return');
                    $email    = $this->input->post('email');

                    if ($storeData) {
                        $data = array(
                            'name'     => $name,
                            'currency' => $currency,
                            'callback' => $callback,
                            'method'   => $method,
                            'cancel'   => $cancel,
                            'return'   => $return,
                            'email'    => $email
                        );

                        if ($secret)
                            $data['secret'] = $secret;

                        $this->merchant_model->updateStore($this->userId, $code, $data);
                        $this->session->set_flashdata('success', 'The Store <strong>' . $name . '</strong> was successfully updated');
                    }
                    else {
                        $data = array(
                            'name'     => $name,
                            'secret'   => $secret,
                            'currency' => $currency,
                            'callback' => $callback,
                            'method'   => $method,
                            'cancel'   => $cancel,
                            'return'   => $return,
                            'email'    => $email
                        );

                        $this->merchant_model->addStore($this->userId, $data);
                        $this->session->set_flashdata('success', 'The Store <strong>' . $name . '</strong> was successfully created');
                    }

                    redirect('/merchant_setup');
                }
            }

            $data['name'] = array(
                'name'        => 'name',
                'id'          => 'name',
                'type'        => 'text',
                'value'       => $this->form_validation->set_value('name', $storeData ? $storeData->name : ''),
                'class'       => 'form-control',
                'placeholder' => 'Store Name'
            );

            $data['secret'] = array(
                'name'        => 'secret',
                'id'          => 'secret',
                'type'        => 'text',
                'value'       => $this->form_validation->set_value('secret', $storeData ? '' : generateRandomString()),
                'class'       => 'form-control',
                'placeholder' => $storeData ? 'Leave blank to keep current Store Secret' : 'Store Secret'
            );

            $currencies = array();
            foreach ($this->meta_model->getAllCurrencies() as $currency) {
                if ($currency == 'xau') continue;
                $currencies[$currency] = code2Name($currency);
            }

            $data['currencies'] = $currencies;

            $currency = $this->input->post('currency');
            if (!$currency)
                $currency = $this->config->item('default_minor');

            $data['currency']   = $storeData ? $storeData->currency : $currency;

            $data['callback'] = array(
                'name'        => 'callback',
                'id'          => 'callback',
                'type'        => 'text',
                'value'       => $this->form_validation->set_value('callback', $storeData ? $storeData->callback : ''),
                'class'       => 'form-control',
                'placeholder' => 'Callback URL'
            );

            $data['method'] = $storeData ? $storeData->method : 'post';

            $data['cancel'] = array(
                'name'        => 'cancel',
                'id'          => 'cancel',
                'type'        => 'text',
                'value'       => $this->form_validation->set_value('cancel', $storeData ? $storeData->cancel : ''),
                'class'       => 'form-control',
                'placeholder' => 'Cancel URL - users will be redirected there if payment is canceled or failed'
            );

            $data['return'] = array(
                'name'        => 'return',
                'id'          => 'return',
                'type'        => 'text',
                'value'       => $this->form_validation->set_value('return', $storeData ? $storeData->return : ''),
                'class'       => 'form-control',
                'placeholder' => 'Return URL - users will be redirected there when payment is complete'
            );

            $data['email'] = array(
                'name'        => 'email',
                'id'          => 'email',
                'type'        => 'text',
                'value'       => $this->form_validation->set_value('email', $storeData ? $storeData->email : ''),
                'class'       => 'form-control',
                'placeholder' => 'A Payment Notification will be sent to this address'
            );

            $data['store'] = $storeData;
            $data['code']  = $code;

            $this->layout->setTitle('Merchant')->view('user/merchant/setup', $data);
        }
    }

    public function orders($page = 1, $perPage = 10) {
        $orders = $this->order_model->getForUser($this->userId);
        $result = $this->order_model->getSubsetForUser($orders, $page, $perPage);
        $count = count($orders);
        
        $data = array(
            'count' => $count,
            'orders'=> $result,
            'pages' => generateNewPagination('/orders', $count, $page, $perPage)
        );

        $this->layout->setTitle('Open Orders')->view('user/orders', $data);
    }

    public function valid_method($method) {
        $methods = array('post', 'get');

        if (in_array($method, $methods) === FALSE) {
            $this->form_validation->set_message('valid_method', _l('e_incorrect'));
            return false;
        }

        return true;
    }

    public function valid_currency($currency) {
        $currencies = $this->meta_model->getAllCurrencies();

        if (in_array($currency, $currencies) === FALSE) {
            $this->form_validation->set_message('valid_currency', _l('e_incorrect'));
            return false;
        }

        return true;
    }

    public function valid_type($type) {
        if (in_array($type, array('json', 'html')) === FALSE) {
            $this->form_validation->set_message('valid_type', _l('e_incorrect'));
            return false;
        }

        return true;
    }

    public function valid_bitcoin_address($address) {
        if ($address != '') {
            if (!preg_match ('/^[123][1-9A-HJ-NP-Za-km-z]{20,40}$/', $address)) {
                $this->form_validation->set_message('valid_bitcoin_address', _l('e_appears_invalid'));
                return false;
            }
        }

        return true;
    }
}
