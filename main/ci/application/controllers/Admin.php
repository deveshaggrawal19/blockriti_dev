<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define("SUPERADMIN", 0);
define("ADMIN", 1);
 
class Admin extends MY_Controller
{
    private $allowedDeposits;
    private $allowedWithdrawals;
    public $access_level;

    public function __construct() {
        parent::__construct();

        $allowed = $this->config->item('admin_emails');
        
        if ($this->user === 'guest' || (in_array($this->user->email, $allowed) === false && !$this->user->isadmin)) {
            show_404('', TRUE);
            return;
        } else {
            if(in_array($this->user->email, $allowed) === true) {
                $this->access_level = SUPERADMIN;
            } else {
                $this->access_level = ADMIN;
            }
        }
        
        $this->layout->setLayout('admin');

        $this->load->driver('cache', array('adapter' => 'file'));

        $this->load->model('admin_model');
        $this->load->model('bitcoin_model');
        $this->load->model('note_model');
        $this->load->model('deposit_model');
        $this->load->model('referral_model');
        $this->load->model('withdrawal_model');
        $this->load->model('user_document_model');
        $this->load->model('logging_model');
        $this->load->model('event_model');

//todo what is tech mode and remove ben and hannin 
        //todo conduct mass search for ben and hanin
        
        if (in_array($this->user->email, array('moe@taurusexchange.com'))) {
            $this->techmode = true;
            $this->logprefixes = $this->logging_model->getAllPrefixes();
        }
        $this->outstandingWithdrawals = $this->withdrawal_model->getCount('pending');
        $this->pendingDocuments       = $this->user_document_model->getPendingCount();

        $this->allowedDeposits    = $this->config->item('deposit_methods');
        $this->allowedWithdrawals = $this->config->item('withdrawal_methods');

        $this->meta_model->getAllCurrencies();
        $this->meta_model->getBooks();

        //$this->output->enable_profiler(true);
    }

	public function index() {
        $books = $this->meta_model->books;

        foreach ($books as $bookId=>$book) {
            $data['lastPrice'][$book[1]] = $this->trade_model->getLastTradePrice($bookId);
            $data['volume'][$book[1]]    = $this->trade_model->getRollingVolume($bookId);
        }

        $data['books']      = $books;
        $data['users']      = $this->user_model->getCountOnly();
        $data['pendingD']   = $this->deposit_model->getCount('pending');
        $data['pendingW']   = $this->withdrawal_model->getCount('pending');
        $data['balances']   = $this->user_balance_model->getTotalBalances();
        $data['currencies'] = $this->meta_model->currencies;
        $data['documents']  = $this->pendingDocuments;

        $data['tradeFees']   = $this->admin_model->getFees('trades');

        $data['depositFees'] = $this->admin_model->getAmounts('deposits');
        $data['withdrawalFees'] = $this->admin_model->getFees('withdrawals');
        /*
        //$this->load->library('easybitcoin');
        $data['withdrawals_balance'] = $this->easybitcoin->getbalance('withdrawals');
        $data['withdrawals_address'] = $this->easybitcoin->getaccountaddress('withdrawals');
        */
        
        /*
        $this->load->library('coinkitelibrary');
        $accounts = $this->coinkitelibrary->send("/v1/my/accounts");
        $listAccounts = array();
        foreach($accounts->results as $account) {
            sleep(3);
            $listAccounts[] = $this->coinkitelibrary->send("/v1/account/".$account->CK_refnum);
        }
        */
        
        $this->load->library('BitGo');
        $listAccounts = $this->bitgo->send("/get_wallet");
        
        $data['withdrawals_account'] = $listAccounts;

        $this->layout->view('admin/index', $data);
	}

    public function users($page = 1, $perPage = 30) {
        $data['section'] = 'users';
        
        $filter = $this->input->get_post('search');

        $count = $this->user_model->getCount($filter ? $filter : 'all');
        $data['count'] = $count;
        $data['users'] = $this->user_model->getSubset($page, $perPage);
        $url = $filter ? '/admin/users/%d/%d?search=' . $filter : '/admin/users';
        $data['pages'] = generateNewPagination($url, $count, $page, $perPage, true);

        $currencies = $this->meta_model->currencies;
        $data['currencies'] = $currencies;
        $data['filter']     = $filter;
        $data['controller'] = $this;

        $this->layout->view('admin/users/index', $data);
    }

    public function usersCSV() {

        $count = $this->user_model->getCount();
        $users = $this->user_model->getSubset(1, 1000000);
        $currencies = $this->meta_model->currencies;

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=users-".date("dmy").".csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '"UID","Firstname","Lastname","Email","Verified","Created","Last Seen","Language"';
        foreach ($currencies as $currency) {
            echo ',"'.strtoupper($currency).'"';
        }
        echo ',"Total Trades","Last Mn Volume"';
        echo "\n";
        foreach ($users as $u) {
            $count = $this->trade_model->getCountForUser($u->id);

            $rollingVolume = $this->trade_model->getUserVolume($u->id);

            echo '"'.$u->id.'","'.$u->first_name.'","'.$u->last_name.'","'.$u->email.'","'.(($u->verified == 1) ? 'Yes' : 'No').'","'.Date('Y-m-d H:i:s', $u->_created / 1000).'","'.($u->_lastseen ? Date('Y-m-d H:i:s', $u->_lastseen / 1000) : '').'","'.$u->language.'"';
            foreach ($currencies as $currency) {
                echo ',"'.$u->balances->{$currency . '_available'}.'"';
            }
            echo ',"'.$count.'","'.$rollingVolume.'"';
            echo "\n";
        }
    }

