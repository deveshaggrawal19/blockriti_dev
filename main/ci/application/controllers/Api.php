<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

    protected $postData;

    protected $user;
    protected $userId;
    protected $ip;
    protected $language;

    protected $properties;
    protected $auth;

    private $locks = array();

    public function __construct() {
        parent::__construct();

        // Make sure we receive the format we expect
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $hack = explode(';', $_SERVER['CONTENT_TYPE']);
            $_ct  = array_shift($hack);
            if ($_ct !== 'application/json' && $_ct !== 'application/x-www-form-urlencoded') {
                header('HTTP/1.1 415 Unsupported Media Type');
                exit;
            }
            if ($_ct == 'application/json')
               $this->postData = json_decode(file_get_contents("php://input"));
            else $this->postData = $_POST;
        }
        //todo libraries used, report
        $this->load->library('redis');
        $this->load->library('api_security');
        //todo Models that are used for api
        $this->load->model('redis_model');
        $this->load->model('api_model');
        $this->load->model('trade_model');
        $this->load->model('order_model');
        $this->load->model('meta_model');
        $this->load->model('permissions_model');
        $this->load->model('user_model');
        $this->load->model('note_model');
        $this->load->model('user_balance_model');
        $this->load->model('caching_model');
        $this->load->model('deposit_model');
        $this->load->model('withdrawal_model');
        $this->load->model('notification_model');
        $this->load->model('admin_model');
        $this->load->model('bitcoin_model');
        //todo meta modle
        $this->meta_model->getBooks();
        $this->meta_model->getAllCurrencies();

        $this->properties = (object)array();

        $this->ip     = getIp();
        $this->userId = null;

        // We need to load the default language cause otherwise we don't see messages
        // like the error 200 - Too many requests
        $language = $this->config->item('default_lang');

        // Load user/language stuff if available
        $this->user = $this->user_model->loadFromSession();
        if (isset($this->user->language)) $language = $this->user->language;

        $this->lang->load('controllers', $language);
        $this->lang->load('models', $language);
        $this->lang->load('misc', $language);

        $this->language = $language;
    }

    public function brokerage (){
        $this->load->library('curl');
        $this->load->model('exchange_model');
        $brokerage = $this->exchange_model->getBrokerage();
        if(!$brokerage){
            $brokerage_perecent = $this->exchange_model->getBrokerageRate(); //3%
            if(!$brokerage_perecent){$brokerage_perecent = 3;}
            $data = $this->curl->simple_get('http://104.155.142.137:8000/list_btc_prices');
            if($data){
                $price_obj = json_decode($data);
                $inr_price = $price_obj->latest->currencies->INR;
                $buy_price = $inr_price->ask + ($inr_price->ask * ($brokerage_perecent/100));
                $sell_price = $inr_price->bid - ($inr_price->bid * ($brokerage_perecent/100));
                $brokerage = array('buy' => $buy_price, 'sell' => $sell_price);
                $this->exchange_model->setBrokerage($brokerage);
            }
        }
        echo json_encode($brokerage);
        die;
    }

    public function confirm_order($type = null){ // $type = buy/sell
        $this->load->model('exchange_model');
        $this->load->model('user_balance_model');
        $this->load->model('brokerage_order_model');
        $this->userId = '17115';

        $this->postData = $_GET;

        if(!$type){
            $type = $this->postData['type'];
        }

        $amount = $this->postData['amount'];
        $amount_value = $amount;
        $currency = $this->postData['currency'];

        if(!$amount || !$currency || !$type){
            $this->_display(array("code" => 154)); // missing fields data
        } else {

            $currency2 = ($currency == 'btc') ? 'cad' : 'btc';
            $brokerage = $this->exchange_model->getBrokerage();
            $brokerage_fee = $this->exchange_model->getBrokerageFee();
            $balances = $this->user_balance_model->get($this->userId);

            $rate = $brokerage[$type];
            //$amount_value = $amount * $rate;
            //$m = $currency2 . "_available";

            $balance_currency = ($type == 'buy')?"cad":'btc';
            $m = ($type == 'buy')?"cad_available":'btc_available';
            if($type == 'buy'){
                $amt = ($currency == 'cad')?$amount:($amount * $rate);
                $amount_value = ($currency == 'cad')?bcmul((1/$rate), $amount, getPrecision($currency2)):bcmul($rate, $amount, getPrecision($currency2));
            } else {
                $amt = ($currency == 'btc')?$amount:($amount * $rate);
                $amount_value = ($currency == 'btc')?bcmul((1/$rate), $amount, getPrecision($currency2)):bcmul($rate, $amount, getPrecision($currency2));
            }

            echo "m:". $m;
            echo ", amt:" . $amt;
            print_r($balances);
            echo "amt val:".$amount_value;

            if ($balances->$m >= ( $amt + ($brokerage_fee / 100) * $amt ) ) {
                $order_data = new stdClass();
                $order_data->major = $currency;
                $order_data->minor = $currency2;
                $order_data->amount = $amount;
                $order_data->brokerage_fee = $brokerage_fee;
                $order_data->rate = $rate;
                $order_data->method = 'api';
                $order_data->value = $amount_value;

                print_r($order_data);

                $order = $this->brokerage_order_model->create($this->userId, $order_data, $type);
                $this->brokerage_order_model->add($order);


                print_r($order);

                die;

                if($type == 'buy'){
                    if($currency == 'cad'){
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'cad', $amount + (($brokerage_fee / 100) * $amount) , 'sub');
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'btc', $order->value , 'add');
                    } else {
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'cad', $order->value + + (($brokerage_fee / 100) * $order->value), 'sub');
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'btc', $amount , 'add');
                    }
                } else {
                    if($currency == 'cad'){
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'btc', $order->value + (($brokerage_fee / 100) * $order->value), 'sub');
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'cad', $amount , 'add');
                    } else {
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'btc', $amount + (($brokerage_fee / 100) * $amount), 'sub');
                        $this->user_balance_model->updateBalanceByCurrency($this->userId, 'cad', $order->value , 'add');
                    }
                }

                $this->_display(array("code" => 152));
            } else {
                $this->_display(array("code" => 153)); // Insufficient balance
            }
        }
    }



    //todo what is this locking and unlocking
    private function lock($single = null) {
        if (!$single)
            $this->locks[] = 'global';
        else $this->locks[] = $single;

        $this->redis_model->lock($single);
    }

    private function unlockAll() {
        foreach ($this->locks as $lock)
            $this->redis_model->unlock($lock == 'global' ? null : $lock);
    }

    protected function _display($data) {
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($data));
        $this->output->_display();

        exit();
    }

    protected function _error($code, $message) {
        $exception = array(
            'error' => array(
                'code'    => $code,
                'message' => $message
            )
        );

        // Release all the locks
        $this->unlockAll();

        return $this->_display($exception);
    }

    protected function _validateFields($required) {
        foreach ($required as $req => $name) {
            if ((!isset($this->auth->{$req}) || empty($this->auth->{$req})) && is_null($this->_getProperty($req)))
                $this->_error(12, _l('is_either_invalid_or_missing', $name, $req));
        }
    }

    protected function _getProperty($key) {
        return isset($this->properties->{$key}) ? $this->properties->{$key} : null;
    }

    protected function checkApiEnabled($which) {
        if ($this->permissions_model->get($which) == 'disabled')
            return $this->_error(301, _l('api_disabled'));
    }

    protected function checkRateLimit() {
        if ($this->api_model->isRateLimited($this->userId))
            $this->_error(200, _l('too_many_requests'));
    }

    // Common functions
    protected function _cancel($orders) {
        if (!is_array($orders))
            $orders = array($orders);

        $this->lock();

        $outOrder = array();

        foreach ($orders as $orderUid) {
            $orderId = $this->order_model->findByUid($orderUid);
            if ($orderId) {
                $order = $this->order_model->get($orderId);

                // Making sure the user is only allowed to cancel his/her order
                if ($order->client == $this->userId && !isset($order->status)) {
                    $this->order_model->cancel($orderId);

                    $package = array(
                        'book' => $order->book
                    );

                    $this->notification_model->direct('user_update', 'user:' . $order->client, $package);
                    $this->notification_model->broadcast('book_update', $package);

                    $outOrder[] = array(
                        'id'     => $orderUid,
                        'result' => 'ok'
                    );
                }
                else {
                    $outOrder[] = array(
                        'id'    => $orderUid,
                        'error' => array(
                            'code'    => 106,
                            'message' => 'Cannot perform request - not found'
                        )
                    );
                }
            }
            else {
                $outOrder[] = array(
                    'id'    => $orderUid,
                    'error' => array(
                        'code'    => 106,
                        'message' => 'Cannot perform request - not found'
                    )
                );
            }
        }

        $this->notification_model->flush();

        // UNLOCK
        $this->redis_model->unlock();

        // This is to make sure the old API v2 still works
        if (count($outOrder) == 1) {
            if (isset($outOrder[0]['error']))
                return false;

            return 'true';
        }

        return $outOrder;
    }

    protected function _lookup_order($orders) {
        if (!is_array($orders))
            $orders = array($orders);

        $outOrder = array();

        foreach ($orders as $orderUid) {
            $orderId = $this->order_model->findByUid($orderUid);
            if ($orderId) {
                $order = $this->order_model->get($orderId);

                // Making sure the user is only allowed to lookup his/her order
                if ($order->client == $this->userId) {
                    $status = 0;

                    if (isset($order->status)) {
                        switch ($order->status) {
                            case 'completed':
                                $status = 2;
                                break;

                            case 'cancelled':
                                $status = -1;
                                break;

                            default:
                                $status = -99; // unknown
                        }
                    } else if ($order->_created != $order->_updated)
                        $status = 1;

                    $data = array(
                        'amount'  => $order->amount,
                        'book'    => $order->book,
                        'created' => date("Y-m-d H:i:s", $order->_created / 1000),
                        'id'      => $order->uid,
                        'price'   => $order->rate,
                        'status'  => (string)$status,
                        'type'    => $order->type == 'buy' ? '0' : '1'
                    );

                    if ($status != 0)
                        $data['updated'] = date("Y-m-d H:i:s", $order->_updated / 1000);

                    $outOrder[] = $data;
                }
                else {
                    $outOrder[] = array(
                        'id'    => $orderUid,
                        'error' => array(
                            'code'    => 106,
                            'message' => 'Cannot perform request - not found'
                        )
                    );
                }
            }
            else {
                $outOrder[] = array(
                    'id'    => $orderUid,
                    'error' => array(
                        'code'    => 106,
                        'message' => 'Cannot perform request - not found'
                    )
                );
            }
        }

        return $outOrder;
    }

    protected function _process($type) {
        $fields = array(
            'major'  => 'Major',
            'minor'  => 'Minor',
            'amount' => 'Amount',
            'rate'   => 'Price'
        );

        $this->_validateFields($fields);

        $major  = $this->_getProperty('major');
        $minor  = $this->_getProperty('minor');
        $amount = $this->_getProperty('amount');
        $rate   = $this->_getProperty('rate');

        $book = $major . '_' . $minor;

        if (!isset($this->meta_model->books[$book]))
            $this->_error(20, _l('invalid_order_book', $book));

        if (empty($amount))
            $this->_error(22, _l('incorrect_amount'));

        if ($amount{0} == '.')
            $amount = '0' . $amount;

        if ($rate{0} == '.')
            $rate = '0' . $rate;

        // WTF, no client ID??!!!!
        if (empty($this->userId)) {
            $this->_error(20, _l('invalid_order_book', $book)); // TODO PROPER ERROR CODE...
        }

        if (!isCurrency($major, $amount))
            $this->_error(22, _l('incorrect_amount', $amount));

        if (!isCurrency($minor, $rate))
            $this->_error(22, _l('incorrect_price', $rate));

        $limits = $this->meta_model->getLimits($book);

        // Check the rate
        if (bccomp($rate, $limits->min_rate, getPrecision($minor)) < 0)
            $this->_error(23, _l('incorrect_price', displayCurrency($minor, $rate, false, false)) . ' ' . _l('is_below_the_minimum_of', displayCurrency($minor, $limits->min_rate, false, false)));

        if (bccomp($rate, $limits->max_rate, getPrecision($minor)) > 0)
            $this->_error(23, _l('incorrect_price', displayCurrency($minor, $rate, false, false)) . ' ' . _l('is_above_the_maximum_of', displayCurrency($minor, $limits->max_rate, false, false)));

        // Check the amount
        if (bccomp($amount, $limits->min_amount, getPrecision($major)) < 0)
            $this->_error(23, _l('incorrect_amount', displayCurrency($major, $amount, false, false)) . ' ' . _l('is_below_the_minimum_of', displayCurrency($major, $limits->min_amount, false, false)));

        if (bccomp($amount, $limits->max_amount, getPrecision($major)) > 0)
            $this->_error(23, _l('incorrect_amount', displayCurrency($major, $amount, false, false)) . ' ' . _l('is_above_the_maximum_of', displayCurrency($major, $limits->max_amount, false, false)));

        // Check the value
        $value = bcmul($rate, $amount, getPrecision($minor));
        if (bccomp($value, $limits->min_value, getPrecision($minor)) < 0)
            $this->_error(23, _l('incorrect_value', displayCurrency($minor, $value, false, false)) . ' ' . _l('is_below_the_minimum_of', displayCurrency($minor, $limits->min_value, false, false)));

        if (bccomp($value, $limits->max_value, getPrecision($minor)) > 0)
            $this->_error(23, _l('incorrect_value', displayCurrency($minor, $value, false, false)) . ' ' . _l('is_above_the_maximum_of', displayCurrency($minor, $limits->max_value, false, false)));

        if ($type == 'sell') {
            $currency = $major;
            $field    = _l('amount');
        }
        else {
            $currency = $minor;
            $amount   = $value;
            $field    = _l('total');
        }

        // LOCK
        $this->lock();
        $this->lock($this->userId); // Lock the user, so they cannot withdraw funds at the same time

        $balances = $this->user_balance_model->get($this->userId);

        $available = $balances->{$currency . '_available'};
        if (bccomp($available, $amount, getPrecision($currency)) === -1)
            $this->_error(21, _l('incorrect_field', $field, displayCurrency($currency, $amount, false, false)) . ' ' . _l('exceeds_available_balance', strtoupper(makeSafeCurrency($currency))));

        // Add funds to locked balance
        $balances->{$currency . '_locked'} = bcadd($balances->{$currency . '_locked'}, $amount, getPrecision($currency));
        $this->user_balance_model->save($this->userId, $balances);

        // Reaching this point will prove the order is fine
        $order = $this->order_model->create($this->userId, $this->properties, $type);

        $this->order_model->add($order);

        $this->trade_model->processTrades($major, $minor);

        $this->trade_model->updateTradeStats($book);

        $package = array(
            'book' => $book
        );

        $this->notification_model->direct('user_update', 'user:' . $this->userId, $package);
        $this->notification_model->broadcast('book_update', $package);
        $this->notification_model->flush();

        // UNLOCK
        $this->redis_model->unlock($this->userId);
        $this->redis_model->unlock();

        return $order;
    }

    protected function _processMarket($type) {
        $fields = array(
            'major'  => 'Major',
            'minor'  => 'Minor',
            'amount' => 'Amount'
        );

        $this->_validateFields($fields);

        $major  = $this->_getProperty('major');
        $minor  = $this->_getProperty('minor');
        $amount = $this->_getProperty('amount');

        $book = $major . '_' . $minor;

        if (!isset($this->meta_model->books[$book]))
            $this->_error(20, _l('invalid_order_book', $book));

        if ($amount{0} == '.')
            $amount = '0' . $amount;

        if (($type == 'buy' && !isCurrency($minor, $amount)) || ($type == 'sell' && !isCurrency($major, $amount))) {
            $this->_error(22, _l('incorrect_amount', $amount));
        }

        $limits   = $this->meta_model->getLimits($book);
        $currency = $type == 'sell' ? $major : $minor;

        // LOCK
        $this->lock();
        $this->lock($this->userId); // Lock the user, so they cannot withdraw funds at the same time

        $balances = $this->user_balance_model->get($this->userId);
        $available = $balances->{$currency . '_available'};

        // WTF, no client ID??!!!!
        if (empty($this->userId)) {
            $this->_error(20, _l('invalid_order_book', $book)); // TODO PROPER ERROR CODE...
        }
        if (bccomp($available, $amount, getPrecision($currency)) === -1)
            $this->_error(21, _l('incorrect_amount', 'amount', displayCurrency($currency, $amount, false, false)) . ' ' . _l('exceeds_available_balance', strtoupper(makeSafeCurrency($currency))));

        $res = $this->trade_model->getFillPrice($book, $type, $amount, $this->userId, true);

        if ($type == 'buy') {
            $checkMajor = $res['total'];
            $checkMinor = $amount;
        } else {
            $checkMajor = $amount;
            $checkMinor = $res['total'];
        }

        // Check the amount
        if (bccomp($checkMajor, $limits->min_amount, getPrecision($major)) < 0)
            $this->_error(23, _l('incorrect_amount', displayCurrency($major, $checkMajor, false, false)) . ' ' . _l('is_below_the_minimum_of', displayCurrency($major, $limits->min_amount, false, false)));

        if (bccomp($checkMajor, $limits->max_amount, getPrecision($major)) > 0)
            $this->_error(23, _l('incorrect_amount', displayCurrency($major, $checkMajor, false, false)) . ' ' . _l('is_above_the_maximum_of', displayCurrency($major, $limits->max_amount, false, false)));

        // Check the value
        if (bccomp($checkMinor, $limits->min_value, getPrecision($minor)) < 0)
            $this->_error(23, _l('incorrect_value', displayCurrency($minor, $checkMinor, false, false)) . ' ' . _l('is_below_the_minimum_of', displayCurrency($minor, $limits->min_value, false, false)));

        if (bccomp($checkMinor, $limits->max_value, getPrecision($minor)) > 0)
            $this->_error(23, _l('incorrect_value', displayCurrency($minor, $checkMinor, false, false)) . ' ' . _l('is_above_the_maximum_of', displayCurrency($minor, $limits->max_value, false, false)));

        $matches = $res['orders'];

        foreach ($matches as $orderId=>$match) {
            if ($match === 'delete') {
                $this->order_model->cancel($orderId);
                continue;
            }

            $matchAmount = $match['amount'];
            $matchRate   = $match['rate'];

            if ($type == 'buy')
                $matchCost = bcmul($matchAmount, $matchRate, getPrecision($currency));
            else $matchCost = $matchAmount;

            // Safety check
            $available = $balances->{$currency . '_available'};
            if (bccomp($available, $matchCost, getPrecision($currency)) === -1)
                break;

            // Add funds to locked balance
            $balances->{$currency . '_locked'} = bcadd($balances->{$currency . '_locked'}, $matchCost, getPrecision($currency));
            $this->user_balance_model->save($this->userId, $balances);

            // Reaching this point will prove the order is fine
            $orderData = new stdClass();
            $orderData->major  = $major;
            $orderData->minor  = $minor;
            $orderData->amount = $matchAmount;
            $orderData->rate   = $matchRate;
            $orderData->method = 'market';
            $order = $this->order_model->create($this->userId, $orderData, $type);

            $this->order_model->add($order);

            $this->trade_model->processTrades($major, $minor);

            $balances = $this->user_balance_model->get($this->userId);
        }

        // Process all the trades once orders have been placed
        $this->trade_model->updateTradeStats($book);

        $package = array(
            'book' => $book
        );

        $this->notification_model->direct('user_update', 'user:' . $this->userId, $package);
        $this->notification_model->broadcast('book_update', $package);
        $this->notification_model->flush();

        // UNLOCK
        $this->redis_model->unlock($this->userId);
        $this->redis_model->unlock();

        return $res;
    }

    public function getInfo($book){


        if ($book != 'all' && in_array($book, array_keys($this->meta_model->books)) === FALSE)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $books = $book == 'all' ? array_keys($this->meta_model->books) : array($book);
        $out = array();
        foreach ($books as $book) {
            $stats = $this->trade_model->getCurrentStats($book);

            $out[$book]['high']      = $stats ? $stats->high : 0;
            $out[$book]['last']      = $this->trade_model->getLastTradePrice($book);
            $out[$book]['timestamp'] = date('U');

            $list = 'orders:buy:' . $book;
            $buy  = $this->order_model->head($list);
            if ($buy)
                $out[$book]['bid'] = $buy->rate;
            else $out[$book]['bid'] = 0;

            $out[$book]['vwap']   = $this->trade_model->getVWAP($book);
            $out[$book]['volume'] = $this->trade_model->getRollingVolume($book);
            $out[$book]['low']    = $stats ? $stats->low : 0;

            $list = 'orders:sell:' . $book;
            $sell = $this->order_model->head($list);
            if ($sell)
                $out[$book]['ask'] = $sell->rate;
            else $out[$book]['ask'] = 0;
        }

        return $out;
    }

    public function getOrderBook(){
        $book = $this->input->get('book'); // optional
        if (empty($book))
            $book = $this->config->item("default_book");

        $group = $this->input->get('group'); // optional
        if (empty($group))
            $group = "1";

        if (!$book)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        if (in_array($book, array_keys($this->meta_model->books)) === FALSE)
            $this->_error(20, 'Unknown OrderBook ' . $book);

        $out              = array();
        $out['timestamp'] = date('U');

        $this->redis_model->lock();

        $list = 'orders:buy:' . $book;
        $bids = $this->order_model->getOrders($list, 1000, $group);

        if ($group)
            $out['bids'] = $bids;
        else $out['bids'] = array_map('_anonymiseOrder2', $bids);

        $list = 'orders:sell:' . $book;
        $asks = $this->order_model->getOrders($list, 1000, $group);

        if ($group)
            $out['asks'] = $asks;
        else $out['asks'] = array_map('_anonymiseOrder2', $asks);

        $this->redis_model->unlock();

        return $out;
    }
}