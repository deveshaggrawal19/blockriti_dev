<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Merchant_model extends Redis_model {

    public $entries;

    public function __construct() {
        parent::__construct();
    }

    public function getStores($userId) {
        $stores   = array();
        $storeIds = $this->redis->lrange('user:' . $userId . ':store', 0, -1);

        if ($storeIds) {
            foreach ($storeIds as $storeCode) {
                $stores[$storeCode] = $this->get($storeCode);
            }
        }

        return $stores;
    }

    public function getButtons($userId) {
        $buttons   = array();
        $buttonIds = $this->redis->lrange('user:' . $userId . ':button', 0, -1);

        if ($buttonIds) {
            foreach ($buttonIds as $buttonCode) {
                $buttons[$buttonCode] = $this->getButton($buttonCode);
            }
        }

        return $buttons;
    }

    public function get($code) {
        return $this->flatten($this->redis->hgetall('merchant:store:' . $code));
    }

    public function getButton($code) {
        return $this->flatten($this->redis->hgetall('merchant:button:' . $code));
    }

    public function addStore($userId, $data) {
        $code = random_string('alpha', mt_rand(10, 20));

        $data['client']   = $userId;
        $data['_created'] = $this->now;
        $data['_updated'] = $this->now;

        $this->redis->hmset('merchant:store:' . $code, $data);
        $this->redis->rpush('user:' . $userId . ':store', $code);

        return true;
    }

    public function deleteStore($userId, $code) {
        $stores = $this->getStores($userId);

        if (isset($stores[$code])) {
            $this->redis->del('merchant:store:' . $code);
            $this->redis->lrem('user:' . $userId . ':store', 0, $code);

            return true;
        }

        return false;
    }

    public function updateStore($userId, $code, $data) {
        $stores = $this->getStores($userId);

        if (isset($stores[$code])) {
            $data['_updated'] = $this->now;

            $this->redis->hmset('merchant:store:' . $code, $data);

            return true;
        }

        return false;
    }

    public function exists($code) {
        return $this->redis->exists('merchant:store:' . $code);
    }

    // To deal with the merchant interaction
    public function saveTemp($data) {
        $data['_created'] = $this->now;
        $data['_updated'] = $this->now;

        $code = generateRandomString(40, true);
        $key  = 'store:call:' . $code;

        $data['_id'] = $code;

        $this->redis->hmset($key, $data);

        $this->redis->set('store:address:' . $data['address'], $key);
        $this->redis->expire('store:address:' . $data['address'], ONE_DAY); // expire after 1 day so that we don't need to delete it ourselves

        return $code;
    }

    public function getTemp($code) {
        return $this->flatten($this->redis->hgetall('store:call:' . $code));
    }

    public function findStoreCall($address) {
        $key = $this->redis->get('store:address:' . $address);

        if ($key)
            return $this->flatten($this->redis->hgetall($key));

        return false;
    }

    public function paymentReceived($address, $transaction) {
        $key = $this->redis->get('store:address:' . $address);

        if (!$key)
            return false;

        $data = $this->flatten($this->redis->hgetall($key));
        if (!$data)
            return false;

        $store = json_decode($data->store);

        $this->checkExpiry($data);

        $data->received     = bcadd($data->received, $transaction['amount'], getPrecision($data->crypto_currency));
        $data->transactions = bcadd($data->transactions, '1', 0);

        // Save the transaction
        $this->redis->hmset($key . ':transaction:' . $data->transactions, $transaction);

        if (bccomp($data->received, $data->crypto_amount, getPrecision($data->crypto_currency)) >= 0) {
            // We have now received the full amount (or more)
            $data->status = 'complete';
        }

        $data->_updated = $this->now;

        $notification = array(
            '_id'             => $data->_id,
            'crypto_amount'   => $data->crypto_amount,
            'crypto_currency' => $data->crypto_currency,
            'received'        => $data->received,
            'transactions'    => $data->transactions,
            'status'          => $data->status
        );

        if ($data->status == 'complete' && $store->return != null) {
            // Bundle the return url if any
            $notification['return'] = $store->return;
        }

        $this->notification_model->direct('status', $data->_id, $notification);
        $this->notification_model->flush();

        $userId = $store->client;

        // If the payment is completed we need to update the balances and proceed with the callback
        if ($data->status == 'complete') {
            $this->lock($userId);

            $balances = $this->user_balance_model->get($userId);

            $feeAmount = $this->redis->get('merchant:fee');
            $fee       = bcmul($data->payout_amount, $feeAmount, getPrecision($data->payout_currency));

            $this->saveFees($data->payout_currency, $fee);

            $amount     = bcsub($data->payout_amount, $fee, getPrecision($data->payout_currency));
            $data->sent = $data->payout_amount;
            $data->fee  = $fee;

            $balances->{$data->payout_currency} = bcadd($balances->{$data->payout_currency}, $amount, getPrecision($data->payout_currency));

            // Add to the user's merchant thing
            $this->redis->lpush('user:' . $userId . ':merchant-sales', $key);

            $this->user_balance_model->save($userId, $balances);

            // Assign a reference to the call
            $data->reference = generateRandomString(10, true);

            // Add the reference lookup
            $this->redis->set('store:call:reference:' . $data->reference, $key);

            if ($store->callback) {
                $data->retries = 0;
                $this->redis->sadd('merchant:callbacks:pending', $key);
            }

            $this->unlock($userId);

            // Email the notification if needed
            if (isset($store->email) && $store->email) {
                $userData = $this->flatten($this->redis->hgetall('user:' . $userId));
                $name     = $userData->first_name . ' ' . $userData->last_name;

                $notificationData = array(
                    'name'           => $name,
                    'date'           => date('m/d/Y H:i:s', $this->now / 1000),
                    'store'          => $store->name,
                    'reference'      => $data->reference,
                    'amount'         => $data->amount,
                    'currency'       => $data->currency,
                    'payoutCurrency' => $data->payout_currency,
                    'payoutAmount'   => $amount,
                    'fee'            => $data->fee
                );

                $properties = json_decode($data->properties);

                if (isset($properties->identifier))
                    $notificationData['properties']['Identifier'] = $properties->identifier;

                if (isset($properties->description))
                    $notificationData['properties']['Description'] = $properties->description;

                // Deal with the custom data
                if (isset($properties->custom)) {
                    $fields = explode(",", $properties->custom);

                    foreach ($fields as $field)
                        $notificationData['properties'][$field] = $properties->{'custom_' . $field};
                }

                $this->load->model('email_queue_model');

                $this->email_queue_model->email   = $store->email;
                $this->email_queue_model->message = $this->layout->partialView('emails/merchant-notification', $notificationData);
                $this->email_queue_model->subject = 'Payment Notification - ' . $this->config->item('site_full_name');

                $this->email_queue_model->store();
            }

            // Clear the cache
            $this->caching_model->delete('user:' . $userId . ':merchant-sales:summary');
        }

        $this->redis->hmset($key, (array)$data);

        return true;
    }

    public function checkExpiry(&$data) {
        $store = json_decode($data->store);

        // So has it been more than 15 minutes? If it has we need to recalculate the remaining amount cause it
        // may have changed
        if ($data->_updated < ($this->now - (15 * 60 * 1000)) && ($data->crypto_currency != $store->currency)) {

            $this->load->model('trade_model');

            $book     = $data->crypto_currency . '_' . $store->currency;
            $newRate  = $this->trade_model->getLastTradePrice($book);

            // Apply this only if the rate has changed
            if (bccomp ($data->rate, $newRate, getPrecision($store->currency)) != 0) {
                $rate = $data->rate;

                $remaining    = bcsub($data->crypto_amount, $data->received, getPrecision($data->crypto_currency));
                $oldValue     = bcmul($remaining, $rate, 2);
                $newRemaining = bcdiv($oldValue, $newRate, getPrecision($data->crypto_currency));

                $offset   = bcsub($newRemaining, $remaining, getPrecision($data->crypto_currency));
                $newTotal = bcadd($data->crypto_amount, $offset, getPrecision($data->crypto_currency));

                // Let's record a trail just in case!
                $deltas = array();

                if (isset ($data->deltas))
                    $deltas = json_decode($data->deltas);

                $deltas[] = array(
                    '_time'    => $this->now,
                    'rate'     => $rate,
                    'total'    => $data->crypto_amount,
                    'received' => $data->received
                );

                $data->deltas = json_encode($deltas);

                // Adjust the values
                $data->rate          = $newRate;
                $data->crypto_amount = $newTotal;

                $data->_updated = $this->now;

                $this->redis->hmset('store:call:' . $data->_id, (array)$data);
            }
        }
    }

    public function getCount($userId) {
        $this->entries = $this->caching_model->get('user:' . $userId . ':merchant-sales:summary');
        if (!$this->entries) {
            $this->entries = array();

            $sales = $this->redis->lrange('user:' . $userId . ':merchant-sales', 0, -1);

            foreach ($sales as $saleKey) {
                $data = $this->flatten($this->redis->hgetall($saleKey));

                if ($data) {
                    $store = json_decode($data->store);

                    $this->entries[] = (object)array(
                        'name'      => $store->name,
                        'currency'  => $data->payout_currency,
                        'amount'    => $data->payout_amount,
                        'fee'       => $data->fee,
                        'total'     => bcsub($data->payout_amount, $data->fee, getPrecision($data->payout_currency)),
                        'reference' => $data->reference,
                        'date'      => $data->_updated
                    );
                }
            }

            $this->caching_model->save($this->entries, ONE_DAY);
        }

        return count($this->entries);
    }

    public function getSubset($page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage;

        $result = array();
        for ($i = $start; $i < $end; $i++) {
            if (!isset($this->entries[$i])) break;

            $result[] = $this->entries[$i];
        }

        return $result;
    }

    public function saveFees($currency, $amount) {
        $month = date('m');

        $currentTotal = $this->flatten($this->redis->hgetall('merchant:fees:total'));
        $monthTotal   = $this->flatten($this->redis->hgetall('merchant:fees:month:' . $month));

        $total = isset($currentTotal) && isset($currentTotal->{$currency}) ? $currentTotal->{$currency} : '0';
        $total = bcadd($total, $amount, getPrecision($currency));

        $this->redis->hset('merchant:fees:total', $currency, $total);

        // Monthly total
        $total = isset($monthTotal) && isset($monthTotal->{$currency}) ? $monthTotal->{$currency} : '0';
        $total = bcadd($total, $amount, getPrecision($currency));

        $this->redis->hset('merchant:fees:month:' . $month, $currency, $total);
    }

    public function cancel($code) {
        $data = $this->getTemp($code);

        $data->status   = 'canceled';
        $data->_updated = $this->now;

        $this->redis->hmset('store:call:' . $data->_id, (array)$data);
    }
}