    public function user($userId) {
        $user = $this->user_model->getUser($userId, false);
        $countReferrals = $this->referral_model->getCountReferals($userId);
        $defaultCountry = $this->config->item('default_country');

        // Dirty hacks
        if ($this->input->post('ban_interac')) {
            $this->admin_model->userInteracPermission($userId, 'ban');
            redirect('/admin/user/' . $userId, 'refresh');
        }
        else if ($this->input->post('unban_interac')) {
            $this->admin_model->userInteracPermission($userId, 'unban');
            redirect('/admin/user/' . $userId, 'refresh');
        }

        if ($this->input->post('suspend')) {
            $this->user_model->update($userId, array('suspended' => milliseconds()));
            redirect('/admin/user/' . $userId, 'refresh');
        }
        else if ($this->input->post('unsuspend')) {
            $this->user_model->update($userId, array('suspended' => 0));
            redirect('/admin/user/' . $userId, 'refresh');
        }

        if ($this->input->post('prevent_auto_withdrawals')) {
            $this->user_model->update($userId, array('auto_withdrawals' => 'off'));
            redirect('/admin/user/' . $userId, 'refresh');
        }
        else if ($this->input->post('allow_auto_withdrawals')) {
            $this->user_model->update($userId, array('auto_withdrawals' => 'on'));
            redirect('/admin/user/' . $userId, 'refresh');
        }

        $this->form_validation->set_rules('first_name', 'First Name',    'required');
        $this->form_validation->set_rules('last_name',  'Last Name',     'required');
        $this->form_validation->set_rules('email',      'Email Address', 'required|valid_email');

        if ($this->form_validation->run() == true)
        {
            $data = array(
                'first_name' => $this->input->post('first_name'),
                'last_name'  => $this->input->post('last_name'),
                'email'      => $this->input->post('email'),
                'dob'        => $this->input->post('dob'),
                'active'     => $this->input->post('active') ? 1 : 0,
                'isadmin'    => $this->input->post('isadmin') ? 1 : 0,
                'isVerifyEmail' => $this->input->post('isVerifyEmail') ? 1 : 0,
                'automatic_interact' => $this->input->post('automatic_interact') ? 1 : 0,
                'interac_limit' => $this->input->post('interac_limit'),
                'verified'   => $this->input->post('verified') ? 1 : 0,
                'trade_fee'  => $this->input->post('trade_fee'),
                'commission' => $this->input->post('commission'),
                'verify_complete_a'   => $this->input->post('verified') ? 1 : 0,
                'verify_complete_b'   => $this->input->post('verified') ? 1 : 0,
                'country'    => $this->input->post('country_of_residence')
            );

            if ($password = $this->input->post('password')) {
                $salt = generateRandomString(20);

                $data['salt']     = $salt;
                $data['password'] = $this->api_security->hash($password, $salt);
            }

            if ($pin = $this->input->post('pin'))
                $data['pin'] = sha1($pin);

            if ($this->user_model->update($userId, $data)) {
                if ($referrerId = $this->input->post('referrer_id'))
                    $this->referral_model->addToUser($userId, $referrerId);

                // Do we have any "details" info
                //if ($user->verified) {

                $data = array(
                    'phone'   => $this->input->post('phone'),
                    'address' => $this->input->post('address'),
                    'city'    => $this->input->post('city'),
                    'state'   => $this->input->post('state'),
                    'country' => $this->input->post('country'),
                    'occupation'=> $this->input->post('occupation'),
                    'zip'     => $this->input->post('zip')
                );

                $this->user_model->updateDetails($userId, $data);
                //}
                
                if($user->verified == 0 && $this->input->post('verified') == "on"){
                    
                    $this->load->library('Mandrilllibrary');
                    $api = $this->mandrilllibrary->getApi();
                    
                    $name = 'user_verified';
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
                            'content' => $this->input->post('first_name')
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
                        'subject' => _l('verified_to'),
                        'from_email' => 'whitebarter Bitcoin Exchange',
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
                    /*
                    $this->email_queue_model->email   = $user->email;
                    $this->email_queue_model->message = $this->layout->partialView('emails/user_verified_'.$this->language, $data);
                    $this->email_queue_model->subject = _l('verified_to');
                    
                    $this->email_queue_model->store();
                    */
                }

                $this->session->set_flashdata('success', 'User updated!');

                redirect('/admin/users', 'refresh');
            }
        }

        $data['userId'] = $userId;
        $data['user']   = $user;
        $data['countReferrals'] = $countReferrals;
        $data['address_bitcoin'] = $this->bitcoin_model->getFromBitcoind($userId);

        $data['first_name'] = array(
            'name'  => 'first_name',
            'id'    => 'first_name',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('first_name', $user->first_name)
        );

        $data['last_name'] = array(
            'name'  => 'last_name',
            'id'    => 'last_name',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('last_name', $user->last_name)
        );

        $data['email'] = array(
            'name'  => 'email',
            'id'    => 'email',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('email', $user->email)
        );

        $data['password'] = array(
            'name'  => 'password',
            'id'    => 'password',
            'type'  => 'text',
            'class' => 'form-control'
        );

        $data['pin'] = array(
            'name'  => 'pin',
            'id'    => 'pin',
            'type'  => 'text',
            'class' => 'form-control'
        );

        $data['dob'] = array(
            'name'  => 'dob',
            'id'    => 'dob',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('dob', $user->dob)
        );

        $data['tradeFee'] = array(
            'name'  => 'trade_fee',
            'id'    => 'trade_fee',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('trade_fee', isset($user->trade_fee) ? $user->trade_fee : '')
        );

        $data['commission'] = array(
            'name'  => 'commission',
            'id'    => 'commission',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('commission', isset($user->commission) ? $user->commission : '')
        );
        
        $data['interac_limit'] = array(
            'name'  => 'interac_limit',
            'id'    => 'interac_limit',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('interac_limit', $user->interac_limit)
        );

        $referrer = isset($user->referrer_id) && $user->referrer_id ? $this->user_model->getUser($user->referrer_id) : null;

        $data['referrer'] = $referrer ? $referrer->first_name . ' ' . $referrer->last_name : array(
            'name'  => 'referrer',
            'id'    => 'referrer',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('referrer')
        );

        $data['active']   = $user->active;
        $data['isadmin']   = $user->isadmin;
        $data['isVerifyEmail'] = $user->isVerifyEmail;
        $data['automatic_interact']   = $user->automatic_interact;
        $data['level'] = $this->checkPermission($this->access_level, SUPERADMIN);
        $data['verified'] = $user->verified;
        if(isset($user->pgp_key)){
            $this->load->library('Mandrilllibrary');
            $dataUpdate = array();
            $dataUpdate['pgp_key'] = $user->pgp_key;
            $res = $this->mandrilllibrary->send('/getFingerprint', "POST", $dataUpdate);
            $data['fingerprint'] = $res->fingerprint;
        }
        
        $data['occupation'] = array(
            'name'  => 'occupation',
            'id'    => 'occupation',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('occupation', isset($user->details->occupation) ? $user->details->occupation : '')
        );

        $data['phone'] = array(
            'name'  => 'phone',
            'id'    => 'phone',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('phone', isset($user->details->phone) ? $user->details->phone : '')
        );

        $data['address'] = array(
            'name'  => 'address',
            'id'    => 'address',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('address', isset($user->details->address) ? $user->details->address : '')
        );

        $data['city'] = array(
            'name'  => 'city',
            'id'    => 'city',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('city', isset($user->details->city) ? $user->details->city : '')
        );

        $data['zip'] = array(
            'name'  => 'zip',
            'id'    => 'zip',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('zip', isset($user->details->zip) ? $user->details->zip : '')
        );

        if (
            (isset($user->details->country) && in_array($user->details->country, array('US','CA')))
            || (!isset($user->details->country)) && in_array($defaultCountry, array('US','CA'))
        ) {
            $data['state']     = isset($user->details->state) ? $user->details->state : 'none';
        } else {
            $data['state'] = array(
                'name'  => 'state',
                'id'    => 'state',
                'type'  => 'text',
                'class' => 'form-control',
                'value' => $this->form_validation->set_value('state', isset($user->details->state) ? $user->details->state : '')
            );
        }

        $data['countries'] = countriesLocalised();
        $data['country']   = isset($user->details->country) ? $user->details->country : $defaultCountry;
        $data['states']    = states($data['country']);

        $data['residence']      = $user->country;
        $data['defaultCountry'] = $defaultCountry;
        $data['language']       = $user->language;

        $data['interacBan'] = $this->deposit_model->userBannedFromInterac($userId);

        $this->load->library('aws_s3');
        $this->load->config('creds_aws');

        $this->load->model('user_document_model');
        $data['documents'] = $this->user_document_model->getForUser($userId);
        
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }

