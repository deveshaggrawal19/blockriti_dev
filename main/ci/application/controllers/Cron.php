<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Cron extends CI_Controller {
    public function __construct() {
        parent::__construct();

        $this->load->library('redis');
        $this->load->library('email');

        $this->load->model('email_queue_model');
        $this->load->model('meta_model');
        $this->load->model('admin_model');
        $this->load->model('caching_model');
    }

    public function emails() {
        // Enable logging
        $log = $this->start_log('emails_' . gmdate('Ymd'));

        for ($i = 0; $i < 50; $i++) {
            $data = $this->email_queue_model->pop();

            if (!$data) {
                $this->log($log, ' -- nothing to send --');

                break;
            }

            $this->log($log, 'Email: ' . $data->email);
            $this->log($log, 'Subject: ' . $data->subject);
            $this->log($log, 'Message: ' . $data->message);

            $this->email->clear();

            $this->email->from($this->config->item('contact_email'), $this->config->item('site_name'));

            $this->email->to($data->email);
            $this->email->subject($data->subject);

            $message = $data->message;

            $this->email->message($message);

            $this->email->send();
        }

        $this->close_log($log);
    }

    private function start_log($logFile = 'cron_log') {
        return fopen(APPPATH . "logs/" . $logFile . '.log', "ab");
    }

    private function log($handle, $info) {
        if ($handle) {
            fwrite($handle, '[' . date('Y-m-d H:i:s') . '] ' . $info . PHP_EOL);
        }
        else {
            echo $info . '<br/>';
        }
    }

    private function close_log($handle) {
        if ($handle) fclose($handle);
    }

    public function pendingDeposits() {
        $this->load->helper('date_helper');
        $this->load->model('deposit_model');
        $this->load->model('user_model');
        $this->load->model('user_balance_model');

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

                            $this->email_queue_model->email   = $user->email;
                            $this->email_queue_model->message = $this->load->view('emails/pending_interac', $data, true);
                            $this->email_queue_model->subject = 'Pending Interac Online Deposit - ' . $this->config->item('site_full_name');

                            $this->email_queue_model->store();

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
        //todo shouldnt need this unless it's referring to bitgo - removed the code

    public function updateBrokerage (){
        $this->load->library('curl');
        $this->load->model('exchange_model');

        $brokerage_perecent = $this->exchange_model->getBrokerageRate(); //3%
        if(!$brokerage_perecent){
            $brokerage_perecent = 3;
            $this->exchange_model->setBrokerageRate($brokerage_perecent);
        }
        $data = $this->curl->simple_get('http://104.155.142.137:8000/list_btc_prices');
        if($data){
            $price_obj = json_decode($data);
            $inr_price = $price_obj->latest->currencies->INR;
            $buy_price = $inr_price->ask + ($inr_price->ask * ($brokerage_perecent/100));
            $sell_price = $inr_price->bid - ($inr_price->bid * ($brokerage_perecent/100));
            $borkerage = array('buy' => $buy_price, 'sell' => $sell_price);
            $this->exchange_model->setBrokerage($borkerage);
            echo 'Brokerage update successfully.';
            //print_r($borkerage);
        }
    }
    public function getExchangeRate() {
        $this->load->library('curl');
        $this->load->model('exchange_model');

        $data = $this->curl->simple_get('http://openexchangerates.org/api/latest.json?app_id=f7bd391b82f94ffca7f793035ef0c3b8');
        if ($data) {
            $data = json_decode($data);

            // Adding some security
            if (isset($data->rates) && isset ($data->rates->CAD)) {
                $rate = $data->rates->CAD;
                $this->exchange_model->setRate('usd', 'cad', $rate);

                $reverse = bcdiv('1', $rate, 6);
                $this->exchange_model->setRate('cad', 'usd', $reverse);
            }
        }
    }
    //todo do we need this LTC/USD?
    public function calculateLocalTrades() {
        // Get the last known USD trade price from BTCe
        $this->load->library('curl');
        $this->load->model('exchange_model');

        // We just need to get LTC data from btc-e
        $this->curl->ssl(false, 0);
        $data = json_decode($this->curl->simple_get('https://btc-e.com/api/2/ltc_usd/ticker'));

        if ($data) {
            $usdPrice  = $data->ticker->last;
            $exchange  = $this->exchange_model->getRate('usd', 'cad');
            $cadPrice  = bcmul($usdPrice, $exchange, 2);
            $cadOffset = bcmul($cadPrice, 0.07, 2);

            $cadBuyPrice  = bcsub($cadPrice, $cadOffset, 2);
            $cadSellPrice = bcadd($cadPrice, $cadOffset, 2);

            $this->exchange_model->setLocalRate('ltc', $cadSellPrice, $cadBuyPrice);
        }

        $this->load->model('trade_model');
        $cadPrice  = $this->trade_model->getLastTradePrice('btc_cad');
        $cadOffset = bcmul($cadPrice, 0.05, 2);

        $cadBuyPrice  = bcsub($cadPrice, $cadOffset, 2);
        $cadSellPrice = bcadd($cadPrice, $cadOffset, 2);

        $this->exchange_model->setLocalRate('btc', $cadSellPrice, $cadBuyPrice);
    }
//todo find easybitcoin lib
    public function bitcoinAutoWithdrawals() {
        $this->load->library('easybitcoin');

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
                        $balance = $this->easybitcoin->getbalance('withdrawals');

                        if (bccomp($balance, $withdrawal->amount, 8) > 0) {
                            $transactionId = $this->easybitcoin->sendfrom('withdrawals', $withdrawal->details->address, (float)$withdrawal->amount);

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
}
