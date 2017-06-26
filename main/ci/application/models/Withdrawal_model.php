<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Withdrawal_model extends Redis_model {

    public $_id;
    public $_created = 0;
    public $_updated = 0;
    public $client;
    public $amount;
    public $currency;
    public $method;
    public $status;

    public function __construct() {
        parent::__construct();
    }

    public function add($data, $details = array()) {
        $this->_id      = $this->newId('withdrawal');
        $this->_created = $this->now;
        $this->_updated = $this->now;
        $this->client   = $data['client'];
        $this->amount   = $data['amount'];
        $this->currency = $data['currency'];
        $this->method   = $data['method'];
        $this->status   = 'pending';

        $userId   = $this->client;
        $currency = strtolower($this->currency);

        $this->lock($userId);

        $balances = $this->user_balance_model->get($userId);
        $res      = false;

        if (bccomp($balances->{$currency}, $this->amount, getPrecision($currency)) >= 0) {
            $d = get_object_vars($this);
            unset($d['now'], $d['_error']);

            $this->redis->hmset($this->_id, $d);
            $this->redis->sadd('user:' . $userId . ':withdrawals', $this->_id);
            $this->redis->sadd('withdrawals:pending', $this->_id);
            $this->redis->rpush('withdrawals:pending:' . $this->method, $this->_id);

            if (count($details))
                $this->redis->hmset($this->_id . ':details', $details);

            $newPendingBalance = bcadd($balances->{$currency . '_pending_withdrawal'}, $this->amount, getPrecision($currency));
            $newBalance        = bcsub($balances->{$currency}, $this->amount, getPrecision($currency));

            $this->user_balance_model->save($userId, array(
                $currency . '_pending_withdrawal' => $newPendingBalance,
                $currency                         => $newBalance
            ));

            $res = true;
        }

        $this->unlock($userId);

        // Remove cache entries
        $this->caching_model->delete('withdrawals:pending');
        $this->caching_model->delete('user:' . $userId . ':withdrawals:*');

        return $res;
    }

    public function getCountForUser($userId, $status = 'complete') {
        $this->entries = $this->caching_model->get('user:' . $userId . ':withdrawals:' . $status);
        if (!$this->entries) {
            if ($status == 'all')
                $entries = $this->redis->smembers('user:' . $userId . ':withdrawals');
            else $entries = $this->redis->sinter('withdrawals:' . $status, 'user:' . $userId . ':withdrawals');

            $this->entries = array();
            foreach ($entries as $entryId) {
                $data = $this->get(_numeric($entryId));
                if ($data)
                    $this->entries[] = $data;
            }

            // No choice but to sort in PHP
            usort($this->entries, array('Withdrawal_model', 'sortByUpdated'));

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
        $data['_id']      = $this->newId('withdrawal');
        $data['_created'] = $this->now;
        $data['_updated'] = $this->now;
        $data['status']   = 'complete';

        $userId   = $data['client'];
        $currency = strtolower($data['currency']);
        $amount   = $data['amount'];
        $fee      = isset($data['fee']) ? $data['fee'] : 0;
        $total    = bcadd($amount, $fee, getPrecision($currency));

        $this->lock($userId);

        $balances = $this->user_balance_model->get($userId);
        $res      = false;

        if (bccomp($balances->{$currency}, $total, getPrecision($currency)) >= 0) {
            $this->redis->hmset($data['_id'], $data);
            $this->redis->sadd('user:' . $data['client'] . ':withdrawals', $data['_id']);
            $this->redis->sadd('withdrawals:complete', $data['_id']);

            if ($details)
                $this->redis->hmset($data['_id'] . ':details', $details);

            $newBalance = bcsub($balances->{$currency}, $total, getPrecision($currency));
            $this->user_balance_model->save($userId, array($currency => $newBalance));

            // Remove cache entries
            $this->caching_model->delete('withdrawals:complete');
            $this->caching_model->delete('user:' . $userId . ':withdrawals:*');

            $res = $data['_id'];
        }

        $this->unlock($userId);

        return $res;
    }

    public function get($id) {
        $object = $this->flatten($this->redis->hgetall('withdrawal:' . $id));

        if ($object)
            $object->id = _numeric($object->_id);

        return $object;
    }

    public function getFull($id) {
        $object = $this->get($id);

        if ($object)
            $object->details = $this->flatten($this->redis->hgetall('withdrawal:' . $id . ':details'));

        return $object;
    }

    public function update($id, $data) {
        $data['_updated'] = $this->now;

        $this->redis->hmset('withdrawal:' . $id, $data);

        return true;
    }

    public function updateDetails($id, $data) {
        $this->redis->hmset('withdrawal:' . $id . ':details', $data);

        return true;
    }

    public function delete($withdrawalId) {
        $withdrawal = $this->get($withdrawalId);

        if ($withdrawal->status != 'pending')
            return false;

        $userId = $withdrawal->client;

        $currency = strtolower($withdrawal->currency);

        $this->lock($userId);

        $this->redis->srem('withdrawals:pending', $withdrawal->_id);
        $this->redis->lrem('withdrawals:pending:' . $withdrawal->method, 0, $withdrawal->_id);
        $this->redis->srem('user:' . $userId . ':withdrawals', $withdrawal->_id);

        $this->redis->del($withdrawal->_id);
        $this->redis->del($withdrawal->_id . ':details');

        $balances = $this->user_balance_model->get($userId);

        $newPendingBalance = bcsub($balances->{$currency . '_pending_withdrawal'}, $withdrawal->amount, getPrecision($currency));
        if (bccomp($newPendingBalance, "0", getPrecision($currency)) < 0) {
            $withdrawal->amount = bcadd($withdrawal->amount, $newPendingBalance, getPrecision($currency));
            $newPendingBalance = bcadd("0", "0", getPrecision($currency));
        }

        $newBalance = bcadd($balances->{$currency}, $withdrawal->amount, getPrecision($currency));

        $this->user_balance_model->save($userId, array(
            $currency . '_pending_withdrawal' => $newPendingBalance,
            $currency                         => $newBalance
        ));

        // Remove cache entries
        $this->caching_model->delete('withdrawals:pending');
        $this->caching_model->delete('user:' . $userId . ':withdrawals:*');

        $this->unlock($userId);

        return true;
    }

    public function sent($withdrawalId, $userId) {
        $withdrawal = $this->get($withdrawalId);

        if ($withdrawal->status != 'pending')
            return false;

        $currency = strtolower($withdrawal->currency);

        $this->lock($userId);

        $balances = $this->user_balance_model->get($userId);

        $newPendingBalance = bcsub($balances->{$currency . '_pending_withdrawal'}, $withdrawal->amount, getPrecision($currency));
        if (bccomp($newPendingBalance, "0", getPrecision($currency)) < 0) {
            $withdrawal->amount = bcadd($withdrawal->amount, $newPendingBalance, getPrecision($currency));
            $newPendingBalance = bcadd("0", "0", getPrecision($currency));
        }

        $this->user_balance_model->save($userId, array($currency . '_pending_withdrawal' => $newPendingBalance));

        $this->update($withdrawalId, array('status' => 'complete'));

        $this->redis->smove('withdrawals:pending', 'withdrawals:complete', $withdrawal->_id);
        $this->redis->lrem('withdrawals:pending:' . $withdrawal->method, 0, $withdrawal->_id);

        // Remove cache entries
        $this->caching_model->delete('withdrawals:pending');
        $this->caching_model->delete('withdrawals:complete');
        $this->caching_model->delete('user:' . $userId . ':withdrawals:*');

        $this->unlock($userId);

        return true;
    }

    public function getCount($status) {
        $this->entries = $this->caching_model->get('withdrawals:' . $status);
        if (!$this->entries) {
            $this->entries = array();

            $entries = $this->redis->smembers('withdrawals:' . $status);

            if ($entries) {
                foreach ($entries as $entryId) {
                    $_entryId = _numeric($entryId);
                    $data     = $this->get($_entryId);

                    if ($data)
                        $this->entries[] = $data;
                }

                // No choice but to sort in PHP
                usort($this->entries, array('Withdrawal_model', 'sortByUpdated'));

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

    public function getAll($status) {
        $this->getCount($status);
        return $this->entries;
    }

    public function getPending($method) {
        $result = array();

        $withdrawalIds = $this->redis->lrange('withdrawals:pending:' . $method, 0, -1);
        foreach ($withdrawalIds as $withdrawalId)
            $result[] = $this->getFull(_numeric($withdrawalId));

        return $result;
    }

    public function breakdown() {
        $withdrawalIds = $this->redis->smembers('withdrawals:complete');

        $result = array();
        foreach ($withdrawalIds as $withdrawalId) {
            $withdrawal = $this->get(_numeric($withdrawalId));

            if ($withdrawal) {
                if (!isset($withdrawal->method))
                    echo $withdrawalId;

                if (!isset($result[$withdrawal->method]))
                    $result[$withdrawal->method] = 0;

                $result[$withdrawal->method]++;
            }
        }


    }
}