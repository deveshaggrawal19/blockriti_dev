<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Bitcoin_model extends Redis_model {
    
    const BITCOIN_DEPOSIT = 2; //0-2 (3)

    private $cachedDeposits;

    public function __construct() {
        parent::__construct();
    }
    
    public function  getBitcoinDepositConst() {
        return ($this::BITCOIN_DEPOSIT + 1);
    }

    public function setup() {
        /* These will be used as a fallback if blockchain is not available */
        $this->redis->del('btc_address');

        $addresses = $this->config->item('backupaddresses');

        foreach ($addresses as $address) {
            $this->redis->sadd('btc_address', $address);
        }
    }

    public function get() {
        $address = $this->redis->srandmember('btc_address');
        $this->redis->srem('btc_address', $address);

        return $address;
    }

    public function getStatus($what) {
        return $this->redis->get($what . ':status');
    }

    public function getBlockchainAddress($userId) {
        try {
            $status = $this->getStatus('blockchain');
            if ($status == 'disabled') return false;

            $secret     = $this->redis->get('blockchain:secret');
            $btcAddress = $this->redis->get('blockchain:address');

            $callback   = 'https://'.MAIN_DOMAIN.'/payment/bitcoin_blockchain?user=' . _numeric($userId) . '&secret=' . $secret;

            $url = 'https://blockchain.info/api/receive?method=create&address=' . $btcAddress . '&callback=' . urlencode($callback);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            $output = curl_exec($ch);
            curl_close($ch);

            if (strpos($output, '<html') === FALSE)
                return json_decode($output);
            return null;
        }
        catch (Exception $e) {
            return null;
        }
    }

    public function getFromBitcoind($userId) {
        $status = $this->getStatus('bitcoind');
        if ($status == 'disabled') return false;

        $address = $this->user_model->getProperty($userId, 'bitcoin_address');
        //$address = false;
        if($address{0} != "2" && $address{0} != "3") {
            $address = false;
        }
        
        if (!$address) {
            /*
            //$this->load->library('easybitcoin');
            $address = $this->easybitcoin->getnewaddress('user:' . $userId);
            */
            /*
            $this->load->library('coinkitelibrary');
            $account = $this->coinkitelibrary->getP2SHAccount();
            sleep(3);
            $data = array(
                "account" => $account->account->CK_refnum
            );
            
            $receive = $this->coinkitelibrary->send("/v1/new/receive", "PUT", $data);
            $address = $receive->result->address;
            */
            $this->load->library('BitGo');
            $addressObject = $this->bitgo->send("/create_address");
            $address = $addressObject->address;

            $this->user_model->setProperty($userId, 'bitcoin_address', $address);
            $this->setUserIdByAdress($address, 'userid', $userId);
        }
        
        if(!$this->redis->exists("address:".$address)){
            $this->setUserIdByAdress($address, 'userid', $userId);
        }

        return $address;
    }
    
    public  function getUserIdByAdress($address, $property) {
        return $this->redis->hget('address:'.$address,  $property);
    }
    
    public  function setUserIdByAdress($address, $property, $value) {
        return $this->redis->hset('address:'.$address,  $property, $value);
    }

    public function getStoreAddress($storeKey) {
        $storeData = $this->merchant_model->get($storeKey);
        $data = (array)$storeData;
        if(array_key_exists("address", $data)){
            return $data['address'];
        } else {
            $this->load->library('coinkitelibrary');
            $account = $this->coinkitelibrary->getP2SHAccount();
            $data = array(
                "account" => $account->account->CK_refnum
            );
            
            $receive = $this->coinkitelibrary->send("/v1/new/receive", "PUT", $data);
            $address = $receive->result->address;
            if (!$address)
                return false;
            $data['address'] = $address;

            $this->merchant_model->updateStore($data['client'], $storeKey, $data);
            return $address;
        }
        /*
        //$this->load->library('easybitcoin');

        return $this->easybitcoin->getnewaddress('merchant:store:' . $storeKey);
        */
    }

    /*
     We check each block as and when we get them, that way we should not fall for the malleability issue
     that many others have been plagued. We can reset the full history at any time by passing true to this
     function.
    */
    /*
    public function checkBitcoind($reset = false) {
        //$this->load->library('easybitcoin');

        if ($reset)
            $this->redis->del('bitcoind:lastblock');

        $lastBlockHash = $this->redis->get('bitcoind:lastblock');

        // List all transactions since this last block if it is found
        if ($lastBlockHash != '')
            $transactionsJSON = $this->easybitcoin->listsinceblock($lastBlockHash);
        else $transactionsJSON = $this->easybitcoin->listsinceblock();

        $lastBlockHash = $transactionsJSON['lastblock'];
        $transactions  = $transactionsJSON['transactions'];

        $this->redis->set('bitcoind:lastblock', $lastBlockHash);

        foreach ($transactions as $transaction) {
            $account  = $transaction['account'];
            $category = $transaction['category'];
            $amount   = $transaction['amount'];
            $txid     = $transaction['txid'];
            $address  = $transaction['address'];

            unset($transaction['walletconflicts']);

            // Only interested in accounts that have the particular format user:XXX
            if (preg_match('/^user:\d+$/', $account)) {
                // Is this a valid user ID?
                if ($this->redis->exists($account)) {
                    $userId = _numeric($account);

                    // Only want money coming in
                    if ($category == 'receive') {
                        // If that transaction does not exist in our system
                        if (!$this->redis->sismember('bitcoind:confirmed:txid', $txid) && !$this->redis->sismember('bitcoind:unconfirmed:txid', $txid)) {
                            $data = array(
                                'client'   => $userId,
                                'method'   => 'btc',
                                'currency' => 'btc',
                                'amount'   => $amount
                            );

                            $this->deposit_model->add($data, $transaction);

                            $this->redis->sadd('bitcoind:unconfirmed:txid', $txid);
                        }
                    }
                }
                else {
                    // Dodgy transaction received, saving it for followup
                    if (!$this->redis->sismember('bitcoind:dodgy:txid', $txid)) {
                        $this->redis->sadd('bitcoind:dodgy:txid', $txid);
                    }
                }
            }
            else if (preg_match('/^merchant:store:[a-z]+$/i', $account)) {
                if ($this->redis->exists($account)) { // Valid store?
                    if ($category == 'receive') {
                        // We are dealing with a store so let's do the checks and move things around
                        $this->load->model('merchant_model');

                        if ($this->merchant_model->paymentReceived($address, $transaction))
                            $this->distributeCoins($account, $amount);
                    }
                }
            }
        }

        $this->_checkUnconfirmedTransactions();
    }
    
    */
    
    /*
    //Coinkite 
    public function checkBitcoind($reset = false) {
        //$this->load->library('easybitcoin');
        $this->load->model('deposit_model');
        $this->load->library('coinkitelibrary');

        if ($reset)
            $this->redis->del('bitcoind:lastblock');
            
        $lastBlockHash = $this->redis->get('bitcoind:lastblock');
        $datetime1 = new DateTime(date("Y-m-d"));
        //$lastBlockTime = new DateTime("2015-04-13");

        try {
            $lastBlockTime = new DateTime($lastBlockHash);
            
        } catch(Exception $e) {
            $lastBlockHash = '';
            $lastBlockTime = null;
        }
        
        // List all transactions since this last block if it is found
        if ($lastBlockHash != ''){
            $interval = $datetime1->diff($lastBlockTime);

            if($interval->format('%a') == "0"){
                $datePeriod = "today";
            } else {
                $datePeriod = $lastBlockTime->format("Y-m-d")."/".$interval->format('P%aD');
            }
            
            $data = array(
                "period" => $datePeriod
            );
            
            
            $events = $this->coinkitelibrary->send("/v1/list/events", "GET", $data);
        } else {
            $events = $this->coinkitelibrary->send("/v1/list/events");
        }
        
        if(count($events->results) > 0) {
            //$address = $this->getFromBitcoind($userId);
            $this->redis->set('bitcoind:lastblock', $datetime1->format("Y-m-d"));
            foreach($events->results as $transaction) {
                $address = $transaction->credit_txo->coin->address;
                $category = $transaction->type;
                $amount = $transaction->credit_txo->amount;
                $txid = $transaction->credit_txo->txo;
                $userId = $this->getUserIdByAdress($address,'userid');
                if ($this->redis->exists("user:".$userId)) {
                    // Only want money coming in
                    if ($category == 'Credit') {
                        // If that transaction does not exist in our system
                        if (!$this->redis->sismember('bitcoind:confirmed:txid', $txid) && !$this->redis->sismember('bitcoind:unconfirmed:txid', $txid)) {

                            $data = array(
                                'client'   => $userId,
                                'method'   => 'btc',
                                'currency' => 'btc',
                                'amount'   => $amount
                            );
                            
                            $txParam = explode(":", $transaction->credit_txo->txo);
                            
                            $details = array(
                                'address' => $address,
                                'confirmations' => $transaction->credit_txo->confirmations,
                                'trabsactionid' => $txParam[0]
                            );
                            $this->deposit_model->add($data, $details);
                            
                            $this->redis->sadd('bitcoind:unconfirmed:txid', $txid);
                            
                            $this->user_model->setProperty($userId, 'bitcoin_address', null);
                        }
                    }
                } else {
                    // Dodgy transaction received, saving it for followup
                    if (!$this->redis->sismember('bitcoind:dodgy:txid', $txid)) {
                        $this->redis->sadd('bitcoind:dodgy:txid', $txid);
                    }
                }
                
            }
            
            $this->_checkUnconfirmedTransactions();

        }
        
        //$transactionsJSON = $this->easybitcoin->listsinceblock($lastBlockHash);
        //else $transactionsJSON = $this->easybitcoin->listsinceblock();
        
        /*
        $lastBlockHash = $transactionsJSON['lastblock'];
        $transactions  = $transactionsJSON['transactions'];

        $this->redis->set('bitcoind:lastblock', $lastBlockHash);

        foreach ($transactions as $transaction) {
            $account  = $transaction['account'];
            $category = $transaction['category'];
            $amount   = $transaction['amount'];
            $txid     = $transaction['txid'];
            $address  = $transaction['address'];

            unset($transaction['walletconflicts']);

            // Only interested in accounts that have the particular format user:XXX
            if (preg_match('/^user:\d+$/', $account)) {
                // Is this a valid user ID?
                if ($this->redis->exists($account)) {
                    $userId = _numeric($account);

                    // Only want money coming in
                    if ($category == 'receive') {
                        // If that transaction does not exist in our system
                        if (!$this->redis->sismember('bitcoind:confirmed:txid', $txid) && !$this->redis->sismember('bitcoind:unconfirmed:txid', $txid)) {
                            $data = array(
                                'client'   => $userId,
                                'method'   => 'btc',
                                'currency' => 'btc',
                                'amount'   => $amount
                            );

                            $this->deposit_model->add($data, $transaction);

                            $this->redis->sadd('bitcoind:unconfirmed:txid', $txid);
                        }
                    }
                }
                else {
                    // Dodgy transaction received, saving it for followup
                    if (!$this->redis->sismember('bitcoind:dodgy:txid', $txid)) {
                        $this->redis->sadd('bitcoind:dodgy:txid', $txid);
                    }
                }
            }
            else if (preg_match('/^merchant:store:[a-z]+$/i', $account)) {
                if ($this->redis->exists($account)) { // Valid store?
                    if ($category == 'receive') {
                        // We are dealing with a store so let's do the checks and move things around
                        $this->load->model('merchant_model');

                        if ($this->merchant_model->paymentReceived($address, $transaction))
                            $this->distributeCoins($account, $amount);
                    }
                }
            }
        }

        $this->_checkUnconfirmedTransactions();
        
    }
    */
    
    public function checkBitcoind($reset = false) {
        //$this->load->library('easybitcoin');
        $this->load->model('deposit_model');
        $this->load->library('BitGo');

        if ($reset)
            $this->redis->del('bitcoind:lastblock');
            
        $lastBlockHash = '';//$this->redis->get('bitcoind:lastblock');
        $txResponse = $this->bitgo->send("/get_wallet_transactions");
        $listAccount = $this->bitgo->send("/get_wallet");
        //print_r($txResponse);
        //echo count($txResponse->transactions);
        if(count($txResponse->transactions) > 0) {
            //$address = $this->getFromBitcoind($userId);
            $this->redis->set('bitcoind:lastblock', $txResponse->transactions[0]->id);
            foreach($txResponse->transactions as $transaction) {
                $txid = $transaction->id;
                if($lastBlockHash != $txid) {
                    
                foreach($transaction->entries as $ents) {
                    //print_r($ents);
                    if($listAccount->id == $ents->account){
                        $amount = $ents->value / 100000000;
                    }
                }
                
                $address = null;
                foreach($transaction->outputs as $output) {
                    //print_r($output);
                    //echo $output->account."<br/><br />";
                    if($this->getUserIdByAdress($output->account,'userid')){
                        $address = $output->account;
                        //echo $address."<br/><br />";
                        break;
                    }
                }
                
                    if(!is_null($address)){
                        if($this->getUserIdByAdress($address,'userid') && $amount > 0){
                            
                            $userId = $this->getUserIdByAdress($address,'userid');                    
                            
                            if ($this->redis->exists("user:".$userId)) {
                                // Only want money coming in
            
                                // If that transaction does not exist in our system
                                if (!$this->redis->sismember('bitcoind:confirmed:txid', $txid) && !$this->redis->sismember('bitcoind:unconfirmed:txid', $txid)) {
            
                                    $data = array(
                                        'client'   => $userId,
                                        'method'   => 'btc',
                                        'currency' => 'btc',
                                        'amount'   => $amount
                                    );
                                    
                                    
                                    $details = array(
                                        'address' => $address,
                                        'confirmations' => $transaction->confirmations,
                                        'trabsactionid' => $txid
                                    );
                                    
                                    //echo "Details: ".$details['address'];
                                    if($this->getUserIdByAdress($address,'userid') != ''){
                                        $this->deposit_model->add($data, $details);
                                        
                                        $this->redis->sadd('bitcoind:unconfirmed:txid', $txid);
                                        
                                        $this->user_model->setProperty($userId, 'bitcoin_address', null);
                                    }
                                }
                                
                            } else {
                                // Dodgy transaction received, saving it for followup
                                if (!$this->redis->sismember('bitcoind:dodgy:txid', $txid)) {
                                    $this->redis->sadd('bitcoind:dodgy:txid', $txid);
                                }
                            }
                        
                        }
                    }
                } else {
                    break;
                }
            }
            
            $this->_checkUnconfirmedTransactions($address);
        }
        
        //$transactionsJSON = $this->easybitcoin->listsinceblock($lastBlockHash);
        //else $transactionsJSON = $this->easybitcoin->listsinceblock();
        
    }
    /*
     This function will go through the unconfirmed transactions that are on the system.
     Looping through them all, if the number of confirmations goes above BITCOIN_DEPOSIT. Now it's 2' then we consider the
     transaction done and we move the amount to the user's balance.
    */
    public function _checkUnconfirmedTransactions($walletAddress) {
        
        $transactionIds = $this->redis->smembers('bitcoind:unconfirmed:txid');
        foreach ($transactionIds as $transactionId) {
            //$this->redis->srem('bitcoind:unconfirmed:txid', $transactionId);
            $array = explode(":", $transactionId);
//            if(count($array) < 2){
                $this->processTransaction($transactionId, $walletAddress);
//            } else {
//                $this->redis->srem('bitcoind:unconfirmed:txid', $transactionId);
//            }
            //sleep(2);
        }
    }
    
    public function processTransaction($txid, $walletAddress) {
        /*
        //$this->load->library('easybitcoin');
        $transaction = $this->easybitcoin->gettransaction($txid);
        */
        $this->load->model('deposit_model');
        $this->load->library('BitGo');
        $data = array(
            "txId" => $txid
        );
        $transaction = $this->bitgo->send("/get_transaction", "POST", $data);
        $account = '';
        foreach($transaction->outputs as $output) {
            if($this->getUserIdByAdress($output->account,'userid')){
                $address = $output->account;
                $amount = $output->value;
                $userId = $this->getUserIdByAdress($address,'userid');
                $account = "user:".$userId;
                /*
                echo "userId: ".$userId."<br />";
                echo "Amount: ".$amount."<br />";
                echo "Address: ".$address."<br /><br />";
                */
                break;
            }
        }  
        
        $confirmations = $transaction->confirmations;
        if($confirmations > 2 && $this->redis->sismember('bitcoind:confirmed:txid', $txid)) {
            $this->redis->srem('bitcoind:unconfirmed:txid', $txid);
            return true;
        }
        //echo $amount;
        if (preg_match('/^user:\d+$/', $account)) {
            $userId = _numeric($account);
            
            
            if (!$this->redis->sismember('bitcoind:unconfirmed:txid', $txid)) {
                //echo "Hello Uncon";
                $data = array(
                    'client'   => $userId,
                    'method'   => 'btc',
                    'currency' => 'btc',
                    'amount'   => $amount
                );
                
                $details = array(
                    'address' => $address,
                    'confirmations' => $confirmations
                );

                $this->deposit_model->add($data, $details);

                $this->redis->sadd('bitcoind:unconfirmed:txid', $txid);
                $this->createTransactionsLookup();

                $this->user_model->setProperty($userId, 'bitcoin_address', null);

                // Update to user's screen
                $notification = array(
                    'txid'          => $txid,
                    'amount'        => $amount,
                    'currency'      => makeSafeCurrency('BTC'),
                    'address'       => $address,
                    'confirmations' => $confirmations,
                    'datetime'      => date('m/d/Y H:i', $this->now / 1000)
                );
                $this->notification_model->direct('status', $address, $notification);
                $this->notification_model->flush();

                echo "[deposit] $txid unconfirmed - $confirmations confirmations";
            } else {
                //echo "Good: ".$address;
                //echo "This transaction already exists";
                //echo "Address Confirmed: ".$address."<br />";
                // use caching to speed things up a little
                /*
                if (isset($this->cachedDeposits[$userId])) {
                    $deposits = $this->cachedDeposits[$userId];
                }
                else {
                */
                $deposits = $this->deposit_model->findBitcoinDeposits($userId, $address);
                $this->cachedDeposits[$userId] = $deposits;
                //}
                
                //print_r($deposits);

                foreach ($deposits as $deposit) {
                    //echo "ADDDDRRREEESSSS: ".$deposit->details->address."<br />";
                    foreach($transaction->entries as $ents) {
                        //echo "EEEEEnts->account: ".$ents->account."<br />";
                        if($deposit->details->address == $ents->account){
                            //echo "Ents->account: ".$ents->account;
                            $amount = rCurrency('btc',$ents->value / 100000000, ''); 
                        }
                    }
                    
                    //echo 'Amount : '.$amount."<br />";
                    if ($deposit->status != 'complete' && $deposit->amount == $amount) {
                        $depositId = $deposit->id;
                        
                        
                        if ((int)$deposit->details->confirmations != (int)$confirmations) {
                            $data['confirmations'] = $confirmations;
                            $this->deposit_model->updateDetails($depositId, $data);

                            echo "[deposit] $txid updated - $confirmations confirmations";

                            $notification = array(
                                'txid'          => $txid,
                                'confirmations' => $confirmations
                            );
                            $this->notification_model->direct('status', $txid, $notification);
                            $this->notification_model->flush();
                        }

                        echo 'Confirmations: '.$confirmations;
                        if ((int)$confirmations > self::BITCOIN_DEPOSIT) {
                            $this->deposit_model->receive($depositId, $userId);

                            $this->redis->smove('bitcoind:unconfirmed:txid', 'bitcoind:confirmed:txid', $txid);

                            $this->distributeCoins($account, $amount);

                            echo "\n[deposit] $txid confirmed!";

                            // Check whether this is an auto-sell
                            $userAutoSell = $this->getAutoSellAddress($userId);
                            if ($userAutoSell) {
                                foreach ($userAutoSell as $currency => $_address) {
                                    if ($_address == $address) {
                                        $this->_processAutoSell($userId, $amount, $currency);

                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->caching_model->delete('user:' . $userId . ':deposits:pending');
        }
        
        return true;
        
    }

    private function _processAutoSell($userId, $amount, $currency) {
        $this->lock();

        $balances = $this->user_balance_model->get($userId);

        // Make sure we have enough BTC to perform the autosell
        $available = $balances->btc_available;
        if (bccomp($available, $amount, getPrecision('btc')) > -1) {
            $this->load->model('order_model');
            $this->load->model('trade_model');

            // Add funds to locked balance
            $balances->btc_locked = bcadd($balances->btc_locked, $amount, getPrecision('btc'));
            $this->user_balance_model->save($userId, $balances);

            // Reaching this point will prove the order is fine
            $orderData         = new stdClass();
            $orderData->major  = 'btc';
            $orderData->minor  = $currency;
            $orderData->method = 'autosell';
            $orderData->amount = $amount;
            $orderData->rate   = '50';

            $book = $orderData->major . '_' . $orderData->minor;

            $order = $this->order_model->create($userId, $orderData, 'sell');

            $this->order_model->add($order);
            $this->trade_model->processTrades($orderData->major, $orderData->minor);
            $this->trade_model->updateTradeStats($book);

            $package = array(
                'book' => $book
            );

            $this->notification_model->direct('user_update', 'user:' . $this->userId, $package);
            $this->notification_model->broadcast('book_update', $package);
            $this->notification_model->flush();
        }

        // UNLOCK
        $this->unlock();
    }

    public function getAutoSellAddress($userId) {
        return $this->flatten($this->redis->hgetall('user:' . $userId . ':autosell'));
    }

    public function setAutoSellAddress($userId) {
        //$this->load->library('easybitcoin');
        
        $this->load->library('coinkitelibrary');
        $account = $this->coinkitelibrary->getP2SHAccount();
        $data = array(
            "account" => $account->account->CK_refnum
        );
            
        $data = array();

        $this->load->model('meta_model');
        $currencies = $this->meta_model->getFiatCurrencies();

        sort($currencies);
        $receive = $this->coinkitelibrary->send("/v1/new/receive", "PUT", $data);
        $address = $receive->result->address;
        foreach ($currencies as $currency)
            $data[$currency] = $address;
            //$data[$currency] = $this->easybitcoin->getnewaddress('user:' . $userId);
            

        $this->redis->hmset('user:' . $userId . ':autosell', $data);

        return (object)$data;
    }

    /*
    public function getSummary() {
        //$this->load->library('easybitcoin');

        $transactions = $this->easybitcoin->listreceivedbyaddress();

        $userTransactions = array();
        $userTotals       = array();
        foreach ($transactions as $transaction) {
            $account = $transaction['account'];

            if (preg_match('/^user:\d+$/', $account)) {
                $userId = _numeric($account);

                $userTransactions[$userId][] = $transaction;
                $userTotals[$userId][] = $transaction;
            }
        }
    }
    */

    /*
    public function processTransaction($txid) {
        
        //$this->load->library('easybitcoin');
        $transaction = $this->easybitcoin->gettransaction($txid);
        
        
        //$this->load->library('coinkitelibrary');
        //$transaction = $this->coinkitelibrary->send("/v1/search/txo/".$txid);

        if (!$transaction)
            return false;

        $details = $transaction['details'];
        $transactionDetails = $details[0];

        // Sometimes the details contain more than 1 entry (eg sent from same server)
        // So chose the one that has the "receive" method
        if (count($details) > 1) {
            foreach ($details as $detail) {
                if ($detail['category'] == 'receive') {
                    $transactionDetails = $detail;
                    break;
                }
            }
        }

        $account       = $transactionDetails['account'];
        $address       = $transactionDetails['address'];
        $amount        = rCurrency('btc', $transactionDetails['amount'], '');
        $confirmations = $transaction['confirmations'];

        unset($transaction['walletconflicts']);
        unset($transaction['details']);

        $transaction = array_merge($transaction, $transactionDetails);

        if (preg_match('/^user:\d+$/', $account)) {
            $userId = _numeric($account);

            if (!$this->redis->sismember('bitcoind:unconfirmed:txid', $txid)) {
                $data = array(
                    'client'   => _numeric($account),
                    'method'   => 'btc',
                    'currency' => 'btc',
                    'amount'   => $amount
                );

                $this->deposit_model->add($data, $transaction);

                $this->redis->sadd('bitcoind:unconfirmed:txid', $txid);
                $this->createTransactionsLookup();

                $this->user_model->setProperty($userId, 'bitcoin_address', null);

                // Update to user's screen
                $notification = array(
                    'txid'          => $txid,
                    'amount'        => $amount,
                    'currency'      => makeSafeCurrency('BTC'),
                    'address'       => $address,
                    'confirmations' => $confirmations,
                    'datetime'      => date('m/d/Y H:i', $this->now / 1000)
                );
                $this->notification_model->direct('status', $address, $notification);
                $this->notification_model->flush();

                echo "[deposit] $txid unconfirmed - $confirmations confirmations";
            }
            else {
                // use caching to speed things up a little
                if (isset($this->cachedDeposits[$userId])) {
                    $deposits = $this->cachedDeposits[$userId];
                }
                else {
                    $deposits = $this->deposit_model->findBitcoinDeposits($userId, $address);
                    $this->cachedDeposits[$userId] = $deposits;
                }

                foreach ($deposits as $deposit) {
                    if ($deposit->status != 'complete' && $deposit->amount == $amount) {
                        $depositId = $deposit->id;

                        if ((int)$deposit->details->confirmations != (int)$confirmations) {
                            $data['confirmations'] = $confirmations;
                            $this->deposit_model->updateDetails($depositId, $data);

                            echo "[deposit] $txid updated - $confirmations confirmations";

                            $notification = array(
                                'txid'          => $txid,
                                'confirmations' => $confirmations
                            );
                            $this->notification_model->direct('status', $txid, $notification);
                            $this->notification_model->flush();
                        }

                        
                        if ((int)$confirmations > self::BITCOIN_DEPOSIT) {
                            $this->deposit_model->receive($depositId, $userId);

                            $this->redis->smove('bitcoind:unconfirmed:txid', 'bitcoind:confirmed:txid', $txid);

                            $this->distributeCoins($account, $amount);

                            echo "\n[deposit] $txid confirmed!";

                            // Check whether this is an auto-sell
                            $userAutoSell = $this->getAutoSellAddress($userId);
                            if ($userAutoSell) {
                                foreach ($userAutoSell as $currency => $_address) {
                                    if ($_address == $address) {
                                        $this->_processAutoSell($userId, $amount, $currency);

                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->caching_model->delete('user:' . $userId . ':deposits:pending');
        }

        return true;
    }
    */
    

    
    /*
    //Coinkite
    public function processTransaction($txid) {
        
        //$this->load->library('easybitcoin');
        //$transaction = $this->easybitcoin->gettransaction($txid);
        
        $this->load->model('deposit_model');
        $this->load->library('coinkitelibrary');
        $transaction = $this->coinkitelibrary->send("/v1/search/txo/".$txid);

        if(!property_exists($transaction, "match")){
            return false;
        }

        $transactionDetails = $transaction->match;
        
        
        $address       = $transactionDetails->coin->address;
        $userId        = $this->getUserIdByAdress($address, 'userid');
        $account       = "user:".$userId;
        
        $amount        = rCurrency('btc', $transactionDetails->amount, '');
        $confirmations = $transactionDetails->confirmations;
        
        if (preg_match('/^user:\d+$/', $account)) {
            $userId = _numeric($account);

            if (!$this->redis->sismember('bitcoind:unconfirmed:txid', $txid)) {
                $data = array(
                    'client'   => $userId,
                    'method'   => 'btc',
                    'currency' => 'btc',
                    'amount'   => $amount
                );
                
                $details = array(
                    'address' => $address,
                    'confirmations' => $confirmations
                );

                $this->deposit_model->add($data, $details);

                $this->redis->sadd('bitcoind:unconfirmed:txid', $txid);
                $this->createTransactionsLookup();

                $this->user_model->setProperty($userId, 'bitcoin_address', null);

                // Update to user's screen
                $notification = array(
                    'txid'          => $txid,
                    'amount'        => $amount,
                    'currency'      => makeSafeCurrency('BTC'),
                    'address'       => $address,
                    'confirmations' => $confirmations,
                    'datetime'      => date('m/d/Y H:i', $this->now / 1000)
                );
                $this->notification_model->direct('status', $address, $notification);
                $this->notification_model->flush();

                echo "[deposit] $txid unconfirmed - $confirmations confirmations";
            } else {
                // use caching to speed things up a little
                if (isset($this->cachedDeposits[$userId])) {
                    $deposits = $this->cachedDeposits[$userId];
                }
                else {
                    $deposits = $this->deposit_model->findBitcoinDeposits($userId, $address);
                    $this->cachedDeposits[$userId] = $deposits;
                }

                foreach ($deposits as $deposit) {
                    if ($deposit->status != 'complete' && $deposit->amount == $amount) {
                        $depositId = $deposit->id;

                        if ((int)$deposit->details->confirmations != (int)$confirmations) {
                            $data['confirmations'] = $confirmations;
                            $this->deposit_model->updateDetails($depositId, $data);

                            echo "[deposit] $txid updated - $confirmations confirmations";

                            $notification = array(
                                'txid'          => $txid,
                                'confirmations' => $confirmations
                            );
                            $this->notification_model->direct('status', $txid, $notification);
                            $this->notification_model->flush();
                        }

                        
                        if ((int)$confirmations > self::BITCOIN_DEPOSIT) {
                            $this->deposit_model->receive($depositId, $userId);

                            $this->redis->smove('bitcoind:unconfirmed:txid', 'bitcoind:confirmed:txid', $txid);

                            //$this->distributeCoins($account, $amount);

                            echo "\n[deposit] $txid confirmed!";

                            // Check whether this is an auto-sell
                            $userAutoSell = $this->getAutoSellAddress($userId);
                            if ($userAutoSell) {
                                foreach ($userAutoSell as $currency => $_address) {
                                    if ($_address == $address) {
                                        $this->_processAutoSell($userId, $amount, $currency);

                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->caching_model->delete('user:' . $userId . ':deposits:pending');
        }
        
        return true;
        
    }
    */
    public function createTransactionsLookup() {
        return $this->redis->sunionstore('bitcoind:transactions', array('bitcoind:confirmed:txid', 'bitcoind:unconfirmed:txid', 'bitcoind:dodgy:txid'));
    }

    private function distributeCoins($account, $amount) {
        //$this->load->library('easybitcoin');
        //$this->redis->set('hotwallet:maximum', '10');
        $withdrawalBalance = $this->admin_model->getWallet('limit'); //$this->redis->get('hotwallet:maximum');
        $address1 = $this->admin_model->getWallet('address1');
        $address2 = $this->admin_model->getWallet('address2');
        
        // Need to check how much there is in the account used for withdrawals
        //$balance = $this->easybitcoin->getbalance('withdrawals');
        
        $this->load->library('BitGo');
        $accountWallet = $this->bitgo->send("/get_wallet");
        $balance = rCurrency('btc',$accountWallet->confirmedBalance / 100000000, '');
        $account = $accountWallet->id;
        
        $newAmount = bcadd($balance, $amount, 8);
        
        //$this->load->config('creds_bitcoin', TRUE);
        //$cold_wallets = $this->config->item('creds_bitcoin');
        // If there is not enough coins in the account, transfer some of the amount
        if (bccomp($balance, $withdrawalBalance, 8) < 0) {
            $newAmount = bcadd($balance, $amount, 8);
            if (bccomp($newAmount, $withdrawalBalance, 8) > 0) {
                echo "Send money to address:".$address1;
                $newAmount = bcsub($newAmount, $withdrawalBalance, 8);
                //$newAmount = bcsub($amount, $newAmount, 8);
                
                $data = array(
                    "amount" => floatval($newAmount),
                    "address" => $address1
                );

                var_dump("attempt 1");
                var_dump($data);

                $this->bitgo->send('/send_coins', "POST", $data);

                //$this->easybitcoin->move($account, 'withdrawals', floatval($newAmount));

                $remaining = bcsub($amount, $newAmount, 8);
                
                echo "Send money 2 to address2:".$address2;
                $data = array(
                    "amount" => floatval($remaining),
                    "address" => $address2
                );

                var_dump("attempt2");
                var_dump($data);
                $this->bitgo->send('/send_coins', "POST", $data);
                
                //$this->easybitcoin->move($account, 'takeoff', floatval($remaining));
            }
        } else {

            echo "Send money 3 to address1:".$address1;

            $amount = bcsub($balance, $withdrawalBalance, 8);
            $data = array(
                "amount" => floatval($amount),
                "address" => $address1
            );
            var_dump("attempt3");
            var_dump($data);
            $this->bitgo->send('/send_coins', "POST", $data);
            //$this->easybitcoin->move($account, 'takeoff', floatval($amount));
        }
    }
    
    public function bitcoinAutoWithdrawals() {
        //$this->load->library('easybitcoin');
        $this->load->model('bitcoin_model');
        $this->load->model('withdrawal_model');
        $this->load->model('logging_model');
        $this->load->model('user_balance_model');
        $this->load->model('user_model');

        $now         = milliseconds();
        $withdrawals = $this->withdrawal_model->getPending('btc');

        if (count($withdrawals) > 0) {
            // We have some withdrawals to process
            foreach ($withdrawals as $withdrawal) {
                // let's wait at least 3 minutes before processing it
                if ($withdrawal->_created / 1000 < ($now / 1000 - 3 * 60)) {
                    // Check if the user is banned from auto withdrawals
                    $userAllowed = $this->user_model->getProperty($withdrawal->client, 'auto_withdrawals');

                    if (!$userAllowed || $userAllowed == 'on') {
                        // Check if there is enough money in the 'withdrawals' account to pay that withdrawal
                        $this->load->library('BitGo');
                        $account = $this->bitgo->send("/get_wallet");
                        $balance = $account->confirmedBalance / 100000000;

                        //echo $balance;
                        if (bccomp($balance, $withdrawal->amount, 8) > 0) {
                            $data = array(
                                "amount" => (float)$withdrawal->amount,
                                "address" => $withdrawal->details->address
                            );

                            var_dump("attempt4");
                            var_dump($data);
                            $response = $this->bitgo->send("/send_coins", "POST", $data);
                            var_dump($response);
                            
                            $transactionId = $response->hash;
                            //$transactionId = $this->easybitcoin->sendfrom('withdrawals', $withdrawal->details->address, (float)$withdrawal->amount);
                            var_dump($transactionId);

                            if ($transactionId) {
                                // We need to save the details of the transaction we have just made
                                $data = array(
                                    'transaction_id' => $transactionId
                                );

                                $this->withdrawal_model->updateDetails($withdrawal->id, $data);

                                // Mark the withdrawal as done
                                $this->withdrawal_model->sent($withdrawal->id, $withdrawal->client);

                                // Save a trail
                                $this->logging_model->log($withdrawal->_id, 'auto-withdrawal', $withdrawal->client);
                            }
                        } else {
                            $this->logging_model->log("Not enough balance to process withdrawal: ".$withdrawal->_id, 'auto-withdrawal', $withdrawal->client);
                        }
                    }
                }
            }
        }
    }
    
    
    public function reloadAddress() {
        $this->load->model('user_model');
        $entries = $this->redis->sort('user:ids', array('BY' => '*->_created', 'sort' => 'DESC'));
        if (!$entries)
            return 0;

        foreach ($entries as $entryId) {
            $_entryId = _numeric($entryId);
            $this->user_model->setProperty($_entryId, 'bitcoin_address', null);

        }
        
        echo "All user address - migrate";
    }
}