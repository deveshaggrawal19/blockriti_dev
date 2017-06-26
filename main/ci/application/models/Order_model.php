<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Order_model extends Redis_model {

    public function getOrders($book, $limit = 100, $grouped = false) {
        $entries = $this->redis->lrange($book, 0, $limit - 1);

        $orders = array();

        if ($grouped) {
            $pricepoints = array();
            for ($i = 0; $i < count($entries); $i++) {
                $orderId = _numeric($entries[$i]);

                $order = $this->get($orderId);
                if ($order) {
                    if (!isset($pricepoints[$order->rate]))
                        $pricepoints[$order->rate] = '0';

                    $pricepoints[$order->rate] = bcadd($pricepoints[$order->rate], $order->amount, 8);
                }
            }

            foreach ($pricepoints as $p=>$a)
                $orders[] = array($p, $a);
        }
        else {
            for ($i = 0; $i < count($entries); $i++) {
                $orderId = _numeric($entries[$i]);

                $order = $this->get($orderId);
                if ($order)
                    $orders[] = $order;
            }
        }

        return $orders;
    }
    
    public function getForUser($userId, $book = null, $count = -1) {
        $orderIds = $this->redis->lrange('user:' . $userId . ':orders', 0, $count);

        $orders = array();
        $idx    = 0;
        foreach ($orderIds as $orderId) {
            $orderId = _numeric($orderId);
            $order   = $this->get($orderId);

            if ($order && ($book == null || $book == $order->book)) {
                $orders[] = $order;
                $idx ++;

                if ($count > -1 && $idx > $count)
                    break;
            }
        }

        return $orders;
    }
    
    public function getSubsetForUser($orders, $page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage;
        
        $result = array();
        
         for ($i = $start; $i < $end; $i++) {
            if (!isset($orders[$i])){
                break;
            } 
            
            $result[] = $orders[$i];
         }
        return $result;
    }
    
    public function getCountForUser($userId, $book = null, $count = -1) {
        $orders = $this->getForUser($userId, $book, $count);
        return count($orders);
    }

    public function create($userId, $data, $type) {
        $value = bcmul($data->rate, $data->amount, getPrecision($data->minor));

        $order = new stdClass();
        $order->_id            = $this->newId('order');
        $order->_created       = $this->now;
        $order->_updated       = $this->now;
        $order->client         = $userId;
        $order->type           = $type;
        $order->book           = $data->major . '_' . $data->minor;
        $order->method         = isset($data->method) ? $data->method : 'www';
        $order->ip             = getIp();
        $order->major_currency = $data->major;
        $order->minor_currency = $data->minor;
        $order->amount         = rCurrency($data->major, $data->amount, '');
        $order->rate           = rCurrency($data->minor, $data->rate, '');
        $order->value          = rCurrency($data->minor, $value, '');
        $order->uid            = generateRandomString(64, true);

        return $order;
    }

    public function add($order) {
        $key = 'orders:' . $order->type . ':' . $order->book;

        // save object
        $this->redis->hmset($order->_id, (array)$order);

        // Update the last change nonce for the book
        $this->redis->incr('orders:nonce:'.$order->book);

        // update last order created timestamp
        $this->redis->set('orders:lastcreatedtime', $order->_created);

        // Save the lookup object
        $this->redis->set('order:uid:' . $order->uid, _numeric($order->_id));

        // add to user orders
        $this->redis->lpush('user:' . $order->client . ':orders', $order->_id);

        $orders = array($order->_id => array(
            'rate' => $order->rate,
            'date' => $order->_created
        ));

        $orderIds = $this->redis->lrange($key, 0, -1);

        foreach ($orderIds as $_orderId) {
            $order = $this->get(_numeric($_orderId));

            if ($order) {
                $orders[$_orderId] = array(
                    'rate' => $order->rate,
                    'date' => $order->_created
                );
            }
        }

        // Sorting by rates but making sure the newer orders appear after the existing ones if the rate
        // are the same - valid for buys and sells
        if ($order->type == 'sell') { // sort ascending for SELLs
            uasort($orders, function($a, $b) {
                if ($a['rate'] == $b['rate']) {
                    return $a['date'] > $b['date'] ? 1 : 0;
                }

                return $a['rate'] > $b['rate'] ? 1 : -1;
            });
        }
        else { // sort descending for BUYs
            uasort($orders, function($a, $b) {
                if ($a['rate'] == $b['rate']) {
                    return $a['date'] > $b['date'] ? 1 : 0;
                }

                return $a['rate'] < $b['rate'] ? 1 : -1;
            });
        }

        $this->redis->multi();
        $this->redis->del($key);
        $this->redis->rpush($key, array_keys($orders));
        $this->redis->exec();
    }

    public function get($id) {
        $data = $this->flatten($this->redis->hgetall('order:' . $id));

        if ($data)
            $data->id = $id;

        return $data;
    }

    public function cancel($id) {
        $order = $this->get($id);

        if ($order->type == 'sell') {
            $currency = strtolower($order->major_currency);
            $amount   = $order->amount;
        }
        else {
            $currency = strtolower($order->minor_currency);
            $amount   = $order->value;
        }

        $balances = $this->user_balance_model->get($order->client);
        $newBalance = bcsub($balances->{$currency . '_locked'}, $amount, getPrecision($currency));
        $this->user_balance_model->save($order->client, array($currency . '_locked' => $newBalance));

        $this->delete($order, 'cancelled');
    }

    public function delete($order, $status) {
        $book = $order->book;

        $data = array(
            '_updated' => milliseconds(),
            'status'   => $status
        );

        $this->redis->hmset($order->_id, $data);

        $this->redis->del('order:uid:' . $order->uid);

        $this->redis->lrem('orders:' . $order->type . ':' . $book, 0, $order->_id);
        $this->redis->lrem('user:' . $order->client . ':orders', 0, $order->_id);

        // Update the last change nonce for the book
        $this->redis->incr('orders:nonce:'.$book);
    }

    public function findByUid($uid) {
        return $this->redis->get('order:uid:' . $uid);
    }

    public function head($list) {
        $orderId = $this->redis->lindex($list, 0);
        if (!$orderId) return null;

        $orderId = _numeric($orderId);
        return $this->get($orderId);
    }

    public function updateOrder($order) {
        return $this->redis->hmset($order->_id, (array)$order);
    }

    public function rebuildOrderBooks() {
        $userOrders = $this->redis->keys('user:*:orders');

        $orders = array();

        foreach ($userOrders as $userOrder) {
            $orderIds = $this->redis->lrange($userOrder, 0, -1);

            foreach ($orderIds as $orderId) {
                $orderId = _numeric($orderId);
                $order = $this->order_model->get($orderId);

                if (!isset($order->status)) {
                    $key = 'orders:' . $order->type . ':' . $order->book;

                    $orders[$key][$order->id] = array(
                        'rate' => $order->rate,
                        'date' => $order->_created
                    );

                    $this->redis->set('order:uid:' . $order->uid, $orderId);
                }
                else echo '<br/><strong>' . $orderId . '</strong><br/>';
            }
        }

        foreach ($orders as $key=>&$_orders) {
            if (strpos($key, ':buy:') !== false) {
                uasort($_orders, function($a, $b) {
                    if ($a['rate'] == $b['rate']) {
                        return $a['date'] > $b['date'] ? 1 : 0;
                    }

                    return $a['rate'] < $b['rate'] ? 1 : -1;
                });
            }
            else {
                uasort($_orders, function($a, $b) {
                    if ($a['rate'] == $b['rate']) {
                        return $a['date'] > $b['date'] ? 1 : 0;
                    }

                    return $a['rate'] > $b['rate'] ? 1 : -1;
                });
            }
        }

        foreach ($orders as $key=>&$_orders) {
            $this->redis->del($key);
            //echo "deleting $key<br/>";
            foreach ($_orders as $orderId=>$__orders) {
                $orderId = 'order:' . _numeric($orderId);
                $this->redis->rpush($key, $orderId);
                //echo "pushing $orderId";
            }
        }
    }

    public function clearOrdersForUser($userId) {
        $orderIds = $this->redis->lrange('user:' . $userId . ':orders', 0, -1);

        foreach ($orderIds as $orderId) {
            $orderId = _numeric($orderId);

            $this->cancel($orderId);
        }
    }
}