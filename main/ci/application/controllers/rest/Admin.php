<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once(APPPATH . 'controllers/Auth_Api.php');
define("SUPERADMIN", 0);
define("ADMIN", 1);
 
class Admin extends Auth_api
{
    private $allowedDeposits;
    private $allowedWithdrawals;
    public $access_level;

    public function __construct() {
        parent::__construct();
        $this->load->model('rest_user_model');
        //Load user from session.
        if(empty($this->sessionData) === false) {
            $this->user = $this->rest_user_model->loadFromSession($this->sessionData);
        }
        $allowed = $this->config->item('admin_emails');
        
        if ($this->user === 'guest' || (in_array($this->user->email, $allowed) === false && !$this->user->isadmin)) {
            show_404('', TRUE);
            return;
        } else {
            if(in_array($this->user->email, $allowed) === true) {
                $this->access_level = SUPERADMIN;
            } else {
                $this->access_level = ADMIN;
            }
        }

        $this->load->driver('cache', array('adapter' => 'file'));

        $this->load->model('admin_model');
        $this->load->model('bitcoin_model');
        $this->load->model('note_model');
        $this->load->model('deposit_model');
        $this->load->model('referral_model');
        $this->load->model('withdrawal_model');
        $this->load->model('user_document_model');
        $this->load->model('logging_model');
        $this->load->model('event_model');
        $this->load->model('meta_model');

        //todo what is tech mode and remove ben and hannin
        //todo conduct mass search for ben and hanin
        
        if (in_array($this->user->email, array('moe@taurusexchange.com'))) {
            $this->techmode = true;
            $this->logprefixes = $this->logging_model->getAllPrefixes();
        }
        $this->outstandingWithdrawals = $this->withdrawal_model->getCount('pending');
        $this->pendingDocuments       = $this->user_document_model->getPendingCount();

        $this->allowedDeposits    = $this->config->item('deposit_methods');
        $this->allowedWithdrawals = $this->config->item('withdrawal_methods');

        $this->meta_model->getAllCurrencies();
        $this->meta_model->getBooks();

    }

	public function dashboard() {
        $books = $this->meta_model->books;
        foreach ($books as $bookId=>$book) {
            $data['lastPrice'][$book[1]] = $this->trade_model->getLastTradePrice($bookId);
            $data['volume'][$book[1]]    = $this->trade_model->getRollingVolume($bookId);
        }
        $data['books']      = $books;
        $data['users']      = $this->user_model->getCountOnly();
        $data['pendingD']   = $this->deposit_model->getCount('pending');
        $data['pendingW']   = $this->withdrawal_model->getCount('pending');
        $data['balances']   = $this->user_balance_model->getTotalBalances();
        $data['currencies'] = $this->meta_model->currencies;
        $data['documents']  = $this->pendingDocuments;
        $data['tradeFees']   = $this->admin_model->getFees('trades');
        $data['depositFees'] = $this->admin_model->getAmounts('deposits');
        $data['withdrawalFees'] = $this->admin_model->getFees('withdrawals');
//        $this->load->library('BitGo');
//        $listAccounts = $this->bitgo->send("/get_wallet");
//        $data['withdrawals_account'] = $listAccounts;
        $this->_displaySuccess($data);
	}

    public function users($page = 1, $perPage = 30) { 
        $filter = $this->postData['search'];
        $count = $this->user_model->getCount($filter ? $filter : 'all');
        $data['count'] = $count;
        $data['users'] = $this->user_model->getSubset($page, $perPage);
        $currencies = $this->meta_model->currencies;
        $data['currencies'] = $currencies;
        $this->_displaySuccess($data);
    }

    public function userBalance($userId) {

        $count = $this->note_model->getCountNotesByUser($userId);
        if($count > 0) {
            $data['notesCount'] = $count;
        }

        $data['userId'] = $userId;
        $data['user']   = $this->rest_user_model->getUserBalance($userId);

        $this->_displaySuccess($data);
    }
}
