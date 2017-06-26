<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Auth_Api.php');

class Trade extends Auth_api
{

    protected $postData;
    private $_obj_Memcache;

    private $locks = array();

    public function __construct()
    {
        parent::__construct();
        $this->load->model('rest_user_model');
        $this->load->model('user_balance_model');
        $this->load->model('trade_model');
        $this->load->model('order_model');
        $this->load->model('meta_model');
        $this->load->model('admin_model');
        $this->load->model('rest_wallet_model');
        $this->load->library('api_security');
        $this->_obj_Memcache  = new Memcache();

        //Load user from session.
        if(empty($this->sessionData) === false) {
            $this->user = $this->rest_user_model->loadFromSession($this->sessionData);
        }
    }
    /* Function to get users buy and sell orders and latest trades.
     * @param $error integer - has the following values
     *  51. Unauthorised user
     * */
    public function getCurrentBuy()
    {
        $major = $this->config->item('default_major');
        $minor = $this->config->item('default_minor');

        $aSessionArray['buy']    = $this->order_model->getOrders('orders:buy:' . $major . '_' . $minor);
        $aSessionArray['buy'] = $this->extractOutputData($aSessionArray['buy']);

        $error = 51;

        if (isset($this->user)) {
            $this->_displaySuccess($aSessionArray);
        } else {
            $this->_displayErrorUnauthorised(array("code" => $error ));
        }
    }
    /* Function to get users buy and sell orders and latest trades.
     * @param $error integer - has the following values
     *  51. Unauthorised user
     * */
    public function getCurrentSell()
    {
        $major = $this->config->item('default_major');
        $minor = $this->config->item('default_minor');

        $aSessionArray['sell']   = $this->order_model->getOrders('orders:sell:' . $major . '_' . $minor);
        $aSessionArray['sell'] = $this->extractOutputData($aSessionArray['sell']);
        $error = 51;

        if (isset($this->user)) {
            $this->_displaySuccess($aSessionArray);
        } else {
            $this->_displayErrorUnauthorised(array("code" => $error ));
        }
    }
    /* Function to get users buy and sell orders and latest trades.
     * @param $error integer - has the following values
     *  51. Unauthorised user 
     * */
    public function getMostRecentTrades()
    {
            $major = $this->config->item('default_major');
            $minor = $this->config->item('default_minor');

            $aSessionArray['trades'] = $this->trade_model->getTrades($major . '_' . $minor, 100);
            $aSessionArray['trades'] = $this->extractOutputData($aSessionArray['trades']);
            $error = 51;

            if (isset($this->user)) {
                $this->_displaySuccess($aSessionArray);
            } else {
                $this->_displayErrorUnauthorised(array("code" => $error ));
            }
    }
    /* Function to get users buy and sell orders and latest trades.
     * @param $error integer - has the following values
     *  52. Unauthorised user 
     * */
    public function getOrders()
    {
        if (empty($this->_obj_Memcache->get('orders:' . $this->userId) ) === true) {
            $data = $this->order_model->getForUser($this->userId);
            $data = $this->extractOrdersOutputData($data);
            $this->_obj_Memcache->set('orders:' . $this->userId, $data, 86400);
        } else {
            $data = $this->_obj_Memcache->get('orders:' . $this->userId);
        }
        $error = 52;
        $aSessionArray['orders'] = $data;
        if (isset($this->user)) {
            $this->_displaySuccess($aSessionArray);
        } else {
            $this->_displayErrorUnauthorised(array("code" => $error));
        }
    }

