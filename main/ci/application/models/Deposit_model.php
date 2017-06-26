<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Deposit_model extends Redis_model {

    public $_id;
    public $_created = 0;
    public $_updated = 0;
    public $client;
    public $amount;
    public $currency;
    public $method;
    public $status;

    public $entries;

    public function __construct() {
        parent::__construct();
        $this->_obj_Memcache  = new Memcache();
    }

    public function add($data, $details = array()) {
        $this->_id      = $this->newId('deposit');
        $this->_created = $this->now;
        $this->_updated = $this->now;
        $this->client   = $data['client'];
        $this->amount   = $data['amount'];
        $this->currency = $data['currency'];
        $this->method   = $data['method'];
        $this->status   = 'pending';

        $d = get_object_vars($this);
        unset($d['now'], $d['_error'], $d['entries'],$d['_obj_Memcache']);

        $this->redis->hmset($this->_id, $d);
        $this->redis->sadd('user:' . $d['client'] . ':deposits', $this->_id);
        $this->redis->sadd('deposits:pending', $this->_id);
        $this->redis->rpush('deposits:pending:' . $this->method, $this->_id);

        if (count($details))
            $this->redis->hmset($this->_id . ':details', $details);

        $userId = $this->client;
        $currency = strtolower($this->currency);

        $this->lock($userId);

        $balances = $this->user_balance_model->get($userId);

        $newBalance = bcadd($balances->{$currency . '_pending_deposit'}, $this->amount, getPrecision($currency));
        $this->user_balance_model->save($userId, array(
            $currency . '_pending_deposit' => $newBalance
        ));

        $this->unlock($userId);

        // Remove cache entries
        $this->caching_model->delete('deposits:pending');
        $this->caching_model->delete('user:' . $userId . ':deposits:*');

        if (in_array($data['method'], array('pm', 'ep', 'pz')) !== false)
            $this->redis->expire($this->_id, ONE_HOUR);

        return $this;
    }

    public function getCountForUser($userId, $status = 'complete') {
        $this->entries = $this->caching_model->get('user:' . $userId . ':deposits:' . $status);
        if (!$this->entries) {
            if ($status == 'all')
                $entries = $this->redis->smembers('user:' . $userId . ':deposits');
            else $entries = $this->redis->sinter('deposits:' . $status, 'user:' . $userId . ':deposits');

            $this->entries = array();
            foreach ($entries as $entryId) {
                $data = $this->getFull(_numeric($entryId));
                if ($data)
                    $this->entries[] = $data;
            }

            // No choice but to sort in PHP
            usort($this->entries, array('Deposit_model', 'sortByUpdated'));

            $this->caching_model->save($this->entries, ONE_DAY);
        }

        return count($this->entries);
    }

    public function getSubsetForUser($page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage;

        $result = array();
        for ($i = $start; $i < $end; $i++) {
            if (!isset($this->entries[$i])) break;

            $result[] = $this->entries[$i];
        }

        return $result;
    }

    private function sortByUpdated($a, $b) {
        return $a->_updated < $b->_updated ? 1 : -1;
    }

    public function addComplete($data, $details = null) {
        $this->_id      = $this->newId('deposit');
        $this->_created = $this->now;
        $this->_updated = $this->now;
        $this->client   = $data['client'];
        $this->amount   = $data['amount'];
        $this->currency = $data['currency'];
        $this->method   = $data['method'];
        $this->status   = 'complete';

        $d = get_object_vars($this);
        unset($d['now'], $d['_error'], $d['entries'],$d['_obj_Memcache']);

        $userId = _numeric($data['client']);

        $this->lock($userId);

        $this->redis->hmset($this->_id, $d);
        $this->redis->sadd('user:' . $d['client'] . ':deposits', $this->_id);
        $this->redis->sadd('deposits:complete', $this->_id);

        if ($details)
            $this->redis->hmset($this->_id . ':details', $details);

        $userId = $this->client;
        $currency = strtolower($this->currency);
        $balances = $this->user_balance_model->get($userId);

        $newBalance = bcadd($balances->{$currency}, $this->amount, getPrecision($currency));
        $this->user_balance_model->save($userId, array($currency => $newBalance));

        // Remove cache entries
        $this->caching_model->delete('deposits:complete');
        $this->caching_model->delete('user:' . $userId . ':deposits:*');

        $this->unlock($userId);
        
        $this->saveCashierDeposits($currency,$this->amount);

        return $this->_id;
    }

    public function find($status, $filter) {
        $depositIds = $this->redis->sort('deposits:' . $status, array('by' => '*->_created', 'sort' => 'DESC'));

        // Fetch the data
        $deposits = array();
        foreach ($depositIds as $depositId) {
            $depositId = _numeric($depositId);
            $deposit = $this->getFull($depositId);
            if (isset($deposit->details)) {
                if (
                    (isset($deposit->details->depositref) && $deposit->details->depositref == $filter)
                    || (isset($deposit->details->speiref) && $deposit->details->speiref == $filter)
                    || (isset($deposit->details->short_payment_id) && $deposit->details->short_payment_id == $filter)
                ) {
                    $deposit->user = $this->user_model->getUser($deposit->client);
                    $deposit->id   = _numeric($deposit->_id);
                    $deposits[] = $deposit;
                }
            }
        }

        return $deposits;
    }

    public function findBitcoinDeposits($userId, $btcAddress) {

        $found = array();
        if (empty($this->_obj_Memcache->get('user:' . $userId. ':deposits:btc') ) === true) {
            $deposits = $this->redis->smembers('user:' . $userId . ':deposits');

            foreach ($deposits as $depositId) {
                $deposit = $this->getFull(_numeric($depositId));

                if ($deposit->method == 'btc' && $deposit->details->address == $btcAddress)
                    $found[] = $deposit;
            }
            $this->_obj_Memcache->set('user:' . $userId. ':deposits:btc', $found);
        }
        else
        {
            $found = $this->_obj_Memcache->get('user:' . $userId. ':deposits:btc');
        }
        
        //print_r($found);
        return $found;
    }

    public function findCompropagoDeposit($comp_payment_id) {
        $deposits = $this->redis->smembers('deposits:pending');

        foreach ($deposits as $depositId) {
            $deposit = $this->getFull(_numeric($depositId));
            if ($deposit->method == 'cp' && $deposit->details->payment_id == $comp_payment_id)
                return $deposit;
        }

        return null;
    }

    public function userBannedFromInterac($userId) {
        return $this->redis->sismember('interac:banned', $userId);
    }

    public function isPending($depositId) {
        return $this->redis->sismember('deposits:pending', 'deposit:' . $depositId) == 1;
    }

    public function get($id) {
        $object = $this->flatten($this->redis->hgetall('deposit:' . $id));

        if ($object)
            $object->id = $id;

        return $object;
    }

    public function getFull($id) {
        $object = $this->get($id);

        if ($object)
            $object->details = $this->flatten($this->redis->hgetall('deposit:' . $id . ':details'));

        return $object;
    }

    public function update($id, $data) {
        $data['_updated'] = $this->now;

        $this->redis->hmset('deposit:' . $id, $data);

        return true;
    }

    public function updateDetails($id, $data) {
        $this->redis->hmset('deposit:' . $id . ':details', $data);

        return true;
    }

    public function delete($depositId, $userId) {
        $deposit = $this->getFull($depositId);
        
        if ($deposit->status != 'pending' && $deposit->status != 'verify')
            return false;

        // Bitcoin special case - Need to put the address back in the pool
        // Only if bitcoind AND blockchain are disabled
        if ($deposit->method == 'btc') {
            $status = $this->bitcoin_model->getStatus('bitcoind');
            if ($status == 'disabled') {
                $status = $this->bitcoin_model->getStatus('blockchain');
                if ($status == 'disabled') {
                    $address = $deposit->details->address;
                    $this->redis->sadd('btc_address', $address);
                }
            }
        }

        $this->lock($userId);

        $this->redis->srem('deposits:pending', $deposit->_id);
        $this->redis->lrem('deposits:pending:' . $deposit->method, 0, $deposit->_id);
        $this->redis->srem('user:' . $userId . ':deposits', $deposit->_id);

        $this->redis->del($deposit->_id);
        $this->redis->del($deposit->_id . ':details');

        $balances = $this->user_balance_model->get($userId);
        $currency = strtolower($deposit->currency);

        $newBalance = bcsub($balances->{$currency . '_pending_deposit'}, $deposit->amount, getPrecision($currency));
        if (bccomp($newBalance, "0", getPrecision($currency)) < 0)
            $newBalance = bcadd($balances->{$currency . '_pending_deposit'}, $deposit->amount, getPrecision($currency));

        $this->user_balance_model->save($userId, array($currency . '_pending_deposit' => $newBalance));

        // Remove cache entries
        $this->caching_model->delete('deposits:pending');
        $this->caching_model->delete('user:' . $userId . ':deposits:*');

        $this->unlock($userId);

        return true;
    }
    
    public function cancel($depositId, $userId) {
        $deposit = $this->getFull($depositId);
        

        // Bitcoin special case - Need to put the address back in the pool
        // Only if bitcoind AND blockchain are disabled
        if ($deposit->method == 'btc') {
            $status = $this->bitcoin_model->getStatus('bitcoind');
            if ($status == 'disabled') {
                $status = $this->bitcoin_model->getStatus('blockchain');
                if ($status == 'disabled') {
                    $address = $deposit->details->address;
                    $this->redis->sadd('btc_address', $address);
                }
            }
        }
        
        $data['status'] = 'canceled';
        $this->update($deposit->id, $data);

        $this->lock($userId);

        $balances = $this->user_balance_model->get($userId);
        $currency = strtolower($deposit->currency);

        $newBalance = bcsub($balances->{$currency . '_pending_deposit'}, $deposit->amount, getPrecision($currency));
        if (bccomp($newBalance, "0", getPrecision($currency)) < 0)
            $newBalance = bcadd($balances->{$currency . '_pending_deposit'}, $deposit->amount, getPrecision($currency));

        $this->user_balance_model->save($userId, array($currency . '_pending_deposit' => $newBalance));
        
        if ($deposit->status == 'pending') {
            $this->redis->smove('deposits:pending', 'deposits:canceled', $deposit->_id);
            $this->redis->lrem('deposits:pending:' . $deposit->method, 0, $deposit->_id);
    
            // Remove cache entries
            $this->caching_model->delete('deposits:canceled');
            $this->caching_model->delete('user:' . $userId . ':deposits:*');
        } else {
            $this->redis->smove('deposits:verify', 'deposits:canceled', $deposit->_id);

            $this->caching_model->delete('deposits:verify');
            $this->caching_model->delete('deposits:canceled');
        }

        $this->unlock($userId);

        return true;
    }

    public function receive($depositId, $reference = null) {
        $deposit = $this->get($depositId);
        $userId  = $deposit->client;

        if ($deposit->status != 'pending' && $deposit->status != 'verify')
            return false;

        $currency = strtolower($deposit->currency);
        $amount   = $deposit->amount;

        $this->lock($userId);

        $balances = $this->user_balance_model->get($userId);

        $newPendingBalance = bcsub($balances->{$currency . '_pending_deposit'}, $amount, getPrecision($currency));
        if (bccomp($newPendingBalance, "0", getPrecision($currency)) < 0) {
            $amount = bcadd($amount, $newPendingBalance, getPrecision($currency));
            $newPendingBalance = bcadd("0", "0", getPrecision($currency));
        }

        $this->saveCashierDeposits($currency, $amount);
        
        // Deal with the fees
        $adjust = null;
        $fee    = 0;
        switch ($deposit->method) {
            case 'io': // Interac Online
                if(bccomp(bcmul($amount, '0.02', 2), 5, 2) < 0){
                    $fee = 5;
                } else {
                    $fee = bcmul($amount, '0.02', 2);
                }
                
                $gross  = $amount;
                $amount = bcsub($amount, $fee, 2);

                $adjust = array(
                    'fee'    => $fee,
                    'gross'  => $gross,
                    'amount' => $amount
                );

                break;

            case 'pz':
                $fee    = bcadd(bcmul($amount, '0.025', 2), '0.25', 2);
                $gross  = $amount;
                $amount = bcsub($amount, $fee, 2);

                $adjust = array(
                    'fee'    => $fee,
                    'gross'  => $gross,
                    'amount' => $amount
                );

                break;

            case 'ep':
                break;
        }

        if ($adjust) {
            $this->update($depositId, $adjust);

            //$this->saveCashierFees($currency, $fee);
        }

        $newBalance = bcadd($balances->{$currency}, $amount, getPrecision($currency));

        $this->user_balance_model->save($userId, array(
            $currency . '_pending_deposit' => $newPendingBalance,
            $currency                      => $newBalance
        ));

        $this->update($depositId, array('status' => 'complete'));

        if ($deposit->status == 'pending') {
            $this->redis->smove('deposits:pending', 'deposits:complete', $deposit->_id);
            $this->redis->lrem('deposits:pending:' . $deposit->method, 0, $deposit->_id);

            $this->caching_model->delete('deposits:pending');
        }
        else {
            $this->redis->smove('deposits:verify', 'deposits:complete', $deposit->_id);

            $this->caching_model->delete('deposits:verify');
        }

        // Remove cache entries
        $this->caching_model->delete('deposits:pending');
        $this->caching_model->delete('deposits:complete');
        $this->caching_model->delete('user:' . $userId . ':deposits:*');

        $this->unlock($userId);

        if (in_array($deposit->method, array('pm', 'ep', 'pz')) !== false) {
            $this->redis->persist($deposit->_id);

            if ($reference) {
                // Adding the reference as a lookup
                $this->redis->sadd('references:' . $deposit->method, $reference);
                $this->redis->set('reference:' . $deposit->method . ':' . $reference, $depositId);
            }
        }

        return true;
    }

    public function toVerify($depositId, $userId) {
        $deposit = $this->get($depositId);

        if ($deposit->status != 'pending')
            return false;

        $this->lock($userId);

        $this->update($depositId, array('status' => 'verify'));

        $this->redis->smove('deposits:pending', 'deposits:verify', $deposit->_id);
        $this->redis->lrem('deposits:pending:' . $deposit->method, 0, $deposit->_id);

        // Remove cache entries
        $this->caching_model->delete('deposits:verify');
        $this->caching_model->delete('deposits:pending');
        $this->caching_model->delete('user:' . $userId . ':deposits:*');

        $this->unlock($userId);

        return true;
    }

    public function fail($depositId, $userId) {
        $deposit = $this->get($depositId);

        if ($deposit->status != 'pending')
            return false;

        $this->lock($userId);

        $currency = strtolower($deposit->currency);
        $balances = $this->user_balance_model->get($userId);

        $newPendingBalance = bcsub($balances->{$currency . '_pending_deposit'}, $deposit->amount, getPrecision($currency));
        if (bccomp($newPendingBalance, "0", getPrecision($currency)) < 0)
            $newPendingBalance = bcadd($balances->{$currency . '_pending_deposit'}, $deposit->amount, getPrecision($currency));

        $this->user_balance_model->save($userId, array(
            $currency . '_pending_deposit' => $newPendingBalance
        ));

        $this->redis->srem($userId . ':deposits', $deposit->_id);
        $this->update($depositId, array('status' => 'failed'));

        $this->redis->smove('deposits:pending', 'deposits:failed', $deposit->_id);
        $this->redis->lrem('deposits:pending:' . $deposit->method, 0, $deposit->_id);

        // Remove cache entries
        $this->caching_model->delete('deposits:pending');
        $this->caching_model->delete('deposits:failed');
        $this->caching_model->delete('user:' . $userId . ':deposits:*');

        $this->unlock($userId);

        return true;
    }

    public function getCount($status) {
        $this->entries = $this->caching_model->get('deposits:' . $status);
        if (!$this->entries) {
            $this->entries = array();

            $entries = $this->redis->smembers('deposits:' . $status);

            if ($entries) {
                foreach ($entries as $entryId) {
                    $_entryId = _numeric($entryId);
                    $data     = $this->get($_entryId);

                    if ($data)
                        $this->entries[] = $data;
                }

                // No choice but to sort in PHP
                usort($this->entries, array('Deposit_model', 'sortByUpdated'));

                $this->caching_model->save($this->entries, ONE_DAY);
            }
        }

        return count($this->entries);
    }

    public function getSubset($page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage;

        $result = array();
        for ($i = $start; $i < $end; $i++) {
            if (!isset($this->entries[$i])) break;

            $data = $this->entries[$i];
            $data->user = $this->user_model->getUser($data->client);

            $result[] = $data;
        }

        return $result;
    }

    public function addTemp($data, $details = array()) {
        $code = random_string();

        $key = 'temp:deposit:' . $code;
        $this->redis->hmset($key, $data);

        $this->redis->expire($key, ONE_HOUR);

        if (count($details)) {
            $this->redis->hmset($key . ':details', $details);
            $this->redis->expire($key . ':details', ONE_HOUR);
        }

        return $code;
    }

    public function getTemp($code) {
        return $this->flatten($this->redis->hgetall('temp:deposit:' . $code));
    }

    public function getTempDetails($code) {
        return $this->flatten($this->redis->hgetall('temp:deposit:' . $code . ':details'));
    }

    public function clearTemp($code) {
        $this->redis->del('temp:deposit:' . $code);
        $this->redis->del('temp:deposit:' . $code . ':details');
    }

    public function getPending($method) {
        $result = array();

        $depositIds = $this->redis->lrange('deposits:pending:' . $method, 0, -1);
        foreach ($depositIds as $depositId) {
            $result[] = $this->get(_numeric($depositId));
        }
        return $result;
    }

    public function saveCashierFees($currency, $amount) {
        $month = date('m');
        $currentTotal = $this->flatten($this->redis->hgetall('deposits:fees:total'));
        $monthTotal   = $this->flatten($this->redis->hgetall('deposits:fees:month:' . $month));

        // Running total
        $total = isset($currentTotal) && isset($currentTotal->{$currency}) ? $currentTotal->{$currency} : '0';
        $total = bcadd($total, $amount, getPrecision($currency));

        $this->redis->hset('deposits:fees:total', $currency, $total);

        // Monthly total
        $total = isset($monthTotal) && isset($monthTotal->{$currency}) ? $monthTotal->{$currency} : '0';
        $total = bcadd($total, $amount, getPrecision($currency));

        $this->redis->hset('deposits:fees:month:' . $month, $currency, $total);
    }
    
    public function saveCashierDeposits($currency, $amount) {
        $month = date('m');
        $currentTotal = $this->flatten($this->redis->hgetall('deposits:amount:total'));
        $monthTotal   = $this->flatten($this->redis->hgetall('deposits:amount:month:' . $month));

        // Running total
        $total = isset($currentTotal) && isset($currentTotal->{$currency}) ? $currentTotal->{$currency} : '0';
        $total = bcadd($total, $amount, getPrecision($currency));

        $this->redis->hset('deposits:amount:total', $currency, $total);

        // Monthly total
        $total = isset($monthTotal) && isset($monthTotal->{$currency}) ? $monthTotal->{$currency} : '0';
        $total = bcadd($total, $amount, getPrecision($currency));

        $this->redis->hset('deposits:amount:month:' . $month, $currency, $total);
    }

    public function setLimits($method, $currency, $min, $max) {
        $this->redis->set('deposit:method:' . $method . ':' . $currency . ':minimum', $min);
        $this->redis->set('deposit:method:' . $method . ':' . $currency . ':maximum', $max);
    }

    public function getLimits($method, $currency) {
        return array(
            'min' => $this->redis->get('deposit:method:' . $method . ':' . $currency . ':minimum'),
            'max' => $this->redis->get('deposit:method:' . $method . ':' . $currency . ':maximum')
        );
    }

    public function referenceExists($code, $reference) {
        return $this->redis->sismember('references:' . $code, $reference);
    }

    public function interacDepositsDay($userId) {
        $count = 0;
        $dummy = $this->getCountForUser($userId, 'all', 'io');

        foreach ($this->entries as $entry) {
            if ($entry->_created > $this->now - (24 * 3600 * 1000))
                $count++;
            else break;
        }

        return $count;
    }
    
    
    //From cron.php
    public function pendingDeposits() {
        $this->load->helper('date_helper');
        $methodsToCheck = array(
            'io' => (12 * 60 * 60),
            'pz' => (1 * 60 * 60)
        );

        foreach ($methodsToCheck as $method=>$timeout) {
            $deposits = $this->deposit_model->getPending($method);

            foreach ($deposits as $deposit) {
                if ($deposit->status == 'pending') {
                    if ($deposit->_created / 1000 < now() - $timeout) {
                        $this->deposit_model->delete($deposit->id, $deposit->client);

                        // Special case for Interac Online
                        if ($method == 'io') {
                            // We need to send the user an email
                            $user = $this->user_model->getUser($deposit->client);

                            $data = array('name' => $user->first_name);
                            
                            $this->load->library('Mandrilllibrary');
                            $api = $this->mandrilllibrary->getApi();
                            
                            $name = 'pending_interac';
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
                                'subject' =>  'Pending Interac Online Deposit - ' . $this->config->item('site_full_name'),
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
                            $this->email_queue_model->message = $this->load->view('emails/pending_interac', $data, true);
                            $this->email_queue_model->subject = 'Pending Interac Online Deposit - ' . $this->config->item('site_full_name');

                            $this->email_queue_model->store();
                            */
                            // Then delete all his other pending deposits until 30min or so
                            foreach ($deposits as $_deposit) {
                                if ($deposit->_id != $_deposit->_id && // ignore same deposit
                                    $_deposit->client == $deposit->client // make sure it is same client
                                    && $_deposit->_created / 1000 < (now() - (30 * 60)) // don't include attempts made less than 30 min ago
                                )
                                    $this->deposit_model->delete($_deposit->id, $_deposit->client); // nuked!
                            }

                            break; // bail out until next cron run
                        }
                    }
                }
                else {
                    // The deposit is not pending yet is in the pending list -> alert admin?
                    //systemEmail('Incorrect pending deposit - ' . $deposit->_id);
                }
            }
        }
    }
}