<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Api.php');
//next line 5 is a new commit from insart
include_once(APPPATH . 'controllers/_api/Public_api_v2.php');

class Engine extends Api {

    public function __construct() {
        parent::__construct();
        //show_404();
        $this->user   = $this->user_model->loadFromSession();
        $this->userId = $this->user !== 'guest' ? _numeric($this->user->_id) : null;

        // Load language stuff
        $language = $this->user !== 'guest' ? $this->user->language : $this->language;

        $this->lang->load('controllers', $language);
        $this->lang->load('models', $language);
        $this->lang->load('misc', $language);

        if ($this->postData) {
            foreach ($this->postData as $key => $value) {
                if (empty($this->auth)) $this->auth = new stdClass();
                if (in_array($key, array('random', 'time', 'signature'))) $this->auth->{$key} = $value;
                else $this->properties->{$key} = $value;
            }

            $this->_validateFields(array('random' => 'Random', 'time' => 'Time', 'signature' => 'Signature'));
        }
    }

    public function book($book = null) {
        if ($book == null)
            return $this->_error(20, _l('invalid_order_book'));

        if (!isset($this->meta_model->books[$book]))
            return $this->_error(20, _l('invalid_order_book', $book));

        $bookBuy  = 'orders:buy:' . $book;
        $bookSell = 'orders:sell:' . $book;

        // LOCK
        $this->redis_model->lock();

        $buys   = $this->order_model->getOrders($bookBuy, 10);
        $sells  = $this->order_model->getOrders($bookSell, 10);
        $trades = $this->trade_model->getTrades($book, 10);

        $out = array(
            'book'   => $book,
            'bids'   => array_map('_anonymiseOrder', $buys),
            'asks'   => array_map('_anonymiseOrder', $sells),
            'trades' => array_map('_anonymiseTrade', $trades)
        );

        $out['stats'] = array(
            'lastTradePrice' => $this->trade_model->getLastTradePrice($book),
            'rollingVolume'  => $this->trade_model->getRollingVolume($book)
        );

        if (isset($this->user) && $this->user !== 'guest') {
            $orders   = $this->order_model->getForUser($this->userId, $book, 1000);
            $trades   = $this->trade_model->getForUser($this->userId, $book, 10);
            $balances = $this->user_balance_model->get($this->userId);

            $user = array(
                'orders'   => array_map('_anonymiseUserOrder', $orders),
                'trades'   => _anonymiseUserTrades($trades, $this->userId),
                'balances' => _availableBalances($balances)
            );

            $out['user'] = $user;
        }

        // UNLOCK
        $this->redis_model->unlock();

        $this->_display($out);
    }

    public function panels($book = null) {
        if ($book == null)
            return $this->_error(20, _l('invalid_order_book'));

        if (!isset($this->meta_model->books[$book]))
            return $this->_error(20, _l('invalid_order_book', $book));

        $max = $this->input->get('max');

        $bookBuy  = 'orders:buy:' . $book;
        $bookSell = 'orders:sell:' . $book;

        // LOCK
        $this->redis_model->lock();

        $buys   = $this->order_model->getOrders($bookBuy, $max);
        $sells  = $this->order_model->getOrders($bookSell, $max);
        $trades = $this->trade_model->getTrades($book, $max);

        $out = array(
            'book'   => $book,
            'bids'   => array_map('_anonymiseOrder', $buys),
            'asks'   => array_map('_anonymiseOrder', $sells),
            'trades' => array_map('_anonymiseTrade', $trades)
        );

        // UNLOCK
        $this->redis_model->unlock();

        $this->_display($out);
    }