    public function extractOrdersOutputData($aInputArray){
        $aInputArray = json_decode(json_encode($aInputArray), true);
        $aReturnArray = array();
        $aAllowedKeys = array('amount', 'rate', 'value','type','uid');
        foreach($aInputArray as $aEntry)
        {
            $aReturnArray[] = array_intersect_key($aEntry, array_flip($aAllowedKeys));
        }
        return ($aReturnArray);
    }
    /* Function to get users buy and sell orders and latest trades.
     * @param $error integer - has the following values
     *  53. Unauthorised user 
     * */
    public function getGraphData()
    {
        if(empty($this->_obj_Memcache->get('chartdata') ) === true) {
            $major = $this->config->item('default_major');
            $minor = $this->config->item('default_minor');
            $book = $major . '_' . $minor;
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
        $error = 53;
        if (isset($this->user)) {
            $aSessionArray["chartdata"] = json_encode($cdata);
            $this->_displaySuccess($aSessionArray);
        }else {
            $this->_displayErrorUnauthorised(array("code" => $error ));
        }

    }
    public function balance() {
        $data['verified']   = $this->user->verified;
        $data['currencies'] = $this->meta_model->getAllCurrencies();
        $data['balances']   = $this->user_balance_model->get($this->userId);

        $this->load->model(array('deposit_model', 'withdrawal_model'));

        $this->deposit_model->getCountForUser($this->userId, 'pending');
        $this->withdrawal_model->getCountForUser($this->userId, 'pending');

        $deposits    = $this->deposit_model->getSubsetForUser(1, 10);
        $withdrawals = $this->withdrawal_model->getSubsetForUser(1, 10);

        $transactions = array();
        foreach ($deposits as $deposit) {
            $transactions[$deposit->_created] = array(
                'type'     => 'deposit',
                'amount'   => $deposit->amount,
                'currency' => $deposit->currency
            );
        }

        foreach ($withdrawals as $withdrawal) {
            $transactions[$withdrawal->_created] = array(
                'type'     => 'withdrawal',
                'amount'   => $withdrawal->amount,
                'currency' => $withdrawal->currency
            );
        }
        $this->load->helper('date_helper');

        $data['volumeTotal'] = $this->trade_model->getUserVolume($this->userId, round((now() - ($this->user->_created / 1000)) / (24 * 3600)));
        $data['transactions'] = $transactions;
        $data['volume']       = $this->trade_model->getUserVolume($this->userId);

        $this->_displaySuccess($data);
    }

    private function _getMarketStats($book = null)
    {
        $data = array();
        if (empty($book) === true) {
            $major = $this->config->item('default_major');
            $minor = $this->config->item('default_minor');
            $book = $major . '_' . $minor;
        }
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

        return $data;
    }

    public function marketOverview($book = null)
    {
        $data = $this->_getMarketStats($book);
        $this->_displaySuccess($data);
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

    public function extractOutputData($aInputArray){
        $aInputArray = json_decode(json_encode($aInputArray), true);
        $aReturnArray = array();
        $aAllowedKeys = array('amount', 'rate', 'value');
        foreach($aInputArray as $aEntry)
        {
            $aReturnArray[] = array_intersect_key($aEntry, array_flip($aAllowedKeys));
        }
        return ($aReturnArray);
    }

    public function getClosedOrders(){
        $aReturnArray = array();
        $aReturnArray['entries'] = array();
        $data = $this->rest_wallet_model->getTradesData($this->userId);
        $aReturnArray['count'] = $data['count'];
        foreach($data['entries'] as $order)
        {
            $tempData = array();
            $tempData['amount'] = $order->major_total;
            $tempData['type'] = $order->type;
            $tempData['price'] = $order->rate;
            $tempData['value'] = $order->value;
            $aReturnArray['entries'][] = $tempData;
        }
        $this->_displaySuccess($aReturnArray);
    }
    /*
    * @param $code integer - has the following values
    *  271. Please provide all proper parameter values
    *  272. Unauthorised user
    * */
    public function getRate() {
        $book      = $this->postData['book'];
        $direction = $this->postData['direction'];
        $amount    = $this->postData['amount'];

        if(empty($book) && empty($direction) && empty($direction)){
            $this->_displayBadRequest(array("code" => 271));
        }
        if ($this->user === 'guest') {
            $this->_displayErrorUnauthorised(array("code" => 272));
        }

        $result = $this->trade_model->getFillPrice($book, $direction, $amount, $this->userId);
        $this->_displaySuccess($result);
    }
}