        $this->layout->view('admin/users/details', $data);
    }

    public function user_balances($userId) {
        
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }
        
        $data['userId'] = $userId;
        $data['user']   = $this->user_model->getUser($userId, false);

        $this->layout->view('admin/users/balances', $data);
    }
    
    public function user_notes($userId) {
        if ($this->input->post('note')) {
            if(strlen($this->input->post('note')) > 0){
                $entities = array(
                    "userId" => $userId,
                    "message" => $this->input->post('note')
                );
                $this->note_model->addNote($entities);
                $this->session->set_flashdata('success', 'The note added to user');
                redirect('/admin/user_notes/' . $userId);
            } else {
                $this->session->set_flashdata('error', 'Required field, please');
                redirect('/admin/user_notes/' . $userId);
            }
        }
        
        $entity = $this->note_model->getNotesByUser($userId);
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notes'] = $entity;
            $data['notesCount'] = '<span class="badge" id="count_notes">'.$count.'</span>';
        }
        
        $data['userId'] = $userId;
        $data['user']   = $this->user_model->getUser($userId, false);

        $this->layout->view('admin/users/notes', $data);
    }

    public function user_deposits($userId, $page = 1, $perPage = 30) {
        $count = $this->deposit_model->getCountForUser($userId, 'all');
        
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }

        $data['count']  = $count;
        $data['items']  = $this->deposit_model->getSubsetForUser($page, $perPage);
        $data['pages']  = generateNewPagination('/admin/user_deposits/' . $userId, $count, $page, $perPage, true);
        $data['userId'] = $userId;

        $this->layout->view('admin/users/deposits', $data);
    }
    //Todo futures?
    public function user_futures($userId, $page = 1, $perPage = 30) {
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }
        
        $countFutures = $this->future_model->getCountForUser($userId);
        $data['items']  = $this->future_model->getSubsetForUser($page, $perPage);
        $data['pages']  = generateNewPagination('/admin/user_futures/' . $userId, $countFutures, $page, $perPage, true);
        $data['userId'] = $userId;
        
        $this->layout->view('admin/users/futures', $data);
    }

    public function user_withdrawals($userId, $page = 1, $perPage = 30) {
        
        
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }
        
        $count = $this->withdrawal_model->getCountForUser($userId, 'all');

        $data['count']  = $count;
        $data['items']  = $this->withdrawal_model->getSubsetForUser($page, $perPage);
        $data['pages']  = generateNewPagination('/admin/user_withdrawals/' . $userId, $count, $page, $perPage, true);
        $data['userId'] = $userId;

        $this->layout->view('admin/users/withdrawals', $data);
    }

    public function user_orders($userId) {
        
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }
        
        $data['userId'] = $userId;
        $data['items']  = $this->order_model->getForUser($userId);

        $this->layout->view('admin/users/orders', $data);
    }

    public function user_trades($userId, $page = 1, $perPage = 30) {
        $count = $this->trade_model->getCountForUser($userId);
        
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }

        $data['count']  = $count;
        $data['items']  = $this->trade_model->getSubsetForUser($page, $perPage);
        $data['pages']  = generateNewPagination('/admin/user_trades/' . $userId, $count, $page, $perPage, true);
        $data['userId'] = $userId;

        $this->layout->view('admin/users/trades', $data);
    }

    public function user_referrals($userId) {
        
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }
        
        $data['userId']     = $userId;
        $data['items']      = $this->referral_model->getSummary($userId);
        $data['currencies'] = $this->meta_model->currencies;

        $this->layout->view('admin/users/referrals', $data);
    }

    public function disable_twofa($userId) {
        $this->admin_model->disable_twofa($userId);

        redirect('/admin/user/' . $userId);
    }

    public function users_online() {
        $data['section'] = 'users';
        $data['users'] = $this->admin_model->getUsersOnline(5);

        $this->layout->view('admin/users/online', $data);
    }

    public function cancel_order($id) {
        $order = $this->order_model->get($id);

        $this->order_model->cancel($id);

        $this->session->set_flashdata('success', 'Order #' . $id . ' was successfully cancelled');

        redirect('/admin/user_orders/' . $order->client);
    }

    public function deposit($id) {
        $deposit = $this->deposit_model->getFull($id);

        $user    = $this->user_model->getUser($deposit->client);

        if ($this->input->post('cancel')) {
            $this->deposit_model->cancel($id, $deposit->client);
            
            $this->session->set_flashdata('success', 'Deposit #' . $deposit->id . ' canceled');
            redirect('admin/deposits/pending', 'refresh');
        }
        
        if ($this->input->post('delete')) {
            $this->deposit_model->delete($id, $deposit->client);
            
            $this->session->set_flashdata('success', 'Deposit #' . $deposit->id . ' deleted');
            redirect('admin/deposits/pending', 'refresh');
        }

        if ($this->input->post('update')) {
            $data = array(
                'amount' => $this->input->post('amount')
            );

            $this->deposit_model->update($id, $data);

            $this->session->set_flashdata('success', 'Deposit updated');
            redirect('admin/deposit/' . $id, 'refresh');
        }

        if ($this->input->post('received')) {
            $this->deposit_model->receive($id, $this->input->post('reference'));

            $this->session->set_flashdata('success', 'Deposit #' . $deposit->id . ' successfully received');
            redirect('admin/deposits/pending', 'refresh');
        }

        $data['user']    = $user;
        $data['deposit'] = $deposit;
        $data['amount']  = array(
            'name'  => 'amount',
            'id'    => 'amount',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('amount', $deposit->amount)
        );

        $this->layout->view('admin/deposit/details', $data);
    }

    public function withdrawal($id) {
        $withdrawal = $this->withdrawal_model->getFull($id);
        $user       = $this->user_model->getUser($withdrawal->client);

        if ($this->input->post('delete'))
        {
            $this->withdrawal_model->cancel($id, $withdrawal->client);
            $this->session->set_flashdata('success', 'Withdrawal #' . $id . ' canceled');
            
            redirect('admin/withdrawals/pending', 'refresh');
        }

        if ($this->input->post('update'))
        {
            $data = array(
                'amount' => $this->input->post('amount')
            );
            $this->withdrawal_model->update($id, $data);

            $this->session->set_flashdata('success', 'Withdrawal updated');
            redirect('admin/withdrawal/' . $id, 'refresh');
        }

        if ($this->input->post('sent'))
        {
            $this->withdrawal_model->sent($id, $withdrawal->client);
            $this->user_balance_model->updateReferrerBalance($withdrawal->client, 
                                                             $withdrawal->details->referrerFee, 
                                                             $withdrawal->currency);

            $this->session->set_flashdata('success', 'Withdrawal #' . $id . ' successfully sent');
            redirect('admin/withdrawals/pending', 'refresh');
        }

        $data['user']       = $user;
        $data['withdrawal'] = $withdrawal;
        $data['amount']     = array(
            'name'  => 'amount',
            'id'    => 'amount',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('amount', $withdrawal->amount)
        );

        $this->layout->view('admin/withdrawal/details', $data);
    }
    
    public function future($id) {
        $future = $this->future_model->get($id);
        $user   = $this->user_model->getUser($future->client);
        
        $data['user']   = $user;
        $data['future'] = $future;
        
        $this->layout->view('admin/future/details', $data);
    }

    public function deposits($status, $page = 1, $perPage = 30) {
        $filter = $this->input->post('search');
        $count = $this->deposit_model->getCount($status);

        $data["section"] = 'deposits';
        $data['status']   = $status;
        if ($filter && strlen($filter) > 3) {
            $data['deposits'] = $this->deposit_model->find($status, $filter);
        } else {
            $data['count']    = $count;
            $data['pages']    = generateNewPagination('/admin/deposits/' . $status, $count, $page, $perPage, true);
            $data['deposits'] = $this->deposit_model->getSubset($page, $perPage);
        }

        $this->layout->view('admin/deposit/index', $data);
    }

    public function depositsCSV($status) {

        $count = $this->deposit_model->getCount($status);
        $deposits = $this->deposit_model->getSubset(1, 10000000);

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=deposits-".$status."-".date("dmy").".csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '"Deposit ID","Client","Client ID","Method","Currency","Credited","Paid","Created","Updated"';
        echo "\n";
        foreach ($deposits as $deposit) {

            $depositId = _numeric($deposit->_id);
            $clientId = $deposit->client;
            echo '"' . $depositId;
            echo '","' . $deposit->user->first_name . ' ' . $deposit->user->last_name;
            echo '","' . $clientId;
            echo '","' . code2Name($deposit->method);
            echo '","' . strtoupper($deposit->currency) . '","' . $deposit->amount . '","' . (isset($deposit->details->pay_amt) ? $deposit->details->pay_amt : '-');
            echo '","' . Date('Y-m-d H:i:s', $deposit->_created / 1000);
            echo '","' . ($deposit->_updated ? Date('Y-m-d H:i:s', $deposit->_updated / 1000) : '--') . '"';
            echo "\n";
        }
    }

    public function withdrawals($status, $page = 1, $perPage = 30) {
        $count = $this->withdrawal_model->getCount($status);
        $data["section"] = 'withdrawals';
        $data['status']      = $status;
        $data['count']       = $count;
        $data['withdrawals'] = $this->withdrawal_model->getSubset($page, $perPage);
        $data['pages']       = generateNewPagination('/admin/withdrawals/' . $status, $count, $page, $perPage, true);

        $this->layout->view('admin/withdrawal/index', $data);
    }

    public function add_deposit() {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $phase = 1;
        $methods = array('btc', 'io', 'bw','ch', 'ba', 'ca', 'bp', 'mo', 'wu', 'ip', 'eft', 'ep', 'pz', 'rp', 'mp', 'vg', 'cy');
        foreach ($methods as $m)
            $data['methods'][$m] = code2Name($m);

        $method   = $this->input->post('method');
        $country  = $this->input->post('country');
        $currency = $this->input->post('currency');

        $clientId = $this->input->post('client');
        $user     = $clientId ? $this->user_model->getUser($clientId) : null;

        if ($method) {
            if (in_array($method, $this->meta_model->cryptoCurrencies) !== false){
                $currencies = array($method);
            } else if($method == 'ba'){
                $currencies = array('btc');
            } else {
                $currencies = $this->meta_model->fiatCurrencies;
            }
        }

        if ($this->input->post('next')) {
            $this->form_validation->set_rules('client', 'ClientID', 'required|callback_valid_client');

            if ($this->form_validation->run() == true) {
                $phase = 2;
            }
        }
        else if ($this->input->post('complete')) {
            $phase = 2;
            $this->form_validation->set_rules('amount',   'Amount',   'required|is_numeric');
            $this->form_validation->set_rules('currency', 'Currency', 'callback_valid_deposit_method');

            switch ($method) {
                case 'wu':
                    $this->form_validation->set_rules('city', 'City', 'required');

                    break;

                case 'btc':
                    $this->form_validation->set_rules('address', 'Address', 'required|callback_valid_bitcoin_address');

                    break;
                case 'ba':
                    $this->form_validation->set_rules('note', 'Note', 'required');

                    break;
                case 'ca':
                    $this->form_validation->set_rules('note', 'Note', 'required');

                    break;
                case 'eft':
                    $this->form_validation->set_rules('bank_name',      'Bank Name',      'required');
                    $this->form_validation->set_rules('bank_address',   'Bank Address',   'required');
                    $this->form_validation->set_rules('account_number', 'Account Number', 'required');
                    $this->form_validation->set_rules('branch_transit', 'Branch Transit', 'required');

                    break;
            }

            if ($this->form_validation->run() == true)
            {
                $data = array(
                    'client'   => $this->input->post('client'),
                    'method'   => $method,
                    'currency' => $currency,
                    'amount'   => abs($this->input->post('amount'))
                );

                $details = null;
                switch ($method) {
                    case 'bw':
                        $details = array(
                            'country' => $this->input->post('country')
                        );

                        break;

                    case 'wu':
                        $details = array(
                            'city'    => $this->input->post('city'),
                            'country' => $this->input->post('country')
                        );

                        break;

                    case 'eft':
                        $details = array(
                            'bank_name'      => $this->input->post('bank_name'),
                            'bank_address'   => $this->input->post('bank_address'),
                            'account_number' => $this->input->post('account_number'),
                            'branch_transit' => $this->input->post('branch_transit')
                        );

                        break;

                    case 'btc':
                        $details = array(
                            'address' => $this->input->post('address')
                        );

                        break;
                        
                    case 'ba':
                        $details = array(
                            'note' => $this->input->post('note')
                        );

                        break;
                    case 'ca':
                        $details = array(
                            'note' => $this->input->post('note')
                        );

                        break;
                }

                if ($depositId = $this->deposit_model->addComplete($data, $details)) {
                    $this->session->set_flashdata('success', 'Deposit #' . _numeric($depositId) . ' added for ' . $user->first_name . ' ' . $user->last_name);

                    redirect('admin/deposits/complete', 'refresh');
                }
                else $data['error_message'] = 'Issue saving the deposit';
            }
        }

        $data["section"] = 'deposits';
        $data['method']  = $method;
        $data['user']    = $user;

        if ($method) {
            foreach ($currencies as $c)
                $data['currencies'][$c] = code2Name($c);
        }

        $data['client']  = array(
            'name'  => 'client',
            'id'    => 'client',
            'type'  => 'text',
            'class' => 'form-control'
        );

        $data['amount'] = array(
            'name'  => 'amount',
            'id'    => 'amount',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('amount')
        );

        $data['countries'] = countriesLocalised();

        if ($method) {
            switch ($method) {
                case 'bw':
                    $data['country'] = $country ? $country : $this->config->item('default_country');

                    break;

                case 'wu':
                    $data['country'] = $country ? $country : $this->config->item('default_country');

                    $data['city'] = array(
                        'name'        => 'city',
                        'id'          => 'city',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Sender\'s City',
                        'value'       => $this->form_validation->set_value('city')
                    );

                    break;

                case 'eft':
                    $data['bankName'] = array(
                        'name'        => 'bank_name',
                        'id'          => 'bank_name',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Bank Name',
                        'value'       => $this->form_validation->set_value('bank_name')
                    );

                    $data['bankAddress'] = array(
                        'name'        => 'bank_address',
                        'id'          => 'bank_address',
                        'class'       => 'form-control',
                        'placeholder' => 'Full Bank Address',
                        'value'       => $this->form_validation->set_value('bank_address')
                    );

                    $data['accountNumber'] = array(
                        'name'        => 'account_number',
                        'id'          => 'account_number',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Account Number',
                        'value'       => $this->form_validation->set_value('account_number')
                    );

                    $data['branchTransit'] = array(
                        'name'        => 'branch_transit',
                        'id'          => 'branch_transit',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Branch Transit',
                        'value'       => $this->form_validation->set_value('branch_transit')
                    );

                    break;

                case 'btc':
                    $data['address'] = array(
                        'name'        => 'address',
                        'id'          => 'address',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Bitcoin Address',
                        'value'       => $this->form_validation->set_value('address')
                    );

                    break;
                case 'ba':
                    $data['note'] = array(
                        'name'        => 'note',
                        'id'          => 'note',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Enter note',
                        'value'       => $this->form_validation->set_value('note')
                    );

                    break;
                case 'ca':
                    $data['note'] = array(
                        'name'        => 'note',
                        'id'          => 'note',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Enter note',
                        'value'       => $this->form_validation->set_value('note')
                    );

                    break;
            }
        }

        $this->layout->view('admin/deposit/add_phase' . $phase, $data);
    }

    public function add_withdrawal() {
        
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $phase = 1;
        $methods = array('btc', 'bw', 'wu', 'ch', 'ba', 'ca', 'ie', 'ep', 'pz', 'rp', 'mp', 'vg', 'cy');

        foreach ($methods as $m)
            $data['methods'][$m] = code2Name($m);

        $method   = $this->input->post('method');
        $country  = $this->input->post('country');
        $currency = $this->input->post('currency');
        $amount   = $this->input->post('amount');

        $clientId = $this->input->post('client');
        $user     = $clientId ? $this->user_model->getUser($clientId) : null;
        $balances = $clientId ? $this->user_balance_model->get($clientId) : null;

        if ($method) {
            if (in_array($method, $this->meta_model->cryptoCurrencies) !== false) {
                $currencies = array($method);
            } else if($method == 'ba'){
                $currencies = array('btc');
            } else {
                $currencies = $this->meta_model->fiatCurrencies;
            }
        }

        if ($this->input->post('next')) {
            $this->form_validation->set_rules('client', 'ClientID', 'required|callback_valid_client');

            if ($this->form_validation->run() == true) {
                $phase = 2;
            }
        }
        else if ($this->input->post('complete')) {
            $phase = 2;
            $this->form_validation->set_rules('amount',   'Amount',   'required|is_numeric');
            $this->form_validation->set_rules('currency', 'Currency', 'callback_valid_withdrawal_method');

            switch ($method) {
                case 'bw':
                    $this->form_validation->set_rules('address',      'Address',        'required');
                    $this->form_validation->set_rules('bank_name',    'Bank Name',      'required');
                    $this->form_validation->set_rules('bank_address', 'Bank Address',   'required');
                    $this->form_validation->set_rules('account',      'Account Number', 'required');
                    $this->form_validation->set_rules('swift',        'SWIFT',          'required');

                    break;

                case 'wu':
                    $this->form_validation->set_rules('city', 'City', 'required');

                    break;

                case 'dt':
                    $this->form_validation->set_rules('institution', 'Financial Institution', 'required');
                    $this->form_validation->set_rules('transit',     'Bank Transit',          'required');
                    $this->form_validation->set_rules('account',     'Account Number',        'required');

                    break;

                case 'ch':
                    $this->form_validation->set_rules('address', 'Address', 'required');

                    break;

                case 'ie':
                    $this->form_validation->set_rules('email', 'Email', 'required|valid_email');

                    break;

                case 'vi':
                    $this->form_validation->set_rules('card_number', 'Card Number', 'required');

                    break;

                case 'btc':
                    $this->form_validation->set_rules('address', 'Address', 'required|callback_valid_bitcoin_address');

                    break;
                case 'ba':
                    $this->form_validation->set_rules('note', 'Note', 'required');

                    break;
                case 'ca':
                    $this->form_validation->set_rules('note', 'Note', 'required');

                    break;
            }

            if ($this->form_validation->run() == true)
            {
                $data = array(
                    'client'   => $this->input->post('client'),
                    'method'   => $method,
                    'currency' => $currency,
                    'amount'   => abs($amount)
                );

                if ($fee = $this->input->post('fee')) {
                    if ($currency == 'xau')
                        $fee = bcmul((string)abs($amount), (string)$fee, 6);

                    $data['fee'] = $fee;
                }

                $details = null;
                switch ($method) {
                    case 'bw':
                        $details = array(
                            'address'      => $this->input->post('address'),
                            'bank_name'    => $this->input->post('bank_name'),
                            'bank_address' => $this->input->post('bank_address'),
                            'account'      => $this->input->post('account'),
                            'swift'        => $this->input->post('swift'),
                            'instructions' => $this->input->post('instructions')
                        );

                        break;

                    case 'wu':
                        $details = array(
                            'city'    => $this->input->post('city'),
                            'country' => $this->input->post('country')
                        );

                        break;

                    case 'dt':
                        $details = array(
                            'institution' => $this->input->post('institution'),
                            'transit'     => $this->input->post('transit'),
                            'account'     => $this->input->post('account')
                        );

                        break;

                    case 'ch':
                        $details = array(
                            'address' => $this->input->post('address')
                        );

                        break;

                    case 'ie':
                        $details = array(
                            'email' => $this->input->post('email')
                        );

                        break;

                    case 'vi':
                        $details = array(
                            'card_number' => $this->input->post('card_number')
                        );

                        break;

                    case 'btc':
                        $details = array(
                            'address' => $this->input->post('address')
                        );

                        break;
                    
                    case 'ba':
                        $details = array(
                            'note' => $this->input->post('note')
                        );

                        break;
                    
                    case 'ca':
                        $details = array(
                            'note' => $this->input->post('note')
                        );

                        break;

                    case '1oz':
                        $details = array(
                            'address' => $this->input->post('address')
                        );

                        break;
                }

                if ($withdrawalId = $this->withdrawal_model->addComplete($data, $details)) {
                    $this->session->set_flashdata('success', 'Withdrawal #' . _numeric($withdrawalId) . ' added for ' . $user->first_name . ' ' . $user->last_name);

                    redirect('admin/withdrawals/complete', 'refresh');
                }
                else $data['error_message'] = 'Issue saving the withdrawal - not enough in the balance?';
            }
        }

        $data["section"]  = 'withdrawals';
        $data['method']   = $method;
        $data['user']     = $user;
        $data['balances'] = $balances;

        if ($method) {
            foreach ($currencies as $c)
                $data['currencies'][$c] = code2Name($c);
        }

        $data['client']  = array(
            'name'  => 'client',
            'id'    => 'client',
            'type'  => 'text',
            'class' => 'form-control'
        );

        $data['amount'] = array(
            'name'  => 'amount',
            'id'    => 'amount',
            'type'  => 'text',
            'class' => 'form-control',
            'value' => $this->form_validation->set_value('amount')
        );

        $data['countries'] = countriesLocalised();

        if ($method) {
            switch ($method) {
                case 'bw':
                    $data['address'] = array(
                        'name'        => 'address',
                        'id'          => 'address',
                        'class'       => 'form-control',
                        'placeholder' => 'Full Address including Postcode, City and Country',
                        'value'       => $this->form_validation->set_value('address')
                    );

                    $data['bankName'] = array(
                        'name'        => 'bank_name',
                        'id'          => 'bank_name',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Bank Name',
                        'value'       => $this->form_validation->set_value('bank_name')
                    );

                    $data['bankAddress'] = array(
                        'name'        => 'bank_address',
                        'id'          => 'bank_address',
                        'class'       => 'form-control',
                        'placeholder' => 'Bank Full Address including Postcode, City and Country',
                        'value'       => $this->form_validation->set_value('bank_address')
                    );

                    $data['account'] = array(
                        'name'        => 'account',
                        'id'          => 'account',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Account Number',
                        'value'       => $this->form_validation->set_value('account')
                    );

                    $data['swift'] = array(
                        'name'        => 'swift',
                        'id'          => 'swift',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'BIC/SWIFT Code',
                        'value'       => $this->form_validation->set_value('swift')
                    );

                    $data['instructions'] = array(
                        'name'        => 'instructions',
                        'id'          => 'instructions',
                        'class'       => 'form-control',
                        'placeholder' => 'Any Specific Instructions...',
                        'value'       => $this->form_validation->set_value('instructions')
                    );

                    break;

                case 'wu':
                    $data['country'] = $country ? $country : $this->config->item('default_country');

                    $data['city'] = array(
                        'name'        => 'city',
                        'id'          => 'city',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Sender\'s City',
                        'value'       => $this->form_validation->set_value('city')
                    );

                    break;

                case 'dt':
                    $data['transit'] = array(
                        'name'        => 'transit',
                        'id'          => 'transit',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Bank Transit',
                        'value'       => $this->form_validation->set_value('transit')
                    );

                    $data['account'] = array(
                        'name'        => 'account',
                        'id'          => 'account',
                        'type'        => 'text',
                        'class'       => 'form-control',
                        'placeholder' => 'Account Number',
                        'value'       => $this->form_validation->set_value('account')
                    );

                    break;

                case 'ch':
                    $data['address'] = array(
                        'name'        => 'address',
                        'id'          => 'address',
                        'class'       => 'form-control',
                        'placeholder' => 'Full Postal Address including Postcode, City, County and Country',
                        'value'       => $this->form_validation->set_value('address')
                    );

                    break;

                case 'ie':
                    $data['email'] = array(
                        'name'        => 'email',
                        'id'          => 'email',
                        'class'       => 'form-control',
                        'placeholder' => 'Your Interac E-Transfer email address',
                        'value'       => $this->form_validation->set_value('email')
                    );

                    break;

                case 'vi':
                    $data['card_number'] = array(
                        'name'        => 'card_number',
                        'id'          => 'card_number',
                        'class'       => 'form-control',
                        'placeholder' => 'VISA Card Long Number',
                        'value'       => $this->form_validation->set_value('card_number')
                    );

                    break;

                case 'btc':
                    $data['address'] = array(
                        'name'        => 'address',
                        'id'          => 'address',
                        'class'       => 'form-control',
                        'placeholder' => 'Bitcoin Address',
                        'value'       => $this->form_validation->set_value('address')
                    );

                    break;
                
                case 'ba':
                    $data['note'] = array(
                        'name'        => 'note',
                        'id'          => 'note',
                        'class'       => 'form-control',
                        'placeholder' => 'Enter note',
                        'value'       => $this->form_validation->set_value('note')
                    );

                    break;
                
                case 'ca':
                    $data['note'] = array(
                        'name'        => 'note',
                        'id'          => 'note',
                        'class'       => 'form-control',
                        'placeholder' => 'Enter note',
                        'value'       => $this->form_validation->set_value('note')
                    );

                    break;

                case '1oz':
                    $data['address'] = array(
                        'name'        => 'address',
                        'id'          => 'address',
                        'class'       => 'form-control',
                        'placeholder' => 'Full Postal Address including Postcode, City, County and Country',
                        'value'       => $this->form_validation->set_value('address')
                    );

                    $data['fee'] = array(
                        'name'        => 'fee',
                        'id'          => 'fee',
                        'class'       => 'form-control',
                        'value'       => $this->form_validation->set_value('fee', '0.05')
                    );

                    break;
            }
        }

        $this->layout->view('admin/withdrawal/add_phase' . $phase, $data);
    }

    public function valid_client($userId) {
        $user = $this->user_model->userExists($userId);
        if (!$user || empty($user))
        {
            $this->form_validation->set_message('valid_client', 'This client does not exist');
            return false;
        }

        return true;
    }

    public function valid_bitcoin_address($address)
    {
        if (!preg_match ('/^[123][1-9A-Za-z][^OIl]{20,40}/', $address))
        {
            $this->form_validation->set_message('valid_bitcoin_address', 'Appears to be invalid');
            return false;
        }

        return true;
    }

    public function valid_deposit_method($currency) {
        $method = $this->input->post('method');
        $key    = $currency . $method;

        if (in_array($key, $this->allowedDeposits) === FALSE) {
            $this->form_validation->set_message('valid_deposit_method', 'Not allowed CURRENCY + METHOD');
            return false;
        }

        return true;
    }

    public function valid_withdrawal_method($currency) {
        $method = $this->input->post('method');
        $key    = $currency . $method;

        if (in_array($key, $this->allowedWithdrawals) === FALSE) {
            $this->form_validation->set_message('valid_withdrawal_method', 'Not allowed CURRENCY + METHOD');
            return false;
        }

        return true;
    }

    public function events($type = 'all', $page = 1, $perPage = 50) {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $count = $this->event_model->getCount($type);

        $data["section"] = 'activity';

        $events = $this->event_model->getSubset($page, $perPage);

        $data['events'] = $events;
        $data['pages']  = generateNewPagination('/admin/events/' . $type, $count, $page, $perPage, true);

        $data['types']     = $this->event_types();
        $data['type']      = $type;

        $this->layout->view('admin/events', $data);
    }

    public function user_events($userId, $type = 'all', $page=1, $perPage=50) {
        $count = $this->event_model->getCountForUser($userId, $type);

        $data["section"] = 'activity';

        $events = $this->event_model->getSubsetForUser($page, $perPage);
        
        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = '<span class="badge ">'.$count.'</span>';
        }

        $data['userId'] = $userId;
        $data['events'] = $events;
        $data['pages']  = generateNewPagination('/admin/events/' . $type, $count, $page, $perPage, true);

        $data['types']     = $this->event_types();
        $data['type']      = $type;

        $this->layout->view('admin/users/events', $data);
    }

    public function event_types() {

        // Attributes are:  Label, Icon, Colour, (show on menu 0 or 1)
        return array(
            "all"       => array("All", ""),
            "login"     => array("Login", "sign-in", "6a6"),
            "logout"    => array("Logout", "sign-out", "ca6", 0),
            "loginfail" => array("Login Failure", "exclamation-triangle", "f93"),
            "vcomplete" => array("Verification Complete", "thumbs-up", "6c6"),
            "vfail"     => array("Verification Fail", "thumbs-down", "f66"),
            "register"  => array("Register", "smile-o", "6c6"),
            "2faon"     => array("2FA Enable", "shield", "6c6", 0),
            "2faoff"    => array("2FA Disable", "shield", "fc9", 0),
            "2faproblem"=> array("2FA Problem", "shield", "f66", 0),
            "pwchange"  => array("Password Change", "life-ring", "9cf", 0),
            "ipchange"  => array("IP Changed", "", "f93", 0)
        );
    }

    public function trades($book = null, $page = 1, $perPage = 50) {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $book  = $book ? $book : $this->config->item('default_book');
        $books = $this->meta_model->books;

        $count = $this->trade_model->getCount($book);

        $data["section"] = 'activity';

        $data['trades'] = $this->trade_model->getSubset($book, $page, $perPage);
        $data['pages']  = generateNewPagination('/admin/trades/' . $book, $count, $page, $perPage, true);

        $data['books'] = $books;
        $data['book']  = $book;

        $this->layout->view('admin/trades/index', $data);
    }

    public function tradesCSV($book) {

        $trades =$this->trade_model->getTrades($book, 10000000, true);

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=trades-".date("dmy").".csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '"ID","Date","Seller","Seller ID","Amount (MAJ)","Currency (MAJ)","Price (MIN)","Fee (MAJ)","Total (MAJ)","Value (MIN)","Currency (MIN)","Fee (MIN)","Total (MIN)","Buyer","Buyer ID"';
        echo "\n";
        foreach ($trades as $trade) {
            $seller  = $trade->major->first_name . ' ' . $trade->major->last_name;
            $buyer   = $trade->minor->first_name . ' ' . $trade->minor->last_name;
            $tradeId = _numeric($trade->_id);

            echo '"' . $tradeId . '","' . Date('Y-m-d H:i:s', $trade->_created / 1000) . '","' . $seller . '","' . $trade->major_client;
            echo '","' . $trade->amount;
            echo '","' . strtoupper($trade->major_currency);
            echo '","' . $trade->rate;
            echo '","' . $trade->major_fee;
            echo '","' . $trade->major_total;
            echo '","' . $trade->value;
            echo '","' . strtoupper($trade->minor_currency);
            echo '","' . $trade->minor_fee;
            echo '","' . $trade->minor_total;
            echo '","' . $buyer . '","' . $trade->minor_client . '"';
            echo "\n";
        }
    }

    public function trade($tradeId) {
        $trade = $this->trade_model->get($tradeId, true);

        if (!$trade) {
            $this->session->set_flashdata('error', 'Trade not found');
            redirect('/admin/trades', 'refresh');
        }

        $data = array(
            'tradeId' => $tradeId,
            'trade'   => $trade
        );

        $this->layout->view('admin/trades/details', $data);
    }

    public function setRollingVolume() {
        $books = $this->meta_model->getBooks();

        foreach ($books as $bookId=>$book)
            $this->trade_model->generateRollingVolume($bookId);
    }

    /* EXPERIMENTAL - highly possible it is broken anyway */
