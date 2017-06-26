<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Trade extends MY_Controller {

    public function __construct() {
        parent::__construct();
        show_404();
        $this->meta_model->getBooks();
        $this->load->model('admin_model');
    }

	public function index($major = null, $minor = null) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error',  _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

        if($this->admin_model->getRenovationData('status') != "disabled"){
            $this->session->set_flashdata('error',  _l('renovation_setting_in_enable'));
            redirect('/account');
        }
        
        $this->instant($major, $minor);
	}

    public function market($major = null, $minor = null) {
//        if (!$major) {
//            if (!$major = $this->session->userdata('major')) {
//                $major = $this->config->item('default_major');
//            }
//        }
//
//        if (!$minor) {
//            if (!$minor = $this->session->userdata('minor')) {
//                $minor = $this->config->item('default_minor');
//            }
//        }
//
//        $this->session->set_userdata(array(
//            'major' => $major,
//            'minor' => $minor
//        ));

        $major = $this->config->item('default_major');
        $minor = $this->config->item('default_minor');

        $book = $major . '_' . $minor;

        // Just making sure we are looking at a book that exists
        if (!isset($this->meta_model->books[$book])) show_404();

        $this->load->model('order_model');

        $data['minor'] = $major;
        $data['major'] = $minor;

        $data['trades'] = $this->trade_model->getTrades($major . '_' . $minor);
        $data['sell']   = $this->order_model->getOrders('orders:sell:' . $major . '_' . $minor);
        $data['buy']    = $this->order_model->getOrders('orders:buy:' . $major . '_' . $minor);

        $data['books']  = $this->meta_model->books;
        $data['header'] = makeSafeCurrency(strtoupper($minor . '/' . $major));
        $data['active'] = $book;

        $this->layout->setTitle(_l('heading_market_trade'))->view('trade/market', $data);
    }

    public function orderbook($major = null, $minor = null) {
//        if (!$major) {
//            if (!$major = $this->session->userdata('major')) {
//                $major = $this->config->item('default_major');
//            }
//        }
//
//        if (!$minor) {
//            if (!$minor = $this->session->userdata('minor')) {
//                $minor = $this->config->item('default_minor');
//            }
//        }
//
//        $this->session->set_userdata(array(
//            'major' => $major,
//            'minor' => $minor
//        ));

        $major = $this->config->item('default_major');
        $minor = $this->config->item('default_minor');

        $book = $major . '_' . $minor;

        $books = $this->meta_model->books;

        // Just making sure we are looking at a book that exists
        if (!isset($books[$book]))
            show_404();

        $data['minor'] = $major;
        $data['major'] = $minor;

        $data['trades'] = $this->trade_model->getTrades($major . '_' . $minor, 100);
        $data['sell']   = $this->order_model->getOrders('orders:sell:' . $major . '_' . $minor);
        $data['buy']    = $this->order_model->getOrders('orders:buy:' . $major . '_' . $minor);

        $data['books']  = $books;
        $data['book']   = $book;
        $data['header'] = makeSafeCurrency(strtoupper($minor . '/' . $major));
        $data['active'] = $book;

        $this->layout->setTitle(_l('heading_order_book'))->view('trade/orderbook', $data);
    }

    public function dash($major = null, $minor = null) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error',  _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }
        
        //$this->bitcoin_model->bitcoinAutoWithdrawals();
        //$this->bitcoin_model->checkBitcoind();

        $data['currencies'] = $this->meta_model->getAllCurrencies();
        $data['user']       = $this->user;
        $data['balances']   = $this->user_balance_model->get($this->userId);
        
        $data['commission'] = bcmul($this->trade_model->getTradeFee($this->userId), 100, 2);
        $data['bitcoin_deposit'] = $this->bitcoin_model->getBitcoinDepositConst();

        $this->load->model(array('deposit_model', 'withdrawal_model'));

        $this->deposit_model->getCountForUser($this->userId, 'pending');
        $this->withdrawal_model->getCountForUser($this->userId, 'pending');

        $deposits    = $this->deposit_model->getSubsetForUser(1, 10);
        $withdrawals = $this->withdrawal_model->getSubsetForUser(1, 10);
        
        //print_r($deposits);

        $transactions = array();
        foreach ($deposits as $deposit) {
            if(!property_exists($deposit->details, 'confirmations') || $deposit->details->confirmations == 0) {
                $confirmations = "zero";
            } else if($deposit->details->confirmations > 0 && 
                        $deposit->details->confirmations <= $data['bitcoin_deposit']) {
                $confirmations = $deposit->details->confirmations;
                switch($confirmations) {
                    case 1:
                        $confirmations = "twentyfive";
                        break;
                    case 2:
                        $confirmations = "fiftyper";
                        break;
                    case 3:
                        $confirmations = "seventyfive";
                        break;
                    default:
                        $confirmations = "onehundred";
                        break;
                }
            } else {
                $confirmations = "onehundred";
            }
            $transactions[$deposit->_created] = array(
                'type'     => 'deposit',
                'amount'   => $deposit->amount,
                'currency' => $deposit->currency,
                'confirmations' => $confirmations,
                'transactionid' => $deposit->details->trabsactionid
            );
        }

        foreach ($withdrawals as $withdrawal) {
            if(!property_exists($withdrawal->details, 'confirmations') || $withdrawal->details->confirmations == 0) {
                $confirmations = "zero";
            } else if($withdrawal->details->confirmations > 0 && 
                        $withdrawal->details->confirmations <= $data['bitcoin_deposit']) {
                $confirmations = $withdrawal->details->confirmations;
                switch($confirmations) {
                    case 1:
                        $confirmations = "twentyfive";
                        break;
                    case 2:
                        $confirmations = "fifty";
                        break;
                    case 3:
                        $confirmations = "seventyfive";
                        break;
                    default:
                        $confirmations = "onehundred";
                        break;
                }
            } else {
                $confirmations = "onehundred";
            }
            
            $transactions[$withdrawal->_created] = array(
                'type'     => 'withdrawal',
                'amount'   => $withdrawal->amount,
                'currency' => $withdrawal->currency,
                'confirmations' => $confirmations,
            );
        }
        
        $books = $this->meta_model->getBooks();

        $args = func_get_args();
        if (!$args) {
            // Check the route
            $uriString = $this->router->uri->uri_string;
            foreach ($books as $bookId=>$currencies) {
                if ((strpos($uriString, $currencies[0]) !== FALSE && strpos($uriString, $currencies[1]) !== FALSE) || (strpos($uriString, $bookId) !== FALSE)) {
                    $book = $bookId;
                    break;
                }
            }

            if (!isset($book)) {
                if (!$major = $this->session->userdata('major')) {
                    $major = $this->config->item('default_major');
                }

                if (!$minor = $this->session->userdata('minor')) {
                    $minor = $this->config->item('default_minor');
                }

                $book = $major . '_' . $minor;
            }
        }
        else $book = $args[0];
        
        $this->load->helper('date_helper');

        $data['volumeTotal'] = $this->trade_model->getUserVolume($this->userId, round((now() - ($this->user->_created / 1000)) / (24 * 3600)));
        
        

        $data['transactions'] = $transactions;
        $data['volume']       = $this->trade_model->getUserVolume($this->userId);

        $this->layout->setTitle(_l('menu_dashboard'))->view('trade/dash', $data);
    }

    public function instant($major = null, $minor = null) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error',  _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