    public function userpanels() {
        // LOCK
        $this->redis_model->lock();

        $max = $this->input->get('max');

        $orders   = $this->order_model->getForUser($this->userId, null, $max ? $max : -1);
        $trades   = $this->trade_model->getForUser($this->userId, null, $max ? $max : 10);
        $balances = $this->user_balance_model->get($this->userId, null);

        $out = array(
            'orders'   => array_map('_anonymiseUserOrder', $orders),
            'trades'   => _anonymiseUserTrades($trades, $this->userId),
            'balances' => _availableBalances($balances),
//            'balances_full' => _fullBalances($balances),
//            'balances_locked' => _lockedBalances($balances)
        );

        // UNLOCK
        $this->redis_model->unlock();

        $this->_display($out);
    }

    public function buy() {
        $this->_processOrder('buy');
    }

    public function buymarket() {
        $this->_processOrder('buy', 'market');
    }

    public function sell() {
        $this->_processOrder('sell');
    }

    public function sellmarket() {
        $this->_processOrder('sell', 'market');
    }

    private function _processOrder($type, $mode='limit') {
        $this->properties->method = 'api'; // Making sure the order will be created with the correct method

        switch ($mode) {
            case 'limit':
                parent::_process($type);
                break;

            case 'market':
                parent::_processMarket($type);
                break;
        }

        $this->_display('OK');
    }

    public function cancel() {
        $orderUid = $this->_getProperty('orderid');

        if (!$this->_check_signature($orderUid))
            $this->_error(11, _l('invalid_signature'));

        if ($this->user === 'guest')
            $this->_error(12, _l('invalid_user_credentials'));

        if (!parent::_cancel($orderUid))
            $this->_error(106, _l('request_not_found'));
        else $this->_display('ok');
    }
    
    public function cancelFutureOrder() {
        $orderId = $this->_getProperty('orderid');
        
        $future = $this->future_model->get($orderId);
        
        $userId = $this->user->id;
        
        if (!$this->_check_signature($orderId))
            $this->_error(11, _l('invalid_signature'));

        if ($this->user === 'guest')
            $this->_error(12, _l('invalid_user_credentials'));
        
        $this->future_model->cancel($orderId);
        
        $green_cumulative = 0;
        $red_cumulative = 0;
        
        $this->future_model->setType($future->typeContract);
        $redItems = $this->future_model->find(array('open_short','close_long'), 'open',true, "ASC");
        $greenItems = $this->future_model->find(array('close_short','open_long'), 'open');
        
        $count = $this->future_model->getCountForUser($userId);
        $countPositions = $this->position_model->getCountForUser($userId);
        
        $history = $this->future_model->getOrdersForUser(10,array('filled','canceled'));
        $items = $this->future_model->getOrdersForUser(10);
        $itemsPosition = $this->position_model->getPositionsForUser(10);
        
        for($i = 0; $i < count($greenItems) && $i < 5; $i++) {
            $green_cumulative += $greenItems[$i]->amount;
        }
                
        $response = array(
                        'items' => adaptiveFutureOrderToUI($items),
                        'itemsPosition' => adaptiveUIPosition($itemsPosition),
                        'red_cumulative' => $red_cumulative,
                        'green_cumulative' => $green_cumulative,
                        'red_items' => $redItems,
                        'green_items' => $greenItems,
                        'history' => adaptiveFutureOrderToUI($history),
                        'balances' => $this->user_balance_model->get($userId)
                        );
        print_r(json_encode($response));
        //$this->_display('ok');
    }
    
    public function endDeliveryPositions() {
        $this->position_model->performDelivery();
    }
    
    public function getDeliveryTime() {
        echo $this->future_model->getDeliveryDateTime();
    }
    
    public function removeNote() {
        $noteId = $this->_getProperty('noteid');
        
        if ($this->user === 'guest')
            $this->_error(12, _l('invalid_user_credentials'));
        $this->note_model->removeNote($noteId);
        $this->_display('ok');
    }
    
    public function sendOtp() {
        $userId = $this->_getProperty('userid');
        $signdata = $this->_getProperty('signdata');
        $json = json_decode($this->_getProperty('jsondata'));
                
        if ($this->user === 'guest')
            $this->_error(12, _l('invalid_user_credentials'));
            
        $response = $this->user_model->sendOtp($userId, $signdata, $json);
        print_r(json_encode($response));
        //$this->_display('ok');
    }
    