//    public function check($userId = '*') {
//        $data = $this->user_balance_model->checkBalances($userId);
//
//        var_dump($data);
//    }

    public function generate_vouchers() {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $this->load->model('voucher_model');

        if ($this->input->post()) {
            $this->form_validation->set_rules('amount', 'Amount', 'required|is_numeric');
            $this->form_validation->set_rules('value',  'value', 'required');

            if ($this->form_validation->run() == true)
            {
                $data = array(
                    'value'    => $this->input->post('value'),
                    'currency' => $this->input->post('currency'),
                    'expiry'   => $this->input->post('expiry'),
                    'referrer' => $this->input->post('referrer') ? $this->input->post('referrer_id') : null
                );

                $amount = $this->input->post('amount');

                $data['vouchers'] = $this->voucher_model->generate($amount, $data);
            }
        }

        $data['amount'] = array(
            'name'        => 'amount',
            'id'          => 'amount',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => 'Number of vouchers',
            'value'       => $this->form_validation->set_value('amount')
        );

        $data['value'] = array(
            'name'        => 'value',
            'id'          => 'value',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => 'Value',
            'value'       => $this->form_validation->set_value('value')
        );

        $data['referrer'] = array(
            'name'        => 'referrer',
            'id'          => 'referrer',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => 'Referrer',
            'value'       => $this->form_validation->set_value('referrer')
        );

        $data['currencies'] = array();

        $currencies = $this->meta_model->currencies;
        foreach ($currencies as $currency)
            $data['currencies'][$currency] = code2Name($currency);

        $data['currency'] = $this->form_validation->set_value('currency');

        $data['expiries'] = array(0 => 'Never', 1 => 'One Day', 3 => 'Three Days', 7 => 'Seven Days', 30 => 'Thirty Days', 60 => 'Sixty Days');
        $data['expiry']   = $this->form_validation->set_value('expiry');

        $data["section"] = 'vouchers';

        $this->layout->view('admin/vouchers/generate', $data);
    }

    public function vouchers($page = 1, $perPage = 50) {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $this->load->model('voucher_model');

        $count = $this->voucher_model->getCount();

        $data["section"] = 'vouchers';
        $data['vouchers'] = $this->voucher_model->getSubset($page, $perPage);
        $data['pages']    = generateNewPagination('/admin/vouchers', $count, $page, $perPage, true);

        $this->layout->view('admin/vouchers/list', $data);
    }
    
    public function voucher($code, $action) {
        $this->load->model('voucher_model');
        $this->voucher_model->remove($code);

        $this->session->set_flashdata('success', 'The voucher has been deleted');
        redirect('/admin/vouchers', 'refresh');
    }

    public function blockchain() {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        if ($this->input->post()) {
            $this->form_validation->set_rules('address', 'Address', 'required|callback_valid_bitcoin_address');

            if ($this->form_validation->run() == true) {
                $address = $this->input->post('address');
                $secret  = $this->input->post('secret');
                $status  = $this->input->post('status');

                $this->admin_model->setBlockchainData('address', $address);
                if ($secret) // Only save the secret if it has been set
                    $this->admin_model->setBlockchainData('secret', $secret);
                $this->admin_model->setBlockchainData('status', $status);

                $this->session->set_flashdata('success', 'Blockchain Bitcoin Data updated');
                redirect('/admin/blockchain', 'refresh');
            }
        }

        $data["section"] = 'settings';

        $data['address'] = array(
            'name'        => 'address',
            'id'          => 'address',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => 'Bitcoin address',
            'value'       => $this->form_validation->set_value('address', $this->admin_model->getBlockchainData('address'))
        );

        $data['secret'] = array(
            'name'        => 'secret',
            'id'          => 'secret',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => 'Secret Handshake',
            'value'       => $this->form_validation->set_value('secret')
        );

        $data['status'] = $this->admin_model->getBlockchainData('status');

        $this->layout->view('admin/deposit/blockchain', $data);
    }

    public function permissions() {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $this->load->model('permissions_model');

        $permissions = array(
            'apipublic'  => 'Public API',
            'apiprivate' => 'Private API',
            'website'    => 'Website'
        );

        foreach ($permissions as $code=>$permission) {
            $data['status'][$code] = $this->permissions_model->get($code);
        }

        $data["section"]     = 'settings';
        $data['permissions'] = $permissions;

        $this->layout->view('admin/permissions', $data);
    }

    public function toggle($what, $status) {
        $this->load->model('permissions_model');

        $this->permissions_model->set($what, $status);
    }

    public function bitcoind() {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        if ($this->input->post()) {
            $status = $this->input->post('status');

            $this->admin_model->setBitcoindData('status', $status);

            $this->session->set_flashdata('success', 'Bitcoind Data updated');
            redirect('/admin/bitcoind', 'refresh');
        }

        $data["section"] = 'settings';
        $data['status']  = $this->admin_model->getBitcoindData('status');

        $this->layout->view('admin/deposit/bitcoind', $data);
    }

    public function limits($book = null) {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        if (!$book)
            $book = $this->config->item('default_book');

        $limits = $this->meta_model->getLimits($book);

        if ($this->input->post()) {
            $data = array();

            foreach ((array)$limits as $key=>$value) {
                $data[$key] = $this->input->post($key);
            }

            $this->meta_model->setLimits($book, $data);

            $this->session->set_flashdata('success', 'New limits set');
            redirect('/admin/limits/' . $book);
        }

        $data["section"] = 'settings';
        $data['book']   = $book;
        $data['books']  = $this->meta_model->books;
        $data['limits'] = $limits;

        foreach ((array)$limits as $key=>$value) {
            $data[$key] = array(
                'name'  => $key,
                'id'    => $key,
                'type'  => 'text',
                'class' => 'form-control',
                'value' => $this->form_validation->set_value($key, $value)
            );
        }

        $this->layout->view('admin/trades/limits', $data);
    }

    public function logs($prefix = 'all', $which = null, $page = 1, $perPage = 30) {
        $data['section'] = 'logs';
        $data['prefix']  = $prefix;
        $data['which'] = $which;

        if ($which && $which!="-") {
            $data['keyData'] = $this->logging_model->get($which);
            $this->layout->view('admin/log/keydata', $data);
            return;
        }

        $count = $this->logging_model->getCount($prefix);
        $logs  = $this->logging_model->getSubset();

        $data['count'] = $count;
        $data['logs'] = $logs;

        $url = '/admin/logs/'.$prefix.'/-';
        $data['pages'] = generateNewPagination($url, $count, $page, $perPage, true);

        $this->layout->view('admin/log/keys', $data);
    }

    public function summary() {
        //$this->bitcoin_model->getSummary();
    }

    public function recalculateFees() {
        $this->trade_model->recalculateFees();

        $this->session->set_flashdata('success', 'Fees have been recalculated');
        redirect('/admin');
    }

    public function site_message() {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $data['section'] = 'messaging';
        if ($this->input->post()) {
            $this->form_validation->set_rules('message', 'Message', 'required');

            if ($this->form_validation->run() == true) {
                $message = $this->input->post('message');
                $type    = $this->input->post('type');

                $data = array(
                    'message' => $message,
                    'type'    => $type
                );

                $this->notification_model->broadcast('notification', $data);
                $this->notification_model->flush();

                $this->session->set_flashdata('success', 'Message successfully sent!');
                redirect('/admin/site_message', 'refresh');
            }
        }

        $data['message'] = array(
            'name'        => 'message',
            'id'          => 'message',
            'class'       => 'form-control',
            'placeholder' => 'Message to send to all users',
            'value'       => $this->form_validation->set_value('message')
        );

        $data['types'] = array('alert' => 'Alert (grey)', 'success' => 'Success (green)', 'error' => 'Error (red)');
        $data['type'] = $this->form_validation->set_value('type');

        $this->layout->view('admin/site_message', $data);
    }

    public function rate_limit() {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $this->load->model('api_model');

        if ($this->input->post()) {
            $ips   = explode("\r\n", trim($this->input->post('ips')));
            $users = explode("\r\n", trim($this->input->post('users')));

            $this->api_model->updateRateLimiterData('ip', $ips);
            $this->api_model->updateRateLimiterData('user', $users);

            $this->session->set_flashdata('success', 'Exclusion lists updated');

            redirect('admin/rate_limit', 'refresh');
        }

        $ips = '';
        foreach ($this->api_model->getRateLimiterData('ip') as $ip)
            $ips .= $ip . "\n";

        $users = '';
        foreach ($this->api_model->getRateLimiterData('user') as $user)
            $users .= $user . "\n";

        $data["section"] = 'settings';

        $data['ips'] = array(
            'name'        => 'ips',
            'id'          => 'ips',
            'class'       => 'form-control',
            'placeholder' => 'Whitelisted IPs, one by line',
            'value'       => $this->form_validation->set_value('ips', $ips)
        );

        $data['users'] = array(
            'name'        => 'users',
            'id'          => 'users',
            'class'       => 'form-control',
            'placeholder' => 'Whitelisted users, one by line',
            'value'       => $this->form_validation->set_value('users', $users)
        );

        $this->layout->view('admin/rate_limiter', $data);
    }

    public function cache($action = '') {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        switch ($action) {
            case 'purge':
                $this->caching_model->purge();

                $this->session->set_flashdata('success', 'Cache purged!');
                redirect('admin/cache', 'refresh');

                break;

            case 'delete':
                $key = $this->input->get('key');

                $this->caching_model->delete($key);

                $this->session->set_flashdata('success', 'Cache key deleted!');
                redirect('admin/cache', 'refresh');

                break;
        }

        $data['list'] = $this->caching_model->listAll();

        $this->layout->view('admin/cache', $data);
    }
    
    public function fee($action = '') {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        if ($this->input->post()) {
            $status = $this->input->post('status');

            $this->admin_model->setFeeData('status', $status);

            $this->session->set_flashdata('success', 'Fee Data updated');
            redirect('/admin/fee', 'refresh');
        }

        $data["section"] = 'settings';
        $data['status']  = $this->admin_model->getFeeData('status');

        $this->layout->view('admin/fee', $data);
    }
    
    public function bitcoinSetup($action = '') {
        
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        if ($this->input->post()) {

            $this->admin_model->setWallet('limit', $this->input->post('limit'));
            $this->admin_model->setWallet('address1', $this->input->post('address1'));
            $this->admin_model->setWallet('address2', $this->input->post('address2'));
            
            
            $depositStatus = $this->input->post('deposit_status');
            $withdrawalsStatus = $this->input->post('withdrawals_status');

            if(isset($depositStatus) && $depositStatus != ''){
                $this->admin_model->setBitcoinDepositData('status', $depositStatus);
            }
            
            if(isset($withdrawalsStatus) && $withdrawalsStatus != ''){
                $this->admin_model->setBitcoinWithdrawalsData('status', $withdrawalsStatus);
            }
            
            $this->session->set_flashdata('success', 'Bitcoin Data updated');
            redirect('/admin/bitcoinSetup', 'refresh');
        }

        $data["section"] = 'settings';
        $data['depositStatus']  = $this->admin_model->getBitcoinDepositData('status');
        $data['withdrawalsStatus']  = $this->admin_model->getBitcoinWithdrawalsData('status');
        
        $data['limit'] = array(
            'name'        => 'limit',
            'id'          => 'limit',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => 'Wallet limit',
            'value'       => $this->form_validation->set_value('limit', $this->admin_model->getWallet('limit'))
        );
        
        $data['address1'] = array(
            'name'        => 'address1',
            'id'          => 'address1',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => 'Adress 1',
            'value'       => $this->form_validation->set_value('address1', $this->admin_model->getWallet('address1'))
        );
        
        $data['address2'] = array(
            'name'        => 'address2',
            'id'          => 'address2',
            'type'        => 'text',
            'class'       => 'form-control',
            'placeholder' => 'Adress 2',
            'value'       => $this->form_validation->set_value('address2', $this->admin_model->getWallet('address2'))
        );

        $this->layout->view('admin/bitcoinSetup', $data);
    }
    
    public function maintenance(){
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        if ($this->input->post()) {
            $status = $this->input->post('status');

            $this->admin_model->setRenovationData('status', $status);

            $this->session->set_flashdata('success', 'Maintenance Data updated');
            redirect('/admin/maintenance', 'refresh');
        }
        
        $data["section"] = 'settings';
        $data['status']  = $this->admin_model->getRenovationData('status');
        $this->layout->view('admin/maintenance', $data);
    }

    public function user_autocomplete() {
        $filter = $this->input->get('term');

        echo $this->user_model->autoCompleteList($filter);
    }

    public function documents() {
        $documents = $this->user_document_model->getPendingSubset();

        $this->load->library('aws_s3');
        $this->load->config('creds_aws');

        $data['section']   = 'documents';
        $data['documents'] = $documents;

        $this->layout->view('admin/documents/index', $data);
    }

    public function document($documentId, $action) {
        $this->user_document_model->update($documentId, $action);

        $this->session->set_flashdata('success', 'The document has been updated');
        redirect('/admin/documents', 'refresh');
    }

    public function revert_trade($id) {
        $this->trade_model->revertTrade($id);
    }

    public function news() {
        if(!$this->checkPermission($this->access_level, SUPERADMIN)){
            show_404('', TRUE);
            return;
        }
        
        $this->load->model('news_model');

        $count = $this->news_model->getCount('all');
        $news  = $this->news_model->getSubset();

        $data["section"] = 'messaging';
        $data['count'] = $count;
        $data['news']  = $news;

        $this->layout->view('admin/news/index', $data);
    }

    public function news_edit($id = null, $action = null) {
        $this->load->model('news_model');

        $newsData = null;

        if ($id)
            $newsData = $this->news_model->get($id);

        if ($action == 'delete') {
            $this->news_model->delete($id);

            $this->session->set_flashdata('success', 'The news item has been deleted');
            redirect('/admin/news', 'refresh');
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('title', 'Title', 'required');
            $this->form_validation->set_rules('text',  'Text',  'required');

            if ($this->form_validation->run() == true) {
                $data = array(
                    'title'     => $this->input->post('title'),
                    'published' => $this->input->post('published') ? strtotime($this->input->post('published')) * 1000 : milliseconds(),
                    'excerpt'   => $this->input->post('excerpt') ? $this->input->post('excerpt') : substr(strip_tags($this->input->post('text')), 0, 80),
                    'text'      => $this->input->post('text'),
                    'language'  => $this->input->post('language')
                );

                if ($id)
                    $data['id'] = $id;

                $this->news_model->save($data);
                $this->session->set_flashdata('success', 'The news item has been successfully ' . ($id ? 'saved' : 'created'));
                redirect('/admin/news', 'refresh');
            }
        }

        $data['title'] = array(
            'name'        => 'title',
            'id'          => 'title',
            'class'       => 'form-control',
            'placeholder' => 'News item title',
            'value'       => $this->form_validation->set_value('title', $id ? $newsData->title : '')
        );

        $data['published'] = array(
            'name'        => 'published',
            'id'          => 'published',
            'class'       => 'form-control',
            'placeholder' => 'Leave blank to set as now',
            'value'       => $this->form_validation->set_value('published', $id ? Date('d F Y', $newsData->published / 1000) : '')
        );

        $data['excerpt'] = array(
            'name'        => 'excerpt',
            'id'          => 'excerpt',
            'class'       => 'form-control',
            'placeholder' => 'Automatically created from the text (80 chars)',
            'value'       => $this->form_validation->set_value('excerpt', $id ? $newsData->excerpt : '')
        );

        $data['text'] = array(
            'name'        => 'text',
            'id'          => 'text',
            'class'       => 'form-control',
            'placeholder' => 'HTML allowed',
            'value'       => $this->form_validation->set_value('text', $id ? $newsData->text : '')
        );

        $data['language'] = $id ? $newsData->language : 'en';

        $this->layout->view('admin/news/edit', $data);
    }
    
    public function screening_tests() {
        $data = array();
        $data["section"] = 'settings';
        
        $this->load->config('creds_bitcoin', TRUE);
        $this->load->library('BitGo');
        $cold_wallets = $this->config->item('creds_bitcoin');
        
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
        
        $correctColdWallet = 1;
        $workBitgoServer = 1;
        $listAccounts = $this->bitgo->send("/get_wallet");
        
        if(!preg_match($reg_ex, $cold_wallets['coldwallet1']) || 
           !preg_match($reg_ex, $cold_wallets['coldwallet2'])) {
            $correctColdWallet = 0;
        }

        if(!preg_match($reg_ex, $listAccounts->id)) {
            $workBitgoServer = 0;
        }
        
        $data['correctColdWallet'] = $correctColdWallet;
        $data['workBitgoServer'] = $workBitgoServer;
        $this->layout->view('admin/settings/screening_tests', $data);
    }

    /* This function should not be used more than once - to be deleted */
    public function one_time_email() {
        $this->user_model->emailUsersWithoutDeposits();
    }

    public function rebuildOrderBooks() {
        $this->order_model->rebuildOrderBooks();
    }

    public function deleteRogueTrades() {
        $this->order_model->clearOrdersForUser(7661);
        $this->order_model->clearOrdersForUser(7668);

        $this->trade_model->deleteRogueTrades(15070);
    }

    public function exportDB() {
        $processed = array();
        $next = null;

        while ($next !== '0') {
            if ($next == null)
                $next = '0';

            $iterator = $this->redis->scan($next);
            $next = $iterator[0];
            $keys = $iterator[1];

            foreach ($keys as $key) {
                $type = (string)$this->redis->type($key);
                if (!isset($processed[$type]))
                    $processed[$type] = array();

                if (in_array($key, $processed[$type]) === false)
                    $processed[$type][] = $key;
            }
        }

        $output = array();
        foreach ($processed as $type=>$keys) {
            foreach ($keys as $key) {
                $value = null;

                switch ($type) {
                    case 'string':
                        $value = (string)$this->redis->get($key);
                        break;

                    case 'list':
                        $itemsCount = $this->redis->llen($key);
                        $value = array();
                        for ($i = 0; $i < $itemsCount; $i++)
                            $value[] = $this->redis->lindex($key, $i);

                        break;

                    case 'hash':
                        $value = $this->redis->hgetall($key);

                        break;

                    case 'set':
                        $value = $this->redis->smembers($key);

                        break;

                    case 'zset':
                        $value = $this->redis->zrange($key, 0, -1, 'WITHSCORES');

                        break;
                }

                if (!empty($value)) {
                    $line = array(
                        'type'  => $type,
                        'name'  => $key,
                        'value' => $value
                    );

                    $output[] = $line;
                }
            }
        }

        echo json_encode($output);
    }

    public function importDB() {
        $data = json_decode(file_get_contents(FCPATH . 'db.txt'));

        $this->redis->select(3);

        foreach ($data as $block) {
            $type  = $block->type;
            $name  = $block->name;
            $value = $block->value;

            switch ($type) {
                case 'string':
                    $this->redis->set($name, $value);
                    break;

                case 'list':
                    $this->redis->rpush($name, (array)$value);

                    break;

                case 'hash':
                    $this->redis->hmset($name, (array)$value);

                    break;

                case 'set':
                    $this->redis->sadd($name, (array)$value);

                    break;

                case 'zset':
                    $this->redis->zadd($name, (array)$value);

                    break;
            }
        }
    }
    
    public function checkPermission($userAccess, $level) {
        if($userAccess <= $level) {
            return true;
        }
        return false;
    }
    //Todo admin_emails 
    public function isAdmin($user) {
        $allowed = $this->config->item('admin_emails');
        if(in_array($user->email, $allowed) === true || $user->isadmin) {
            return true;
        }
        
        return false;
    }
    
    public function hasRefferal($userId) {
        return $this->referral_model->getCountReferals($userId);
    }
}