//        if (!$major) {
//            if (!$major = $this->session->userdata('major')) {
//                $major = $this->config->item('default_major');
//            }
//        }
//
//        if (!$minor) {
//            if (!$minor = $this->session->userdata('minor')) {
//                $minor = $this->config->item('default_minor');
//            }
//        }
//
//        $this->session->set_userdata(array(
//            'major' => $major,
//            'minor' => $minor
//        ));

        $major = $this->config->item('default_major');
        $minor = $this->config->item('default_minor');

        $book  = $major . '_' . $minor;
        $books = $this->meta_model->books;

        if (!isset($books[$book])) show_404();

        $data['book']  = $book;
        $data['books'] = $books;
        $data['major'] = $major;
        $data['minor'] = $minor;

        $data['orders']     = $this->order_model->getForUser($this->userId, null, 9);
        $data['balances'] = $this->user_balance_model->get($this->userId);

        $data['sell']  = $this->order_model->getOrders('orders:sell:' . $major . '_' . $minor, 10);
        $data['buy']   = $this->order_model->getOrders('orders:buy:' . $major . '_' . $minor, 10);

        $this->layout->setTitle('Instant Trading')->view('trade/instant', $data);
    }

    public function limit($major = null, $minor = null) {
        if ($this->user === 'guest') {
            $this->session->set_flashdata('error',  _l('need_to_be_logged_in'));
            redirect('/login?redirect=/' . $this->router->uri->uri_string);
        }

//        if (!$major) {
//            if (!$major = $this->session->userdata('major')) {
//                $major = $this->config->item('default_major');
//            }
//        }
//
//        if (!$minor) {
//            if (!$minor = $this->session->userdata('minor')) {
//                $minor = $this->config->item('default_minor');
//            }
//        }
//
//        $this->session->set_userdata(array(
//            'major' => $major,
//            'minor' => $minor
//        ));

        $major = $this->config->item('default_major');
        $minor = $this->config->item('default_minor');

        $book  = $major . '_' . $minor;
        $books = $this->meta_model->books;

        if (!isset($books[$book])) show_404();

        $data['book']  = $book;
        $data['books'] = $books;
        $data['major'] = $major;
        $data['minor'] = $minor;

        $data['orders']     = $this->order_model->getForUser($this->userId, null, 9);
        $data['balances'] = $this->user_balance_model->get($this->userId);

        $data['sell']  = $this->order_model->getOrders('orders:sell:' . $major . '_' . $minor, 10);
        $data['buy']   = $this->order_model->getOrders('orders:buy:' . $major . '_' . $minor, 10);

        $this->layout->setTitle('Limit Trading')->view('trade/limit', $data);
    }

    public function get_rate() {
        $book      = $this->input->get('book');
        $direction = $this->input->get('direction');
        $amount    = $this->input->get('amount');

        header('Content-Type: application/json');

        if ($this->user === 'guest') {
            echo json_encode(array('error' => 'login'));
            return;
        }

        $result = $this->trade_model->getFillPrice($book, $direction, $amount, $this->userId);
        echo json_encode($result);
    }
}