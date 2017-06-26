<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main extends MY_Controller {

    public function __construct() {
        parent::__construct();
        show_404();
        $this->load->library('captcha');
    }
    
    public function error(){
        show_404();
    }

	public function index($major = null, $minor = null) {
        $data = array();
        
        if ($this->input->post()){
            /*
            $phase = 1;
            
            $this->form_validation->set_rules('email',      _l('email_address'), 'required|xss_clean|valid_email|callback_unique_email');
            $this->form_validation->set_rules('first_name', _l('first_name'),    'required|xss_clean|alpha_dash_space');
            $this->form_validation->set_rules('last_name',  _l('last_name'),     'required|xss_clean|alpha_dash_space');
			$this->form_validation->set_rules('country',    _l('country'),       'required|xss_clean|valid_country_no_us');
            $this->form_validation->set_rules('password',   _l('l_password'),         'required');
            $this->form_validation->set_rules('pin',        _l('l_transaction_pin'),  'required|integer');
            
            if ($this->form_validation->run()) {
                $userData = array(
                    'email'      => $this->input->post('email'),
                    'first_name' => $this->input->post('first_name'),
                    'last_name'  => $this->input->post('last_name'),
                    'country'    => $this->input->post('country'),
                    'language'   => $this->config->item('default_lang')
                );

                if ($userId = $this->user_model->save($userData, null, $phase)) {
                    $data['userId'] = $userId;
                    $phase = 2;
                    
                    $user = $this->user_model->get($userId);
                    
                    $salt     = generateRandomString(20);
                    $password = $this->input->post('password');
                    
                    $tempPassword = $this->api_security->hash($userId, $password);
                    $userDataPhase2 = array(
                        'salt'     => $salt,
                        'password' => $this->api_security->hash($tempPassword, $salt),
                        'pin'      => sha1($this->input->post('pin')),
                        'active'   => 1
                    );
                    
                    if ($this->user_model->save($userDataPhase2, $userId, $phase)) {
                        // This block is used to grab a new btc address for that user if bitcoind is enabled
                        $status = $this->bitcoin_model->getStatus('bitcoind');
                        if ($status != 'disabled') {
                            $this->load->library('easybitcoin');
                            $this->easybitcoin->getaccountaddress('user:' . $userId);
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
                        
                        //$resultRender = $this->mandrilllibrary->replaceParams($templateContent, $mergeVars);
                        
                        $resultRender = $api->templates->render($name, $templateContent, $mergeVars);
                        
                        
                        $htmlContent = $resultRender['html'];
                        $pgpData = array();
                        $pgpData['content'] = $resultRender['html'];
                        if(isset($user->pgp_status) && $user->pgp_status == 1) {
                            $pgpData['key'] = $user->pgp_key;
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
                            'from_email' => 'support@whitebarter.com',
                            'from_name' => 'whitebarter',
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
                else $data['errors'] = _l('unexpected_error');
            }
            */
            $data['clientId'] = $this->input->post('userId');
            $this->layout->setTitle(_l('registration_complete'))->view('user/register/complete', $data);
            return;
        }

        $books = $this->meta_model->getBooks();

//        if (!$major) {
//            if (!$major = $this->session->userdata('major')) {
//                $major = $this->config->item('default_major');
//            }
//        }
//
//        if (!$minor) {
//            if (!$minor = $this->session->userdata('minor')) {
//                $minor = $this->config->item('default_minor');
//            }
//        }
//
//        $this->session->set_userdata(array(
//            'major' => $major,
//            'minor' => $minor
//        ));

        $major = $this->config->item('default_major');
        $minor = $this->config->item('default_minor');

        $book = $major . '_' . $minor;

        $data['minor'] = $minor;
        $data['major'] = $major;

        if (!isset($books[$book])) show_404();

        $this->load->model('order_model');

        $data['trades'] = $this->trade_model->getTrades($major . '_' . $minor, 10);
        $data['sell']   = $this->order_model->getOrders('orders:sell:' . $major . '_' . $minor, 10);
        $data['buy']    = $this->order_model->getOrders('orders:buy:' . $major . '_' . $minor, 10);

        $data['user']  = $this->user;
        $data['books'] = $books;
        $data['book']  = $book;
        
        $data['email'] = array(
            'name'        => 'email',
            'id'          => 'email',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('email'),
            'class'       => 'form-control col-md-12',
            'placeholder' => 'Email',
            'autofocus'   => 'autofocus'
        );

        $data['first_name'] = array(
            'name'        => 'first_name',
            'id'          => 'first_name',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('first_name'),
            'class'       => 'form-control',
            'placeholder' => 'First name'
        );

        $data['last_name'] = array(
            'name'        => 'last_name',
            'id'          => 'last_name',
            'type'        => 'text',
            'value'       => $this->form_validation->set_value('last_name'),
            'class'       => 'form-control',
            'placeholder' => 'Last name'
        );
        
        $data['password'] = array(
            'name'        => 'password',
            'id'          => 'password',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('p_password'),
            'autofocus'   => 'autofocus'
        );
        
        $data['pin'] = array(
            'name'        => 'pin',
            'id'          => 'pin',
            'type'        => 'password',
            'class'       => 'form-control',
            'placeholder' => _l('transaction_pin_digits_only')
        );
        
        $data['countries'] = countriesLocalised($this->language);

		$this->layout->setTitle(_l('menu_home'))->view('home', $data);
	}

	public function about() {
		$this->layout->setTitle(_l('menu_about'))->view('about');
	}

    public function fees() {
        $data = array(
            'fees' => $this->trade_model->getFeeStructure()
        );

        $this->layout->setTitle(_l('menu_about'))->view('fees', $data);
    }

    public function terms() {
        $this->layout->setTitle(_l('menu_disclaimer'))->view('terms');
    }

    public function privacy() {
        $this->layout->setTitle(_l('menu_privacy'))->view('privacy');
    }

    public function refund() {
        $this->layout->setTitle(_l('menu_refund'))->view('refund');
    }

	public function faq() {
		$this->layout->setTitle(_l('menu_faq'))->view('faq');
	}

	public function support() {
		$this->layout->setTitle(_l('menu_support'))->view('support');
	}

	public function intro() {
		$this->layout->setTitle(_l('menu_intro'))->view('intro');
	}

    public function navigation(){
        echo widget::run('navigation');
    }

    public function tradebalance(){
        echo widget::run('tradebalance');
    }

    public function stats($book) {
        echo widget::run('stats', $book);
    }

    public function hpstats($book) {
        echo widget::run('hpstats', $book);
    }

    public function marketstats() {
        echo widget::run('marketStats');
    }

    public function userbalance($currency, $type = '') {
        echo widget::run('userbalance', $currency, $type);
    }

    public function dashhistory() {
        echo widget::run('dashhistory');
    }

    public function api_info($v='') {
        $this->layout->setTitle(_l('menu_api'))->view('api'.$v);
    }

    public function merchant_info() {
        $this->layout->setTitle('Merchant Platform Information')->view('merchant_info');
    }

    public function merchant_setup() {
        $this->layout->setTitle('Merchant Platform Setup Information')->view('merchant_setup');
    }

    public function help() {
        $this->layout->setTitle('Help')->view('help');
    }

    public function news() {
        $this->layout->setTitle('News')->view('news');
    }

    public function state($country) {
        header('Content-Type: application/json');

        echo json_encode(states($country));
    }
    
    public function verify_email($email = null, $hash = null){
        if(is_null($email) || is_null($hash) || md5($email.":::".date("Y-m-d")) != $hash){
            $data['class'] = 'bs-callout-danger';
            $data['text'] = 'Incorrect or outdated link to verify the email';
        } else {
            $email = urldecode($email);
            $user = $this->user_model->findUserByEmail($email);
            $userData['isVerifyEmail'] = 1;
            $this->user_model->update($user->id, $userData);
            $data['class'] = 'bs-callout-info';
            $data['text'] = 'Your email was successfully verified';
        }
        
        $this->layout->setTitle('Verify email')->view('verify_email', $data);
    }
    
    public function unique_email($email) {
        if ($this->user_model->uniqueEmail(strtolower($email)))
            return TRUE;

        $this->form_validation->set_message('unique_email', _l('email_in_use'));
        return FALSE;
    }
}