    public function shortRegister() {
        $json = json_decode($this->_getProperty('jsondata'));
        $phase = 1;
        
        $userData = array(
            'email'      => $json->email,
            'first_name' => $json->first_name,
            'last_name'  => $json->last_name,
            'country'    => $json->country,
            'language'   => $this->config->item('default_lang')
        );

        if ($userId = $this->user_model->save($userData, null, $phase)) {
            $data['userId'] = $userId;
            $phase = 2;
            
            $user = $this->user_model->get($userId);
            
            $salt     = generateRandomString(20);
            $password = $json->password;
            
            $tempPassword = $this->api_security->hash($userId, $password);
            $userDataPhase2 = array(
                'salt'     => $salt,
                'password' => $this->api_security->hash($tempPassword, $salt),
                'pin'      => sha1($json->pin),
                'active'   => 1
            );
            
            if ($this->user_model->save($userDataPhase2, $userId, $phase)) {
                // This block is used to grab a new btc address for that user if bitcoind is enabled
                /*
                $status = $this->bitcoin_model->getStatus('bitcoind');
                if ($status != 'disabled') {
                    $this->load->library('easybitcoin');
                    $this->easybitcoin->getaccountaddress('user:' . $userId);
                }
                */

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
                    'from_email' => 'shrikant@alulimtech.com',
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
                

                if ($referrerData) {
                    $this->referral_model->addToUser($userId, $referrerData->id);
                    delete_cookie('referrer');
                }

                // Log registration event
                $this->event_model->add($userId,'register');

                //$this->layout->setTitle(_l('registration_complete'))->view('user/register/complete', $data);
                //return;
            } else {
                $data['errors'] = _l('unexpected_error');
            }

        } else {
            $data['errors'] = _l('unexpected_error');
        }      
        
        print_r(json_encode($data));
    }
    
    public function changePgpSupport() {
        $userId = $this->_getProperty('userid');
        $type = $this->_getProperty('type');
        $data = array();
        $data['pgp_status'] = $type;
        $this->user_model->update($userId, $data);
        $this->_display('ok');
    }

    private function _check_signature() {
        $this->load->library('api_security');

        $args = func_get_args();
        array_unshift($args, $this->auth->signature, $this->auth->random, $this->auth->time);

        return call_user_func_array(array($this->api_security, 'check'), $args);
    }
    //todo does this function get used?
    public function check_bitcoind() {
        
        $this->load->model('admin_model');
        $this->load->model('bitcoin_model');
        if($this->admin_model->getBitcoinDepositData('status') != "disabled"){
            $this->bitcoin_model->checkBitcoind();
        }
        echo "Request done: ".date("Y-m-d H:i:s");
    }
    
    public function bitcoinAutoWithdrawalsRequest() {
        $this->load->model('admin_model');
        $this->load->model('bitcoin_model');
        sleep(5);
        if($this->admin_model->getBitcoinWithdrawalsData('status') != "disabled"){
            $this->bitcoin_model->bitcoinAutoWithdrawals();
        }
        echo "Request AutoWithdrawals done: ".date("Y-m-d H:i:s");
    }
    //todo getbotinformation added by insart
    public function getBotInformation() {
        $clientId1 = $this->input->get('clientId1');
        $clientId2 = $this->input->get('clientId2');

        $data = array('status' => $this->admin_model->getBotStatus('status'),
                      'limit' => $this->admin_model->getBotLimit('limit'),
                      'max_value' => $this->admin_model->getBotMaxValue('max_value'),
                      'info' => $this->getInfo('btc_cad'),
                      'order_book' => $this->getOrderBook(),
                      'user_balance1' => $this->user_balance_model->get($clientId1),
                      'user_balance2' => $this->user_balance_model->get($clientId2));
        print_r(json_encode($data));
    }
}
