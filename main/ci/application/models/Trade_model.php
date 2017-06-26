<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Trade_model extends Redis_model {

    public  $entries;
    private $_userId;

    public function __construct() {
        parent::__construct();
        $this->_obj_Memcache  = new Memcache();
    }

    public function get($tradeId, $full = false) {
        $data = $this->flatten($this->redis->hgetall('trade:' . $tradeId));
        if ($data) {
            $data->id = $tradeId;

            if ($full) {
                $data->major = $this->user_model->getUser($data->major_client);
                $data->minor = $this->user_model->getUser($data->minor_client);

                if (isset($data->minor_referral_fee))
                    $data->major_referrer = $this->user_model->getUser($data->major->referrer_id);

                if (isset($data->major_referral_fee))
                    $data->minor_referrer = $this->user_model->getUser($data->minor->referrer_id);

                $data->datetime = date('d/m/Y H:i:s', $data->_created / 1000);
            }
        }

        return $data;
    }

    public function getCountForUser($userId) {
        $this->_userId = $userId;
        $this->entries = $this->caching_model->get('user:' . $userId . ':trades');

        if (!$this->entries) {
            $this->entries = array();

            $entries = $this->redis->lrange('user:' . $userId . ':trades', 0, -1);

            foreach ($entries as $entryId) {
                $_entryId = _numeric($entryId);

                $this->entries[] = $_entryId;
            }

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

            $_entryId = $this->entries[$i];
            $trade = $this->get($_entryId, true);

            if ($this->_userId == $trade->major_client) {
                $trade->fee   = $trade->minor_fee;
                $trade->total = $trade->minor_total;
                $trade->type  = 'sell';
                $trade->with  = $trade->minor;
            }
            else if ($this->_userId == $trade->minor_client) {
                $trade->fee   = $trade->major_fee;
                $trade->total = $trade->major_total;
                $trade->type  = 'buy';
                $trade->with  = $trade->major;
            }

            $result[] = $trade;
        }

        return $result;
    }

    public function getCount($book) {
        return $this->redis->llen('trades:' . $book);
    }

    public function getSubset($book, $page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage - 1;

        $this->entries = $this->caching_model->get('trades:' . $book . ':page:' . $page);
        if (!$this->entries) {
            $this->entries = array();

            $entries = $this->redis->lrange('trades:' . $book, $start, $end);

            foreach ($entries as $entryId) {
                $_entryId = _numeric($entryId);

                $trade = $this->get($_entryId);

                $trade->datetime = date('d/m/Y H:i:s', $trade->_created / 1000);

                $trade->major = $this->user_model->getUser($trade->major_client);
                $trade->minor = $this->user_model->getUser($trade->minor_client);

                $this->entries[] = $trade;
            }

            $this->caching_model->save($this->entries, ONE_HOUR);
        }

        return $this->entries;
    }

    public function getLastTradePrice($book) {
        return $this->redis->hget('stats:trades:recent', $book);
    }

    public function getRollingVolume($book) {
        return $this->redis->hget('stats:trades:volume', $book);
    }

    public function getVWAP($book) {
        return $this->redis->hget('stats:trades:vwap', $book);
    }

    public function getTrades($book, $limit = 50, $full = false) {
        $entries = $this->redis->lrange('trades:' . $book, 0, $limit - 1);

        $trades = array();
        for ($i = 0; $i < count($entries); $i++) {
            $tradeId = _numeric($entries[$i]);

            $trade = $this->get($tradeId);
            if ($trade)
                $trades[] = $this->get($tradeId, $full);
        }

        return $trades;
    }

    // Get last 1000 trades and the return those which fall within the specified timeframe
    public function getTradesTimeframe($book, $timeframeminutes) {

        $end = round($this->now / 1000) - ($timeframeminutes * 60);

        $inwindow = array();
        $trades = $this->getTrades($book, 1000);
        foreach ($trades as $trade) {
            $date    = round($trade->_created / 1000);
            if ($date > $end) {
                $inwindow []= $trade;
            }
        }

        return $inwindow;
    }

    public function getGraph($book, $days = 30) {
        $graphData = $this->caching_model->get('trades:graph:' . $book . ':' . $days);

        if ($graphData === null) {
            $data = $this->redis->lrange('stats:trades:' . $book . ':graph', -$days, -1);

            $graphData = array();
            foreach ($data as $idx => $statId) {
                $date = str_replace('stats:trades:' . $book . ':', '', $statId);
                $date = str_replace(':imported', '', $date); // In case it has been imported

                $stat = $this->flatten($this->redis->hgetall($statId));

                $graphData[$date]['low']    = (float)$stat->low;
                $graphData[$date]['high']   = (float)$stat->high;
                $graphData[$date]['value']  = (float)$stat->average;
                $graphData[$date]['volume'] = (float)$stat->volume;
                $graphData[$date]['open']   = (float)$stat->open;
                $graphData[$date]['close']  = (float)$stat->last;
            }

            $this->caching_model->save($graphData, ONE_HOUR);
        }

        return $graphData;
    }

    public function generateRollingVolume($book, $offsetHours = 24) {
        $end = round($this->now / 1000) - ($offsetHours * 3600);

        $index = 0;
        $total = '0';
        while (true) {
            $tradeId = $this->redis->lindex('trades:' . $book, $index);
            if (!$tradeId)
                break;

            $tradeId = _numeric($tradeId);
            $trade   = $this->get($tradeId);
            $date    = round($trade->_created / 1000);

            if ($date > $end) {
                $total = bcadd($total, $trade->amount, 8);
            }
            else break;

            $index++;
        }

        $this->redis->hset('stats:trades:volume', $book, $total);
    }

    public function getCurrentStats($book) {
        $statDayKey = $this->redis->lrange('stats:trades:' . $book . ':graph', -1, -1);
        if (!$statDayKey)
            return null;

        return $this->flatten($this->redis->hgetall($statDayKey[0]));
    }

    public function getForUser($userId, $book = null, $count = 10) {
        $tradeIds = $this->redis->lrange('user:' . $userId . ':trades', 0, -1);

        $trades = array();
        $idx    = 0;
        foreach ($tradeIds as $tradeId) {
            $tradeId  = _numeric($tradeId);
            $trade    = $this->get($tradeId);
            if ($trade) {
                $tradeBook = $trade->major_currency . '_' . $trade->minor_currency;

                if ($book == null || $book == $tradeBook) {
                    $trades[] = $trade;
                    $idx++;

                    if ($idx > $count)
                        break;
                }
            }
        }

        return $trades;
    }

    public function processTrades($major, $minor) {
        $book = strtolower($major . '_' . $minor);

        $bookSell = 'orders:sell:' . $book;
        $bookBuy  = 'orders:buy:' . $book;

        // Get the orders that are ready to be processed
        $topSell = $this->order_model->head($bookSell);
        $topBuy  = $this->order_model->head($bookBuy);

        // If any is unavailable then bail out
        if (!$topBuy || !$topSell) return;

        $clients       = array();
        $notifications = array();

        $minorPrecision = getPrecision($minor);
        $majorPrecision = getPrecision($major);

        while (bccomp($topBuy->rate, $topSell->rate, $minorPrecision) >= 0) {
            $topBuyId  = _numeric($topBuy->_id);
            $topSellId = _numeric($topSell->_id);

            if ($topBuy->client == $topSell->client) {
                // cancel oldest order because it is from the same dude
                $this->order_model->cancel($topBuy->_created > $topSell->_created ? $topSellId : $topBuyId);
            }
            else {
                // We need to get the lowest amount of MAJOR currency (eg XBT) to fulfill the order
                $amount = bcmin($majorPrecision, $topBuy->amount, $topSell->amount);

                // If the sell order was placed before the buy order we need to use the sell rate
                // otherwise we need to use the buy rate
                $rate  = $topSell->_created < $topBuy->_created ? $topSell->rate : $topBuy->rate;
                $value = bcmul($amount, $rate, $minorPrecision);

                $tradeId = $this->newId('trade');

                if($value == 0){
                    $value = ceil(bcmul($amount, $rate, 7) * 100) / 100;
                }

                $tradeData = array(
                    '_id'            => $tradeId,
                    '_created'       => $this->now,
                    'major_currency' => $major,
                    'major_order_id' => $topSell->id,
                    'major_client'   => $topSell->client,
                    'minor_currency' => $minor,
                    'minor_order_id' => $topBuy->id,
                    'minor_client'   => $topBuy->client,
                    'amount'         => $amount,
                    'rate'           => $rate,
                    'value'          => $value
                );

                // Get user's fees
                $sellerFee = $this->getTradeFee($topSell->client);
                $buyerFee  = $this->getTradeFee($topBuy->client);

                $tradeData = array_merge ($tradeData, array(
                    'seller_fee' => $sellerFee,
                    'buyer_fee'  => $buyerFee
                ));

                // Seller fee is in non crypto currency and has a minimum of 0.01 or 0.000001
                $precision       = $minorPrecision;
                $minorMinimumFee = (string)(pow(10, -$precision));

                $minorFee = bccomp($sellerFee, '0', 3) > 0 ? bcmax($minorPrecision, $minorMinimumFee, bcround(bcmul($value, $sellerFee, $minorPrecision + 1), $minorPrecision)) : '0';
                $majorFee = bccomp($buyerFee, '0', 3) > 0 ? bcround(bcmul($amount, $buyerFee, $majorPrecision), $majorPrecision) : '0';

                $totalValue  = bcsub($value, $minorFee, $minorPrecision);
                $totalAmount = bcsub($amount, $majorFee, $majorPrecision);

                // These are used to send the messages
                $_majorFee = $majorFee;
                $_minorFee = $minorFee;

                $tradeData = array_merge ($tradeData, array(
                    'major_fee' => $majorFee,
                    'minor_fee' => $minorFee,

                    'major_total' => $totalAmount,
                    'minor_total' => $totalValue,

                    // These 2 fields are kept for trail purposes before they are updated below
                    'major_order' => json_encode($topSell),
                    'minor_order' => json_encode($topBuy)
                ));

                $commission = $this->redis->get('trades:commission');

                // Check if the seller has a referrer
                if (bccomp($sellerFee, '0', 3) > 0) {
                    $referrerId = $this->redis->hget('user:' . $topSell->client, 'referrer_id');

                    if ($referrerId) {
                        $referrerCommission = $this->redis->hget('user:' . $referrerId, 'commission');

                        if (!$referrerCommission)
                            $referrerCommission = $commission;

                        // Calculate the referral fee and pay it to the person
                        $fee = bcmul($minorFee, $referrerCommission, $minorPrecision);

                        if (bccomp($fee, '0', $minorPrecision) > 0) {
                            $minorFee = bcsub($minorFee, $fee, $minorPrecision);

                            $tradeData['minor_referral_fee'] = $fee;

                            $this->processReferral($topSell->client, $referrerId, $minor, $fee);
                        }
                    }
                }

                // Check if the buyer has a referrer
                if (bccomp($buyerFee, '0', 3) > 0) {
                    $referrerId = $this->redis->hget('user:' . $topBuy->client, 'referrer_id');

                    if ($referrerId) {
                        $referrerCommission = $this->redis->hget('user:' . $referrerId, 'commission');

                        if (!$referrerCommission)
                            $referrerCommission = $commission;

                        // Calculate the referral fee and pay it to the person
                        $fee = bcmul($majorFee, $referrerCommission, $majorPrecision);

                        if (bccomp($fee, '0', $majorPrecision) > 0) {
                            $majorFee = bcsub($majorFee, $fee, $majorPrecision);

                            $tradeData['major_referral_fee'] = $fee;

                            $this->processReferral($topBuy->client, $referrerId, $major, $fee);
                        }
                    }
                }

                $tradeFees = array(
                    $major => $majorFee,
                    $minor => $minorFee
                );

                $this->saveTradeFees($tradeFees);

                $majorUserBalances = $this->user_balance_model->get($topSell->client);
                $minorUserBalances = $this->user_balance_model->get($topBuy->client);

                // Give the crypto to the buyer
                $minorUserBalances->{$major} = bcadd($minorUserBalances->{$major}, $totalAmount, $majorPrecision);
                // Adjust the money locked balance
                $orderValue = bcmul($amount, $topBuy->rate, $minorPrecision);
                $minorUserBalances->{$minor . '_locked'} = bcsub($minorUserBalances->{$minor . '_locked'}, $orderValue, $minorPrecision);
                $minorUserBalances->{$minor} = bcsub($minorUserBalances->{$minor}, $value, $minorPrecision);

                // Give the money to the seller
                $majorUserBalances->{$minor} = bcadd($majorUserBalances->{$minor}, $totalValue, $minorPrecision);
                // Adjust the crypto locked balance
                $majorUserBalances->{$major . '_locked'} = bcsub($majorUserBalances->{$major . '_locked'}, $amount, $majorPrecision);
                $majorUserBalances->{$major} = bcsub($majorUserBalances->{$major}, $amount, $majorPrecision);

                // Now we deal with the orders
                $topSell->amount = bcsub($topSell->amount, $amount, $majorPrecision);
                $topSell->value  = bcmul($topSell->amount, $topSell->rate, $minorPrecision);

                if (bccomp($topSell->amount, "0", $majorPrecision) <= 0) // Sell order is now complete
                    $this->order_model->delete($topSell, 'completed');
                else $this->order_model->updateOrder($topSell);

                $topBuy->amount = bcsub($topBuy->amount, $amount, $majorPrecision);
                $topBuy->value  = bcmul($topBuy->amount, $topBuy->rate, $minorPrecision);

                if (bccomp($topBuy->amount, "0", $majorPrecision) <= 0) // Buy order is now complete
                    $this->order_model->delete($topBuy, 'completed');
                else $this->order_model->updateOrder($topBuy);

                $this->user_balance_model->save($topSell->client, $majorUserBalances);
                $this->user_balance_model->save($topBuy->client, $minorUserBalances);

                // Save the trade
                $this->redis->hmset($tradeData['_id'], $tradeData); // store trade object
                $this->redis->lpush('trades:' . $book, $tradeData['_id']); // add to trade to public list

                $this->redis->lpush('user:' . $topSell->client . ':trades', $tradeData['_id']); // associate with major user
                $this->redis->lpush('user:' . $topBuy->client . ':trades', $tradeData['_id']); // associate with minor user
                $this->_obj_Memcache->delete('chartdata');
                $this->addTradeToGraph($book, $tradeData);

                $this->redis->hset('stats:trades:recent', $book, $rate);

                // Need to recalculate the users' locked balances
                $clients[] = $topBuy->client;
                $clients[] = $topSell->client;

                $message = array(
                    'book'     => $book,
                    'major'    => $major,
                    'minor'    => $minor,
                    'amount'   => $amount,
                    'value'    => $value,
                    'majorFee' => $_majorFee,
                    'minorFee' => $_minorFee
                );

                $this->notification_model->addTrade('buy', 'user:' . $topBuy->client, $message);
                $this->notification_model->addTrade('sell', 'user:' . $topSell->client, $message);
                $this->notification_model->pushTradeNotification($tradeId);
            }

            $topSell = $this->order_model->head($bookSell);
            $topBuy  = $this->order_model->head($bookBuy);

            if (!$topBuy || !$topSell) break;
        }

        // Clear the cache for trades
        $this->caching_model->delete('trades:' . $book . ':page:*');

        // Finally recalculate the locked balances for the users involved in the trades above
        $this->recalculateLockedBalance($clients);

        $this->_obj_Memcache->delete('user:' . $tradeData['minor_client'] . ':trades');
        $this->_obj_Memcache->delete('orders:' . $tradeData['minor_client'] );
        $this->_obj_Memcache->delete('user:' . $tradeData['major_client'] . ':trades');
        $this->_obj_Memcache->delete('orders:' . $tradeData['major_client'] );
    }
    //TODO INSART ADDED paymentAffComission 
    public function paymentAffComission($userId, $majorFee, $major) {

        $tradeId = $this->newId('trade');
        $majorPrecision = getPrecision($major);

        $commission = $this->redis->get('trades:commission');

        $referrerId = $this->redis->hget('user:' . $userId, 'referrer_id');

        if ($referrerId) {
            $referrerCommission = $this->redis->hget('user:' . $referrerId, 'commission');

            if (!$referrerCommission)
                $referrerCommission = $commission;

            // Calculate the referral fee and pay it to the person
            $fee = bcmul($majorFee, $referrerCommission, $majorPrecision);
            $tradeData = array(
                '_id'            => $tradeId,
                '_created'       => $this->now,
                'major_currency' => $major,
                'major_client'   => $userId,
                'major_referral_fee' => $fee
            );

            if (bccomp($fee, '0', $minorPrecision) > 0) {
                $minorFee = bcsub($minorFee, $fee, $minorPrecision);
                $this->processReferral($userId, $referrerId, $major, $fee);
                $this->redis->hmset($tradeId, $tradeData); // store trade object
                $this->redis->lpush('user:' . $userId . ':trades', $tradeId);
            }
        }
    }

    public function addTradeToGraph($book, $trade) {
        // Get the day's data
        $day  = date('Y-m-d', $trade['_created'] / 1000);
        $data = $this->flatten($this->redis->hgetall('stats:trades:' . $book . ':' . $day));

        if (!$data) {
            $data = array(
                'open'    => null,
                'last'    => '0',
                'count'   => 0,
                'value'   => '0',
                'volume'  => '0',
                'low'     => '10000', // start high on purpose
                'high'    => '0',
                'average' => '0'
            );
        }
        else $data = (array)$data;

        if ($data['open'] === null)
            $data['open'] = $trade['rate'];

        $data['last']    = $trade['rate'];
        $data['count']   = bcadd($data['count'], 1, 0);
        $data['value']   = bcadd($data['value'], $trade['rate'], 2);
        $data['volume']  = bcadd($data['volume'], $trade['amount'], 8);
        $data['low']     = bccomp($data['low'], $trade['rate'], 2) > 0 ? $trade['rate'] : $data['low'];
        $data['high']    = bccomp($data['high'], $trade['rate'], 2) < 0 ? $trade['rate'] : $data['high'];
        $data['average'] = bcdiv($data['value'], $data['count'], 2);

        $key = 'stats:trades:' . $book . ':' . $day;
        $this->redis->hmset($key, $data);

        // Check if the today was added to the graph list
        $last = $this->redis->lrange('stats:trades:' . $book . ':graph', -1, -1);
        if ($last[0] != $key)
            $this->redis->rpush('stats:trades:' . $book . ':graph', $key);

        $this->caching_model->delete('trades:graph:' . $book . ':*');

        $this->updateUserVolume($trade);
    }

    public function updateUserVolume($trade) {
        $amount   = $trade['amount'];
        $currency = $trade['major_currency'];
        $tradeDay = date('Y-m-d', $trade['_created'] / 1000);

        // Both users' volume will change
        foreach (array($trade['minor_client'], $trade['major_client']) as $userId) {
            // Update the day's volume
            $data = $this->redis->get('stats:user:' . $userId . ':volume:' . $tradeDay);
            if (!$data)
                $data = '0';
            $data = bcadd($data, $amount, getPrecision($currency));
            $this->redis->set('stats:user:' . $userId . ':volume:' . $tradeDay, $data);

            // Check if there was an entry created for the day already
            $day = date('Ymd', $trade['_created'] / 1000);
            // Remove the entry for that day if it exists
            $this->redis->zremrangebyscore('stats:user:' . $userId . ':volume', $day, $day);
            // Add it again
            $this->redis->zadd('stats:user:' . $userId . ':volume', $day, $data);

            $this->caching_model->delete('user:' . $userId . ':volume');
            $this->caching_model->delete('orders:user:' . $userId . ':*');
        }
    }

    public function updateTradeStats($book) {
        $end    = round($this->now / 1000) - (24 * 3600);
        $index  = 0;
        $total  = '0';
        $volume = '0';

        while (true) {
            $tradeId = $this->redis->lindex('trades:' . $book, $index);
            if (!$tradeId)
                break;

            $tradeId = _numeric($tradeId);
            $trade   = $this->get($tradeId);
            if (!$trade)
                continue;

            $date = round($trade->_created / 1000);

            if ($date > $end) {
                $total  = bcadd($total, $trade->minor_total, 2);
                $volume = bcadd($volume, $trade->amount, 8);
            }
            else break;

            $index++;
        }

        $this->redis->hset('stats:trades:volume', $book, $volume);
        if (bccomp($volume, '0', 8) > 0) {
            $vwap = bcdiv($total, $volume, 2);
            $this->redis->hset('stats:trades:vwap', $book, $vwap);
        }
    }

    public function twentyFourHourChange($book) {
        $dailyAverages = $this->getGraph($book, 2);
        if (count($dailyAverages) < 2)
            return false;

        $keys = array_keys($dailyAverages);

        return array(
            $dailyAverages[$keys[1]]['value'],
            $dailyAverages[$keys[0]]['value']
        );
    }

    public function recalculateLockedBalance($clients) {
        $currencies  = $this->meta_model->currencies;
        $totalLocked = array();

        $clients = array_unique($clients);

        foreach ($clients as $clientId) {
            // Remove cache data
            $this->caching_model->delete('user:' . $clientId . ':trades');

            foreach ($currencies as $currency)
                $totalLocked[$currency . '_locked'] = '0';

            $orderKeys = $this->redis->lrange('user:' . $clientId . ':orders', 0, -1);
            foreach ($orderKeys as $orderKey) {
                $orderId = _numeric($orderKey);
                $order   = $this->order_model->get($orderId);

                if ($order->type == 'sell') {
                    $currency = $order->major_currency;
                    $amount   = $order->amount;
                }
                else {
                    $currency = $order->minor_currency;
                    $amount   = $order->value;
                }

                $totalLocked[$currency . '_locked'] = bcadd($totalLocked[$currency . '_locked'], $amount, getPrecision($currency));
            }

            $this->user_balance_model->save($clientId, $totalLocked);
        }
    }

    public function saveTradeFees($fees) {
        $month = date('m');
        $currentTotal = $this->flatten($this->redis->hgetall('trades:fees:total'));
        $monthTotal   = $this->flatten($this->redis->hgetall('trades:fees:month:' . $month));

        foreach ($fees as $currency=>$amount) {
            // Running total
            $total = isset($currentTotal) && isset($currentTotal->{$currency}) ? $currentTotal->{$currency} : '0';
            $total = bcadd($total, $amount, getPrecision($currency));

            $this->redis->hset('trades:fees:total', $currency, $total);

            // Monthly total
            $total = isset($monthTotal) && isset($monthTotal->{$currency}) ? $monthTotal->{$currency} : '0';
            $total = bcadd($total, $amount, getPrecision($currency));

            $this->redis->hset('trades:fees:month:' . $month, $currency, $total);
        }
    }

    public function processReferral($userId, $referrerId, $currency, $amount) {
        $balances = $this->user_balance_model->get($referrerId);

        $balances->{$currency} = bcadd($balances->{$currency}, $amount, getPrecision($currency));

        $this->user_balance_model->save($referrerId, $balances);

        $totals = $this->flatten($this->redis->hgetall('referral:user:' . $userId . ':total'));
        if (!isset($totals->{$currency}))
            $totals->{$currency} = '0';

        $newTotal = bcadd($totals->{$currency}, $amount, getPrecision($currency));

        $this->redis->hset('referral:user:' . $userId . ':total', $currency, $newTotal);

        // Wipe the summary cache
        $this->caching_model->delete('user:' . $referrerId . ':referrals:*');
    }

    public function recalculateFees() {
        $tradeIds = $this->redis->keys('trade:*');

        $fees = array();

        foreach ($tradeIds as $tradeId) {
            $tradeId = _numeric($tradeId);
            $trade   = $this->get($tradeId);

            if ($trade && (bccomp($trade->minor_fee, '0', getPrecision($trade->minor_currency)) != 0 || bccomp($trade->major_fee, '0', getPrecision($trade->major_currency)) != 0)) {
                $month = date('m', $trade->_created / 1000);

                if (!isset($fees[$month][$trade->minor_currency]))
                    $fees[$month][$trade->minor_currency] = '0';

                if (!isset($fees[$month][$trade->major_currency]))
                    $fees[$month][$trade->major_currency] = '0';

                $fees[$month][$trade->minor_currency] = bcadd($fees[$month][$trade->minor_currency], $trade->minor_fee, getPrecision($trade->minor_currency));
                $fees[$month][$trade->major_currency] = bcadd($fees[$month][$trade->major_currency], $trade->major_fee, getPrecision($trade->major_currency));
            }
        }

        $totals = array();
        foreach ($fees as $month=>$monthly) {
            foreach ($monthly as $currency=>$value) {
                if (!isset($totals[$currency]))
                    $totals[$currency] = '0';

                $totals[$currency] = bcadd($totals[$currency], $value, getPrecision($currency));
            }

            $this->redis->hmset('trades:fees:month:' . $month, $monthly);
        }

        $this->redis->hmset('trades:fees:total', $totals);
    }

    /* Highly experimental */
    private function _fixFees($trade) {
        $month = date('m', $trade->_created / 1000);

        $_month = $this->flatten($this->redis->hgetall('trades:fees:month:' . $month));

        $_month->{$trade->major_currency} = bcsub($_month->{$trade->major_currency}, $trade->major_fee, getPrecision($trade->major_currency));
        $_month->{$trade->minor_currency} = bcsub($_month->{$trade->minor_currency}, $trade->minor_fee, getPrecision($trade->minor_currency));

        $this->redis->hmset('trades:fees:month:' . $month, (array)$_month);

        $_month = $this->flatten($this->redis->hgetall('trades:fees:total'));

        $_month->{$trade->major_currency} = bcsub($_month->{$trade->major_currency}, $trade->major_fee, getPrecision($trade->major_currency));
        $_month->{$trade->minor_currency} = bcsub($_month->{$trade->minor_currency}, $trade->minor_fee, getPrecision($trade->minor_currency));

        $this->redis->hmset('trades:fees:total', (array)$_month);
    }

    private function _fixGraph($trade) {
        $day = date('Y-m-d', $trade->_created / 1000);
        $book = $trade->major_currency . '_' . $trade->minor_currency;

        $tradeIds = $this->redis->lrange('trades:' . $book, 0, -1);

        $trades = array();
        foreach ($tradeIds as $tradeId) {
            $tradeId = _numeric($tradeId);
            $_trade  = (array)$this->get($tradeId);

            $_day = date('Y-m-d', $_trade['_created'] / 1000);

            if (strtotime($day) > strtotime($_day)) {
                break;
            }
            else if ($day == $_day) {
                $trades[] = $_trade;
            }
        }

        $data = array(
            'count'   => 0,
            'value'   => '0',
            'volume'  => '0',
            'low'     => '10000', // start high on purpose
            'high'    => '0',
            'average' => '0'
        );

        for ($i = count($trades) - 1; $i > 0; $i--) {
            $_trade = $trades[$i];

            $data['last']    = $_trade['rate'];
            $data['count']   = bcadd($data['count'], 1, 0);
            $data['value']   = bcadd($data['value'], $_trade['rate'], 2);
            $data['volume']  = bcadd($data['volume'], $_trade['amount'], 8);
            $data['low']     = bccomp($data['low'], $_trade['rate'], 2) > 0 ? $_trade['rate'] : $data['low'];
            $data['high']    = bccomp($data['high'], $_trade['rate'], 2) < 0 ? $_trade['rate'] : $data['high'];
            $data['average'] = bcdiv($data['value'], $data['count'], 2);
        }

        $key = 'stats:trades:' . $book . ':' . $day;
        $this->redis->hmset($key, $data);
    }

    public function revertTrade($tradeId) {
        $trade = $this->get($tradeId, true);

        if ($trade) {
            // Give back the funds to the users
            $majorBalances = $this->user_balance_model->get($trade->major_client);
            $minorBalances = $this->user_balance_model->get($trade->minor_client);

            $major = $trade->major_currency;
            $minor = $trade->minor_currency;

            $book = $major . '_' . $minor;

            $majorBalances->{$major} = bcadd($majorBalances->{$major}, $trade->amount, getPrecision($major));
            $majorBalances->{$minor} = bcsub($majorBalances->{$minor}, $trade->value, getPrecision($minor));

            $this->user_balance_model->save($trade->major_client, $majorBalances);

            $minorBalances->{$major} = bcsub($minorBalances->{$major}, $trade->amount, getPrecision($major));
            $minorBalances->{$minor} = bcadd($minorBalances->{$minor}, $trade->value, getPrecision($minor));

            $this->user_balance_model->save($trade->minor_client, $minorBalances);

            // Delete the trades
            $this->redis->del('trade:' . $tradeId);

            // Also from the book
            $this->redis->lrem('trades:' . $book, 0, 'trade:' . $tradeId);
            $this->redis->lrem('user:' . $trade->major_client . ':trades', 0, 'trade:' . $tradeId);
            $this->redis->lrem('user:' . $trade->minor_client . ':trades', 0, 'trade:' . $tradeId);

            // Hardcore: Recalculate the fees
            $this->_fixFees($trade);

            // Hardcore: Change the graph for that day
            $this->_fixGraph($trade);

            $this->caching_model->delete('user:' . $trade->major_client . ':trades');
            $this->caching_model->delete('user:' . $trade->minor_client . ':trades');
            $this->caching_model->delete('trades:' . $book . ':*');
        }
    }

    public function getFillPrice($book, $direction, $toSpend, $userId = null, $returnOrders = false) {
        $books = $this->meta_model->getBooks();

        if (!isset($books[$book]))
            return false;

        list($major, $minor) = explode("_", $book);

        $majorPrecision = getPrecision($major);
        $minorPrecision = getPrecision($minor);

        $outTotal = '0';
        $outFee   = '0';
        $orders   = array();

        $userFee = $userId ? $this->getTradeFee($userId) : null;

        $statusFee = $this->admin_model->getFeeData('status');

        if ($direction == 'buy') {
            $sells = $this->order_model->getOrders('orders:sell:' . $book, 100);

            $fee   = '0';

            foreach ($sells as $data) {
                $amount = $data->amount;
                $value  = $data->value;

                if (bccomp($value, $toSpend, $minorPrecision) > 0) {
                    $ratio  = bcdiv($toSpend, $value, $majorPrecision + 2); // Add 2 to the precision to ensure rounding is ok
                    $amount = bcmul($amount, $ratio, $majorPrecision);
                }

                if ($userFee)
                    $fee = bcround(bcmul($amount, $userFee, $majorPrecision + 2), $majorPrecision);

                if ($returnOrders) {
                    $orders[$data->id] = array(
                        'amount' => $amount,
                        'rate'   => $data->rate
                    );
                }

                if ($userId && $data->client == $userId) {
                    $orders[$data->id] = 'delete';

                    continue;
                }

                $outTotal = bcadd($outTotal, $amount, $majorPrecision);
                $outFee   = bcadd($outFee, $fee, $majorPrecision);

                $toSpend = bcsub($toSpend, $value, $minorPrecision);
                if (bccomp($toSpend, '0', $minorPrecision) < 1)
                    break;
            }
        }
        else if ($direction == 'sell') {
            $buys = $this->order_model->getOrders('orders:buy:' . $book, 100);

            foreach ($buys as $data) {
                $amount = $data->amount;
                $rate   = $data->rate;
                $value  = $data->value;

                if (bccomp($amount, $toSpend, $majorPrecision) > 0) {
                    $amount = $toSpend;
                    $value  = bcmul($amount, $rate, $minorPrecision);
                }

                if ($userFee)
                    $fee = bcround(bcmul($value, $userFee, $minorPrecision + 2), $minorPrecision);

                if ($returnOrders) {
                    $orders[$data->id] = array(
                        'amount' => $amount,
                        'rate'   => $data->rate
                    );
                }

                if ($userId && $data->client == $userId) {
                    $orders[$data->id] = 'delete';

                    continue;
                }

                $outTotal = bcadd($outTotal, $value, $minorPrecision);
                $outFee   = bcadd($outFee, $fee, $minorPrecision);

                $toSpend = bcsub($toSpend, $amount, $majorPrecision);
                if (bccomp($toSpend, '0', $majorPrecision) < 1)
                    break;
            }
        }

        $net = bcsub($outTotal, $outFee, $direction == 'buy' ? $majorPrecision : $minorPrecision);

        $data = array(
            'total'    => $outTotal,
            'fees'     => $outFee,
            'net'      => $net,
            'currency' => $direction == 'buy' ? $major : $minor
        );

        if ($returnOrders)
            $data['orders'] = $orders;


        return $data;
    }

    public function getTradeFee($userId) {
        $status = $this->admin_model->getFeeData('status');
        if($status == "disabled"){
            $userFee = null;
        } else {
            $userFee = $this->redis->hget('user:' . $userId, 'trade_fee');
            if ($userFee == '')
                $userFee = null;

            if ($userFee == null) {
                //todo insart commented out the following block
                /*
                //It adds a commission depending on the trading volume
                
                if ($this->redis->exists('fee:structure')) {
                    $rollingVolume = $this->getUserVolume($userId);
    
                    $range = $this->redis->zrangebyscore('fee:structure', $rollingVolume, '+inf', 'LIMIT', 0, 1);
    
                    if ($range)
                        $userFee = $range[0];
                }
                */

                // Default fee if none were set above
                if (!$userFee)
                    $userFee = $this->redis->get('trades:fee');
            }
        }



        return $userFee;
    }
    //todo insart added the next 2 functions
    public function getGlobalTradeFee() {
        return $this->redis->get('trades:fee');
    }

    public function setGlobalTradeFee($fee) {
        return $this->redis->set('trades:fee', $fee);
    }

    public function deleteRogueTrades($from) {
        $tradeId = $from;
        while (true) {
            $trade = $this->get($tradeId);

            if ($trade) {
                $majorBalances = $this->user_balance_model->get($trade->major_client);
                $minorBalances = $this->user_balance_model->get($trade->minor_client);

                $major = $trade->major_currency;
                $minor = $trade->minor_currency;

                $book = $major . '_' . $minor;

                $majorBalances->{$major} = bcadd($majorBalances->{$major}, $trade->amount, getPrecision($major));
                $majorBalances->{$minor} = bcsub($majorBalances->{$minor}, $trade->value, getPrecision($minor));

                $this->user_balance_model->save($trade->major_client, $majorBalances);

                $minorBalances->{$major} = bcsub($minorBalances->{$major}, $trade->amount, getPrecision($major));
                $minorBalances->{$minor} = bcadd($minorBalances->{$minor}, $trade->value, getPrecision($minor));

                $this->user_balance_model->save($trade->minor_client, $minorBalances);

                // Delete the trades
                $this->redis->del('trade:' . $tradeId);

                // Also from the book
                $this->redis->lrem('trades:' . $book, 0, 'trade:' . $tradeId);
                $this->redis->lrem('user:' . $trade->major_client . ':trades', 0, 'trade:' . $tradeId);
                $this->redis->lrem('user:' . $trade->minor_client . ':trades', 0, 'trade:' . $tradeId);

                $tradeId++;
            }
            else break;
        }

        $this->redis->set('id_trade', $from);
    }

    // Get the total amount of BTC in existence ever!
    public function getTotalBTC() {
        $key   = 'total:btc';
        $value = $this->redis->get($key);
        if (!$value) {
            $this->load->library('curl');

            $json = $this->curl->simple_get('http://api.cbix.ca/v1/summary');
            $obj = json_decode($json);
            $value = $obj->data->total_coins;
            if ($value) {
                $this->redis->set($key, $value);
                $this->redis->expire($key, ONE_DAY);
            }
        }

        return $value;
    }

    public function getMarketCap() {
        $key   = 'market:cap';
        $value = $this->redis->get($key);
        if (!$value) {
            $this->load->library('curl');

            $json = $this->curl->simple_get('http://api.cbix.ca/v1/summary');
            $obj = json_decode($json);
            $value = $obj->data->market_cap;
            if ($value) {
                $this->redis->set($key, $value);
                $this->redis->expire($key, ONE_DAY);
            }
        }

        return $value;
    }

    public function getUserVolume($userId, $days = 30) {
        $rollingVolume = $this->caching_model->get('user:' . $userId . ':volume:' . $days . 'days');
        if ($rollingVolume == null) {
            $thirtyDays    = date('Ymd', ($this->now / 1000) - ($days * 24 * 3600));
            $daysVolume    = $this->redis->zrangebyscore('stats:user:' . $userId . ':volume', $thirtyDays, '+inf');
            $rollingVolume = '0';

            foreach ($daysVolume as $vol)
                $rollingVolume = bcadd($rollingVolume, $vol, 8);

            $this->caching_model->save($rollingVolume, ONE_HOUR);
        }

        return $rollingVolume;
    }

    public function getFeeStructure() {
        return $this->redis->zrange('fee:structure', 0, -1, 'WITHSCORES');
    }
}
