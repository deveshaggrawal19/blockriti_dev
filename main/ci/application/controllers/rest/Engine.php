<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Auth_Api.php');
class Engine extends Auth_api
{

    protected $postData;

    private $locks = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->model('rest_user_model');
        $this->load->model('trade_model');
        $this->load->model('order_model');
        $this->load->model('meta_model');
        $this->load->model('admin_model');
        $this->load->model('user_balance_model');
        $this->load->library('api_security');
        $this->load->library('firebase_pub');
        $this->_obj_Memcache  = new Memcache();

        //Load user from session.
        if(empty($this->sessionData) === false) {
            $this->user = $this->rest_user_model->loadFromSession($this->sessionData);
        }
        if ($this->postData) {
            foreach ($this->postData as $key => $value) {
                $this->properties->{$key} = $value;
            }
        }
    }
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
    public function buy() {
        $this->_processOrder('buy');
    }

    public function buymarket() {
        $this->_processOrder('buy', 'market');
    }

    private function _processOrder($type, $mode='limit') { 
        $this->properties->method = 'api'; // Making sure the order will be created with the correct method

        switch ($mode) {
            case 'limit':
                $this->_process($type);
                break;

            case 'market':
                $this->_processMarket($type);
                break;
        }

        $this->_display(array("code"=>68));
    }
    /*
    * @param $error integer - has the following values
    *  54. Invalid Order book format
    *  55. Incorrect amount
    *  56. Logged out
    *  57. Exceeds available balance
    *  58. Below minimum allowed amount
    *  59. Above maximum allowed amount  
    *  281. No buyer available, please try after some time.
    *  282. No seller available, please try after some time.
    * */
    private function _processMarket($type) {
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

        if (!isset($this->meta_model->books[$book])) {
            $this->_displayBadRequest(array("code" => 54));
        }
        if ($amount{0} == '.')
            $amount = '0' . $amount;

        if (($type == 'buy' && !isCurrency($minor, $amount)) || ($type == 'sell' && !isCurrency($major, $amount))) {
            $this->_displayBadRequest(array("code" => 55));
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
            // Release all the locks
            $this->unlockAll();
            $this->_displayErrorUnauthorised(array("code" => 56));
        }
        if (bccomp($available, $amount, getPrecision($currency)) === -1) {
            // Release all the locks
            $this->unlockAll();
            $this->_displayBadRequest(array("code" => 57));
        }
        $res = $this->trade_model->getFillPrice($book, $type, $amount, $this->userId, true);

        if ($type == 'buy') {
            $checkMajor = $res['total'];
            $checkMinor = $amount;
            if($res['total'] == '0'){
                $this->unlockAll();
                $this->_displayBadRequest(array("code" => 282));
            }
        } else {
            $checkMajor = $amount;
            $checkMinor = $res['total'];
            if($res['total'] == '0'){
                $this->unlockAll();
                $this->_displayBadRequest(array("code" => 281));
            }
        }

        // Check the amount
        if (bccomp($checkMajor, $limits->min_amount, getPrecision($major)) < 0){
            // Release all the locks
            $this->unlockAll();
            $this->_displayBadRequest(array("code" => 58,"amount"=>$limits->min_amount));
        }

        if (bccomp($checkMajor, $limits->max_amount, getPrecision($major)) > 0){
            // Release all the locks
            $this->unlockAll();
            $this->_displayBadRequest(array("code" => 59,"amount"=>$limits->max_amount));
        }
        // Check the value
        if (bccomp($checkMinor, $limits->min_value, getPrecision($minor)) < 0){
            // Release all the locks
            $this->unlockAll();
            $this->_displayBadRequest(array("code" => 58,"amount"=>$limits->min_value));
        }
        if (bccomp($checkMinor, $limits->max_value, getPrecision($minor)) > 0){
            // Release all the locks
            $this->unlockAll();
            $this->_displayBadRequest(array("code" => 59,"amount"=>$limits->max_value));
        }
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

            //remove the memcache entries for cached user trades.
            $this->_obj_Memcache->delete('user:' . $this->userId . ':trades');
            $this->_obj_Memcache->delete('orders:' . $this->userId );

            $balances = $this->user_balance_model->get($this->userId);
        }

        $package = array(
            'book' => $book
        );

        $this->notification_model->direct('user_update', 'user:' . $this->userId, $package);
        $this->notification_model->broadcast('book_update', $package);
        $this->notification_model->flush();
        $this->notification_model->pushOrderNotification($this->userId);

        // UNLOCK
        $this->redis_model->unlock($this->userId);
        $this->redis_model->unlock();

        return $res;
    }

    protected function _getProperty($key) {
        return isset($this->properties->{$key}) ? $this->properties->{$key} : null;
    }

    protected function _validateFields($required) {
        foreach ($required as $req => $name) {
            if (is_null($this->_getProperty($req))) {
                $this->_displayBadRequest(array("code" => 60));
            }
        }
    }

    public function sell() {
        $this->_processOrder('sell');
    }

    public function sellmarket() {
        $this->_processOrder('sell', 'market');
    }
    /*
    * @param $error integer - has the following values
    *  54. Invalid Order book format
    *  55. Incorrect amount
    *  56. Logged out
    *  60. Invalid parameter keys
    *  61. Incorrect rate
    *  62. Rate is below min allowed
    *  63. Rate is above max allowed
    *  64. Amount is below min allowed
    *  65. Amount is above max allowed
    *  66. Value is below min allowed
    *  67. Value is above max allowed
    * */
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

        if (!isset($this->meta_model->books[$book])) {
            $this->_displayBadRequest(array("code" => 54));
        }
        if (empty($amount)) {
            $this->_displayBadRequest(array("code" => 55));
        }
        if ($amount{0} == '.')
            $amount = '0' . $amount;

        if ($rate{0} == '.')
            $rate = '0' . $rate;

        // WTF, no client ID??!!!!
        if (empty($this->userId)) {
            $this->_displayErrorUnauthorised(array("code" => 56));
        }

        if (!isCurrency($major, $amount)){
            $this->_displayBadRequest(array("code" => 55));
        }

        if (!isCurrency($minor, $rate)) {
            $this->_displayBadRequest(array("code" => 61));
        }
        $limits = $this->meta_model->getLimits($book);

        // Check the rate
        if (bccomp($rate, $limits->min_rate, getPrecision($minor)) < 0){
            $this->_displayBadRequest(array("code" => 62,"amount"=>$limits->min_rate));
        }
        if (bccomp($rate, $limits->max_rate, getPrecision($minor)) > 0){
            $this->_displayBadRequest(array("code" => 63,"amount"=>$limits->max_rate));
        }
        // Check the amount
        if (bccomp($amount, $limits->min_amount, getPrecision($major)) < 0){
            $this->_displayBadRequest(array("code" => 64,"amount"=>$limits->min_amount));
        }
        if (bccomp($amount, $limits->max_amount, getPrecision($major)) > 0){
            $this->_displayBadRequest(array("code" => 65,"amount"=>$limits->max_amount));
        }
        // Check the value
        $value = bcmul($rate, $amount, getPrecision($minor));
        if (bccomp($value, $limits->min_value, getPrecision($minor)) < 0){
            $this->_displayBadRequest(array("code" => 66,"amount"=>$limits->min_value));
        }
        if (bccomp($value, $limits->max_value, getPrecision($minor)) > 0){
            $this->_displayBadRequest(array("code" => 67,"amount"=>$limits->max_value));
        }
        if ($type == 'sell') {
            $currency = $major;
        }
        else {
            $currency = $minor;
            $amount   = $value;
        }

        // LOCK
        $this->lock();
        $this->lock($this->userId); // Lock the user, so they cannot withdraw funds at the same time

        $balances = $this->user_balance_model->get($this->userId);

        $available = $balances->{$currency . '_available'};
        if (bccomp($available, $amount, getPrecision($currency)) === -1){
            // Release all the locks
            $this->unlockAll();
            $this->_displayBadRequest(array("code" => 57));
        }
        // Add funds to locked balance
        $balances->{$currency . '_locked'} = bcadd($balances->{$currency . '_locked'}, $amount, getPrecision($currency));
        $this->user_balance_model->save($this->userId, $balances);

        // Reaching this point will prove the order is fine
        $order = $this->order_model->create($this->userId, $this->properties, $type);

        $this->order_model->add($order);

        $this->trade_model->processTrades($major, $minor);

        //remove the memcache entries for cached user trades.
        $this->_obj_Memcache->delete('user:' . $this->userId . ':trades');
        $this->_obj_Memcache->delete('orders:' . $this->userId );


        $package = array(
            'book' => $book
        );

        $this->notification_model->direct('user_update', 'user:' . $this->userId, $package);
        $this->notification_model->broadcast('book_update', $package);
        $this->notification_model->flush();
        $this->notification_model->pushOrderNotification($this->userId);

        // UNLOCK
        $this->redis_model->unlock($this->userId);
        $this->redis_model->unlock();

        return $order;
    }

    public function cancelOrders()
    {
        $ids = $this->postData['id'];
        if(is_array($ids) === false)
        {
            $ids = array($ids);
        }
        $return = $this->_cancel($ids);
        $this->_obj_Memcache->delete('orders:' . $this->userId );
        $this->_displaySuccess($return);
    }

    /*
     * Function to deposit coupons
     * Returns the following error codes
     * 241 : The user does not have access to the order.
     * 242 : Order not found
     * 250 : Cancel Successful
     *
     * */
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
                        'result' => 'success',
                        'code' => 250
                    );
                    $this->notification_model->flush();
                    $this->notification_model->pushCancelOrderNotification($this->userId,$orderId);
                }
                else {
                    $outOrder[] = array(
                        'id'    => $orderUid,
                        'error' => array(
                            'code'    => 241,
                            'message' => 'Cannot perform request - invalid order id'
                        )
                    );
                }
            }
            else {
                $outOrder[] = array(
                    'id'    => $orderUid,
                    'error' => array(
                        'code'    => 242,
                        'message' => 'Cannot perform request - order not found'
                    )
                );
            }
        }

        // UNLOCK
        $this->redis_model->unlock();
        return $outOrder;
    }

    public function updatePublicData(){

        $major = $this->config->item('default_major');
        $minor = $this->config->item('default_minor');
        $book = $major . '_' . $minor;

        //Get Current buy data
        $aBuyArray['buy']    = $this->order_model->getOrders('orders:buy:' . $major . '_' . $minor);
        $aBuyArray['buy'] = $this->extractOutputData($aBuyArray['buy']);
        $this->firebase_pub->setData('getCurrentBuy', $aBuyArray);

        //Get Current buy data
        $aSellArray['sell']   = $this->order_model->getOrders('orders:sell:' . $major . '_' . $minor);
        $aSellArray['sell'] = $this->extractOutputData($aSellArray['sell']);
        $this->firebase_pub->setData('getCurrentSell', $aSellArray);

        //Get graph data
        $aChartArray = array();
        if(empty($this->_obj_Memcache->get('chartdata') ) === true) {
            $plots = $this->trade_model->getGraph($book, 10000);
            $cdata = array();

            foreach ($plots as $date => $plotData) {
                $item = array(
                    'date' => $date,
                    'value' => $plotData['value'],
                    'volume' => $plotData['volume'],
                    'low' => $plotData['low'],
                    'high' => $plotData['high'],
                    'open' => $plotData['open'],
                    'close' => $plotData['close']
                );

                $cdata[] = $item;
            }
            $this->_obj_Memcache->set('chartdata', $cdata, 86400);
        }
        else{
            $cdata = $this->_obj_Memcache->get('chartdata');
        }

        $aChartArray["chartdata"] = $cdata;
        $this->firebase_pub->setData('getGraphData', $aChartArray);

        //Get Market Stats
        $data = array();
        $data['currencies'] = explode('_', $book);
        $data['lastPrice'] = $this->trade_model->getLastTradePrice($book);
        $data['volume']    = $this->trade_model->getRollingVolume($book);
        $days = $this->trade_model->twentyFourHourChange($book);

        if (!$days)
            $days = array(5948, 5948);

        $data['change'] = $days[0] - $days[1];

        $perc = ($days[0] - $days[1]) / $days[1];

        if (empty($perc) === true) {
            $data['perc'] = 0;
        } else {
            $perc = $perc * 100;
            $data['perc'] = $this->roundSigDigs($perc, 2);
        }
        $this->firebase_pub->setData('getMarketOverview', $data);

        //Get Most Recent trades
        $aTradeArray = array();
        $aTradeArray['trades'] = $this->trade_model->getTrades($major . '_' . $minor, 100);
        $aTradeArray['trades'] = $this->extractOutputData($aTradeArray['trades']);
        $this->firebase_pub->setData('getMostRecentTrades', $aTradeArray);
    }

    private function extractOutputData($aInputArray){
        $aInputArray = json_decode(json_encode($aInputArray), true);
        $aReturnArray = array();
        $aAllowedKeys = array('amount', 'rate', 'value');
        foreach($aInputArray as $aEntry)
        {
            $aReturnArray[] = array_intersect_key($aEntry, array_flip($aAllowedKeys));
        }
        return ($aReturnArray);
    }

    function roundSigDigs($number, $sigdigs) {
        $neg = 1;
        if ($number < 0) {
            $number = abs($number);
            $neg = -1;
        }

        $multiplier = 1;
        while ($number < 0.1) {
            $number *= 10;
            $multiplier /= 10;
        }

        while ($number >= 1) {
            $number /= 10;
            $multiplier *= 10;
        }

        return round($number, $sigdigs) * $multiplier * $neg;
    }

}