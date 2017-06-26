<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');
use \Firebase\JWT\JWT;
class Rest_user_model extends Redis_model {

    public $_id;
    public $_created  = 0;
    public $_lastseen = 0;
    public $active    = 0;
    public $verified  = 0;
    public $verify_complete_a  = 0;
    public $verify_complete_b  = 0;
    public $jumio     = 0;
    public $email;
    public $first_name;
    public $last_name;
    public $dob;
    public $country;
    public $language;
    public $password;
    public $salt;
    public $pin;
    public $register_ip;
    public $balances;
    public $twofa_status = 0;
    public $twofa_secret = '';
    public $twofa_reset  = '';
    public $suspended    = 0;

    private $ip;

    private $entries;

    public function __construct() {
        parent::__construct();
        $this->load->model('event_model');
        $this->load->model('user_balance_model');
        $this->ip = getIp();
    }

    public function save($data, $userId = null, $phase = null) {
        if ($userId) {
            $this->get($userId);

            if ($this->_id === null)
                return FALSE;
        }

        // If we try to save a new email to the database, check it does not already exist
        if ($userId && isset ($data['email']) && $this->email != strtolower($data['email'])) {
            if (!$this->uniqueEmail($data['email']))
                return false;
            else {
                $this->redis->srem('user:emails', $this->email);
                $this->redis->sadd('user:emails', strtolower($data['email']));

                $this->redis->srem('user:lookup', strtolower(_numeric($this->_id) . ':' . $this->first_name . '.' . $this->last_name . '.' . $this->email));
                $this->redis->sadd('user:lookup', strtolower(_numeric($this->_id) . ':' . $this->first_name . '.' . $this->last_name . '.' . $data['email']));
            }
        }

        foreach ($data as $k => $v)
            $this->{$k} = $v;

        // Lowercase the email no matter what
        $this->email = strtolower($this->email);

        if ($this->_id === null) {
            if (!$this->uniqueEmail($this->email))
                return false;

            $this->_id         = $this->newId();
            $this->_created    = $this->now;
            $this->register_ip = $this->ip;
        }

        if ($phase === 2) {
            // Saving the data on Phase 2 of the registration

            // Setting the empty balances
            $userId = _numeric($this->_id);
            $this->user_balance_model->init($userId);

            // Saves the list of all users
            $this->redis->sadd('user:ids', $this->_id);

            // Saves the data for lookup
            $this->redis->sadd('user:lookup', strtolower(_numeric($this->_id) . ':' . $this->first_name . '.' . $this->last_name . '.' . $this->email));

            // Adding the email address to the pool of used emails
            $this->redis->sadd('user:emails', $this->email);
            $this->redis->persist($this->_id);
        }

        $u = get_object_vars($this);
        unset($u['id'], $u['_error'], $u['now'], $u['balances'], $u['ip'], $u['entries']); // Don't need them things here

        $this->redis->hmset($this->_id, $u);

        if ($phase == 1) {
            // Set an expiry time on the key just in case the user does not complete the registration
            $this->redis->expire($this->_id, 3600);
        }

        $this->caching_model->delete('members:*');

        return _numeric($this->_id);
    }

    public function get($id) {
        $data = $this->flatten($this->redis->hgetall('user:' . $id));
        if (!$data)
            return false;

        foreach ((array)$data as $key=>$value)
            $this->{$key} = $value;

        $this->_id = $id;

        return $this;
    }

    public function uniqueEmail($email) {
        return $this->redis->sismember('user:emails', strtolower($email)) == "0";
    }

    public function newId() {
        return parent::newRandomId('user');
    }

    public function setLoginHash($timestamp) {
        $key     = 'login:hash:' . $timestamp;
        $hash    = $this->redis->get($key);
        if (!$hash) {
            $hash = generateRandomString(20);
            $this->redis->set($key, $hash);
            $this->redis->expire($key, 120);
        }

        return $hash;
    }

    public function getLoginHash($timestamp) {
        $key = 'login:hash:' . $timestamp;
        return $this->redis->get($key);
    }

    public function setLoginFailed($clientId) {
        $key = 'user:' . $clientId . ':' . $this->ip . ':login_failed';
        $tries = $this->redis->incr($key);
        $this->redis->expire($key, $this->config->item('login_fail_timeout'));
        // Log login failure event
        $this->event_model->add($clientId,'loginfail');
        return $tries;
    }

    public function getLoginFailed($clientId) {
        return $this->redis->get('user:' . $clientId . ':' . $this->ip . ':login_failed');
    }

    public function clearLoginFailed($clientId) {
        return $this->redis->del('user:' . $clientId . ':' . $this->ip . ':login_failed');
    }

    /* Function to log the user in and hence persist his session data across the firebase system.
     * @param $clientId integer
     * @param $password string
     * @param $error integer - has the following values
     *  1. Username/Password incorrect
     *  2. Too many login attempts
     *  3. Account suspended
     *  10. Success
     * Return the following codes based on the response received from the
     *        firebase system.
     *  @return body
     *  @retunr status_code , having values

     * */
     public function login($clientId, $password, &$code) {
        $code = 0;

         if((intval($clientId)) === 0 ){
             if(filter_var($clientId, FILTER_VALIDATE_EMAIL))
             {
                 $this->getUserByEmail($clientId);
             }
             else
             {
                 $code = 4;
                 return false;
             }
         }
         else
         {
             $this->get($clientId);
         }

        if ($this->_id === null || $this->active == 0) {
            $code = 1;
            return false;
        }

        if ($this->getLoginFailed($this->_id) >= $this->config->item('login_attempts')) {
            $code = 2;
            return false;
        }

        $clientPasswordHash = $this->api_security->hash($this->_id, trim($password));
        // Check the password hashes are the same
        $passwordHash = $this->api_security->hash($clientPasswordHash, $this->salt);
         if ($passwordHash != $this->password) {
            $this->setLoginFailed($this->_id);
            $code = 1;
            return false;
        }

        if ((int)$this->suspended > 0) {
            $code = 3;
            return false;
        }

         $code = 10;

        // All clear, remove the login failed counter
        $this->clearLoginFailed($this->_id);

        // Log successful login
        $this->event_model->add($this->_id,'login');

        return $this;
    }

    /* Function to log the user out.
     * @param $iUserID integer     *
     *  1. Firebase service unreachable
     *  10. Success
     * Return the following codes based on the response received from the
     *
     * */
    public function logout($iUserID)
    {  printf("inside rest user model");
        var_dump($iUserID);
        if(empty($this->firebase_auth_model->clearUserSession($iUserID) ) === false)
        {
            return 10;
        }
        else return 1;
    }

    public function saveSession($user) {
        if ($this->ip != $user->ip) {
            // Log IP change event - disabling this for now. Alex - dont 'whack' this.
            //$this->event_model->add($user->id,'ipchange');
        }
        $sessionId = $this->api_security->hash(mt_rand());

        $data = array(
            'client' => _numeric($user->_id),
            'ip'     => $this->ip,
            'ua'     => $this->input->user_agent(),
            'time'   => $this->now,
            'status' => $user->twofa_status === '0' ? 'logged_in' : 'authenticated',
            'verification_level' => $this->getUserVerificationLevel($user),
            'approval_level' => $this->getAdminAprovalLevel($user)
        );
        $this->redis->hmset('session:' . $sessionId, $data);
        $this->redis->expire('session:' . $sessionId, 3600); // 1 hour ttl

        $data['_id']   = $sessionId;
        $data['_token'] = $this->firebase_auth_model->genrateAuthToken($user->_id);
        $data['_accessToken'] = $this->firebase_auth_model->genrateAccessToken($user->_id);


        $this->firebase_auth_model->setUserSessionData($user->_id, $data);
        unset($data['_token']);
        unset($data['_accessToken']);
        return $data;
    }

    public function getUserSessionData($iUserID)
    {
        return $this->firebase_auth_model->getUserSessionData($iUserID);
    }

    public function getUserVerificationLevel($user)
    {
        if ((intval($this->redis->llen('user:' . $user->_id . ':documents')) >= 3) && (intval(intval($user->verify_complete_a) + intval($user->verify_complete_b))) == 2) {
            return 3;
        }
        else if (intval($this->redis->llen('user:' . $user->_id . ':documents')) === 0) {
            return 0;
        } else if (intval($this->redis->llen('user:' . $user->_id . ':documents')) === 1) {
            return 0;
        } else if (intval($this->redis->llen('user:' . $user->_id . ':documents')) === 2) {
            return 1;
        } else if (intval($this->redis->llen('user:' . $user->_id . ':documents')) >= 3) {
            return 2;
        }
    }

    public function getAdminAprovalLevel($user){
        if ($user->verify_complete_b) {
            return 2;
        }
        else if ($user->verify_complete_a) {
            return 1;
        }
        else if (isset($user->isVerifyEmail) && $user->isVerifyEmail) {
            return 0;
        }else{
            return -1;
        }
    }

    public function loadFromSession($obj_Session) {

        $userId = $obj_Session->client;

        $this->get($userId);
        if ($this->_id === null) {
            $this->clearSession();
            return 'guest';
        }

        $ip = $this->ip;
        if ($ip !== $obj_Session->ip || $this->input->user_agent() !== $obj_Session->ua) {
            $this->clearSession();
            return 'guest';
        }

        $this->balances = $this->user_balance_model->get($userId);

        //$this->updateSession($sessionId, $userId);

        $this->_status = $obj_Session->status;

        return $this;

    }

    public function clearSession($clientId=null) {


        // Log successful login
        if (!is_null($clientId)) {
            $this->event_model->add($clientId,'logout');
        }
    }

    public function authenticateSession() {
        $cookie = $this->input->cookie('taurus_session');

        if ($cookie) {
            $session   = json_decode($cookie);
            $sessionId = $session->_id;

            $data = $this->redis->hgetall('session:' . $sessionId);

            if (!$data) {
                $this->clearSession();
                return false;
            }

            $sessObj = $this->flatten($data);

            $userId = $sessObj->client;

            $this->get($userId);
            if ($this->_id === null) {
                $this->clearSession();
                return false;
            }

            $this->redis->hset('session:' . $sessionId, 'status', 'logged_in');

            $this->updateSession($sessionId, $userId);

            return true;
        }

        return false;
    }

    private function updateSession($sessionId, $userId) {
        $this->redis->hset('session:' . $sessionId, 'time', $this->now);
        $this->redis->hset('user:' . $userId, '_lastseen', $this->now);

        $this->redis->expire('session:' . $sessionId, 3600); // refresh 1 hour ttl
    }

    public function getDetails($userId) {
        return $this->flatten($this->redis->hgetall('user:' . $userId . ':details'));
    }

    public function setLanguage($lang) {
        $this->language = $lang;
        $this->save(array('language' => $lang));
    }

    // New functions
    public function userExists($userId) {
        return $this->redis->exists('user:' . $userId);
    }

    public function getUser($userId, $simple = true) {
        $data = $this->flatten($this->redis->hgetall('user:' . $userId));

        if (!$data)
            return false;

        if (!$simple) {
            $data->balances = $this->user_balance_model->get($userId);
            $data->details = $this->flatten($this->redis->hgetall('user:' . $userId . ':details'));
        }

        $data->id = _numeric($data->_id);

        return $data;
    }
    public function getUserBalance($userId) { 
        $data = new stdClass();
        $data->balances = $this->user_balance_model->get($userId); 
        $data->id = _numeric($data->_id);

        return $data;
    }
    public function getUserWithBalances($userId) {
        $data = $this->flatten($this->redis->hgetall('user:' . $userId));
        $data->balances = $this->user_balance_model->get($userId); 
        return $data;
    }

    public function getCount($filter = 'all') {
        // In this function we cache only the user's data but not the balances because it can refresh at anytime
        // The balance retrieval is performed in the getSubset to save resources
        $this->entries = $this->caching_model->get('members:' . $filter);
        if (!$this->entries) {
            $this->entries = array();

            if ($filter != 'all') {
                $lookupEntries = $this->redis->smembers('user:lookup');

                foreach ($lookupEntries as $lookup) {
                    if (strpos($lookup, strtolower($filter)) !== FALSE) {
                        list($userId, $dummy) = explode(':', $lookup);

                        $data = $this->getUser($userId);
                        $this->entries[] = $data;

                        usort($this->entries, function($a, $b){
                            return $a->_created < $b->_created;
                        });
                    }
                }
            }
            else {
                $entries = $this->redis->sort('user:ids', array('BY' => '*->_created', 'sort' => 'DESC'));

                if (!$entries)
                    return 0;

                foreach ($entries as $entryId) {
                    $_entryId = _numeric($entryId);
                    $data     = $this->getUser($_entryId);

                    if ($filter != 'all') {
                        $concat = strtolower($data->first_name . '.' . $data->last_name . '.' . $data->email);

                        if (strpos($concat, strtolower($filter)) !== FALSE)
                            $this->entries[] = $data;
                    }
                    else $this->entries[] = $data;
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

            $data = $this->entries[$i];
            $data->balances = $this->user_balance_model->get($data->id);

            $result[] = $data;
        }

        return $result;
    }

    public function getCountOnly() {
        return $this->redis->scard('user:ids');
    }

    public function update($userId, $data) {
       // $this->createUserLookup(); // doesn't hurt to rebuild the lookup in full

        $this->caching_model->delete('members:*');

        return $this->redis->hmset('user:' . $userId, $data);
    }

    public function updatePin($userId, $data) {
        // Removed create lookup for reducing response time
        $this->caching_model->delete('members:*');

        return $this->redis->hmset('user:' . $userId, $data);
    }

    public function updateDetails($userId, $data) {
        return $this->redis->hmset('user:' . $userId . ':details', $data);
    }

    public function autoCompleteList($filter) {
        $lookupEntries = $this->redis->smembers('user:lookup');

        $entries = array();

        foreach ($lookupEntries as $lookup) {
            if (strpos($lookup, strtolower($filter)) !== FALSE) {
                list($userId, $dummy) = explode(':', $lookup);

                $user = $this->getUser($userId);

                $data = array(
                    'id'    => $userId,
                    'value' => $user->first_name . ' ' . $user->last_name
                );

                $entries[] = $data;
            }
        }

        usort($entries, function($a, $b){
            return strcmp($a['value'], $b['value']);
        });

        echo json_encode($entries);
    }

    public function createUserLookup() {
        $this->redis->del('user:lookup');

        $keys = $this->redis->keys('user:*:balances');

        foreach ($keys as $key) {
            $key = str_replace(':balances', '', $key);

            $user = $this->flatten($this->redis->hgetall($key));
            if ($user) {
                $_key = strtolower(_numeric($user->_id) . ':' . $user->first_name . '.' . $user->last_name . '.' . $user->email);

                $this->redis->sadd('user:lookup', $_key);
            }
        }
    }

    public function isPINLockedOut($userId) {
        $key = 'user:' . $userId . ':locked:pin';

        if ($this->redis->exists($key)) {
            if ((int)$this->redis->get($key) > 9)
                return $this->redis->ttl($key);
        }

        return false;
    }

    public function increasePINLockedOut($userId) {
        $key = 'user:' . $userId . ':locked:pin';

        $this->redis->incr($key);
        $this->redis->expire($key, 300); // 5 minutes
    }

    /* ONE OFF */
    public function emailUsersWithoutDeposits() {
        $usersWithoutDeposits = array();
        $usersWithDeposits    = array();

        // Get all the pending deposits
        $deposits = $this->redis->smembers('deposits:pending');

        foreach ($deposits as $depositId) {
            $deposit = $this->flatten($this->redis->hgetall($depositId));
            if ($deposit) {
                $userId  = $deposit->client;

                if (in_array($userId, $usersWithDeposits) === false)
                    $usersWithDeposits[] = $userId;
            }
        }

        $allUsers = $this->redis->smembers('user:ids');

        foreach ($allUsers as $userId) {
            $userId = _numeric($userId);

            if (in_array($userId, $usersWithDeposits) === false) {
                if (!$this->redis->exists('user:' . $userId . ':deposits')) {
                    $usersWithoutDeposits[] = $userId;
                }
            }
        }

        $date = $this->redis->get('email:bonus');
        if (!$date)
            $date = mktime(0, 0, 0, 8, 4, 2014);

        // Now we got the list of users who have never deposited, get their details
        foreach ($usersWithoutDeposits as $userId) {
            $user = $this->getUser($userId);

            if ($user->_created / 1000 < $date)
                continue;

            // Generate the voucher
            $code = random_string('alnum', 16);

            $voucherData = array(
                'value'    => '5',
                'currency' => 'cad',
                '_created' => $this->now,
                '_updated' => $this->now,
                'client'   => '',
                'referrer' => ''
            );

            $this->redis->hmset('voucher:' . $code, $voucherData);
            $this->redis->expire('voucher:' . $code, 30 * 3600 * 24); // 30 days expiry

            $emailData = array(
                'id'      => $userId,
                'name'    => $user->first_name . ' ' . $user->last_name,
                'email'   => $user->email,
                'voucher' => $code
            );

            $this->email_queue_model->email   = $user->email;
            $this->email_queue_model->message = $this->layout->partialView('emails/free_5cad', $emailData);
            $this->email_queue_model->subject = $this->config->item('site_full_name') . ' - Free $5 to get you started!';

            $this->email_queue_model->store();
        }

        $this->redis->set('email:bonus', $this->now);
    }

    /* ONE OFF */
    public function emailUsersWithoutDepositsBITSO() {
        $usersWithoutDeposits = array();
        $usersWithDeposits    = array();

        // Get all the pending deposits
        $deposits = $this->redis->smembers('deposits:pending');

        foreach ($deposits as $depositId) {
            $deposit = $this->flatten($this->redis->hgetall($depositId));
            if ($deposit) {
                $userId  = $deposit->client;

                if (in_array($userId, $usersWithDeposits) === false)
                    $usersWithDeposits[] = $userId;
            }
        }

        $allUsers = $this->redis->smembers('user:ids');

        foreach ($allUsers as $userId) {
            $userId = _numeric($userId);

            if (in_array($userId, $usersWithDeposits) === false) {
                if (!$this->redis->exists('user:' . $userId . ':deposits')) {
                    $usersWithoutDeposits[] = $userId;
                }
            }
        }

        $date = $this->redis->get('email:bonus');
        if (!$date)
            $date = mktime(0, 0, 0, 1, 1, 2013);

        $mostrecent = mktime(0, 0, 0, 10, 9, 2014);

        //$usersWithoutDeposits = array(9,17);  // TEST

        $this->load->model('logging_model');
        $this->logging_model->log($usersWithoutDeposits,'promoemail');

        // Now we got the list of users who have never deposited, get their details
        foreach ($usersWithoutDeposits as $userId) {
            $user = $this->getUser($userId);

            if ($user->_created / 1000 < $date)
                continue;

            if ($user->_created / 1000 > $mostrecent)
                continue;

            if (in_array($userId,array(25,82,84,161,233,432,3141,1100,5932,1814,1965))) continue; // PEOPLE WE DONT LIKE

            // Generate the voucher
            $code = random_string('alnum', 16);

            $voucherData = array(
                'value'    => '40',
                'currency' => 'mxn',
                '_created' => $this->now,
                '_updated' => $this->now,
                'client'   => '',
                'referrer' => ''
            );

            $this->redis->hmset('voucher:' . $code, $voucherData);
            $this->redis->expire('voucher:' . $code, 30 * 3600 * 24); // 30 days expiry

            $emailData = array(
                'id'      => $userId,
                'name'    => $user->first_name,// . ' ' . $user->last_name,
                'email'   => $user->email,
                'voucher' => $code
            );


            if (empty($user->language)) $lang = 'es';
            else $lang = $user->language;


            $this->email_queue_model->email   = $user->email;
            $this->email_queue_model->message = $this->layout->partialView('emails/free_credit_'.$lang, $emailData);
            switch ($lang) {
                case 'en':
                    $this->email_queue_model->subject = $this->config->item('site_full_name') . ' - Free $75 MXN to get you started!';
                    break;
                default:
                    $this->email_queue_model->subject = $this->config->item('site_full_name') . ' - Te regalamos un total de $75 pesos (MXN) en cupones';
            }
            $this->email_queue_model->store();


            echo 'Sending to user: '.$userId.' lang '.$lang.' <br/>';

        }

        $this->redis->set('email:bonus', $this->now);
    }

    public function findUserByEmail($email) {
        $lookupEntries = $this->redis->smembers('user:lookup');

        foreach ($lookupEntries as $lookup) {
            if (strpos($lookup, strtolower($email)) !== FALSE) {
                list($userId, $dummy) = explode(':', $lookup);

                return $this->getUser($userId);
            }
        }

        return false;
    }

    public function getUserByEmail($email) {
        $lookupEntries = $this->redis->smembers('user:lookup');

        foreach ($lookupEntries as $lookup) {
            if (strpos($lookup, strtolower($email)) !== FALSE) {
                list($userId, $dummy) = explode(':', $lookup);
                $this->get($userId);
            }
        }
    }
    public function resetPassword($data) {
        $clientId = $data['id'];
        if((intval($clientId)) === 0 ){
            if(filter_var($clientId, FILTER_VALIDATE_EMAIL))
            {
                $this->getUserByEmail($clientId);
            }
            else
            {
                return false;
            }
        }
        else
        {
            if($this->get($clientId) == false){
                return false;
            }
        }

//        if ($this->dob == $data['dob'] &&
//            $this->country == $data['country']) {
            // Create a request code here
        $code =  generateRandomString(16, true);
        $encoded = JWT::encode($code,DEFAULT_TOKEN, JWT_ALGORITHM);

            $data = array(
                'client' => $this->_id,
                'name'   => $this->first_name . ' ' . $this->last_name,
                'ip'     => getIp(),
                'link'   => clent_url_https('/#/reminder/complete?q='. $encoded),
                'date'   => $this->now,
                'email'  => $this->email
            );
            $this->redis->hmset('reset:request:' . $code, $data);
            $this->redis->expire('reset:request:' . $code, 1800); // 30 minutes expiry time
            return $data;
//        }
//
//        return false;
    }

    public function checkResetPasswordCode($code) {
        if ($this->redis->exists('reset:request:' . $code))
            return $this->flatten($this->redis->hgetall('reset:request:' . $code));

        return false;
    }

    public function changePasswordAfterReset($code, $data) {
        $requestData = $this->flatten($this->redis->hgetall('reset:request:' . $code));
        if ($requestData) {
            $userId = $requestData->client;

            $this->redis->hmset('user:' . $userId, $data);

            $this->redis->del('reset:request:' . $code);

            // Log successful login
            $this->event_model->add($userId,'pwchange');

            return true;
        }

        return false;
    }

    public function getProperty($userId, $property) {
        return $this->redis->hget('user:' . _numeric($userId), $property);
    }

    public function setProperty($userId, $property, $value) {
        return $this->redis->hset('user:' . _numeric($userId), $property, $value);
    }
    /* Function to checck Google Authentication code.
     * @param $code integer
     * @param $secret string
     * @param $error integer - has the following values
     *  1. Code is incorrect
     *  10. Success
     * */
    public function validateTwoFACode($code, $secret, &$error) {
        $this->load->helper('GoogleAuthenticator');
        $ga = new PHPGangsta_GoogleAuthenticator();
        if (!$ga->verifyCode($secret, $code)) {
            $error = 1;
        }else{
            $error = 10;
        }
        return;
    }
    public function register($data) {
        foreach ($data as $k => $v)
            $this->{$k} = $v;

        // Lowercase the email no matter what
        $this->email = strtolower($this->email);
        $this->_id         = $this->newId();
        $iClientID = explode(":",$this->_id);
        $sPasswordHash = $this->api_security->hash($iClientID[1], $this->password);        
        $this->password = $this->api_security->hash($sPasswordHash, $this->salt);
        $this->_created    = $this->now;
        $this->register_ip = $this->ip;
        // Setting the empty balances
        $userId = _numeric($this->_id);
        $this->user_balance_model->init($userId);

        // Saves the list of all users
        $this->redis->sadd('user:ids', $this->_id);

        // Saves the data for lookup 
        $this->redis->sadd('user:lookup', strtolower(_numeric($this->_id) . ':' . $this->first_name . '.' . $this->last_name . '.' . $this->email));
        
        // Adding the email address to the pool of used emails
        $this->redis->sadd('user:emails', $this->email);
        $this->redis->persist($this->_id);

        $u = get_object_vars($this);
        unset($u['id'], $u['_error'], $u['now'], $u['balances'], $u['ip'], $u['entries']); // Don't need them things here

        $this->redis->hmset($this->_id, $u); 

        $this->caching_model->delete('members:*');

        return _numeric($this->_id);
    }
    function clearProtectimusGarbage() {
        $this->load->library('Protectimus');
        $api = $this->protectimus->getApi();
        try {
            $response = $api->getTokensQuantity();
            $response = $api->getTokens(0, $response->response->quantity);
            if (count($response->response->tokens->token) == 1)
                $tokens = $response->response->tokens;
            else
                $tokens = $response->response->tokens->token;
            if(!empty($tokens)) {
                foreach ($tokens as $token) {
                    if (!empty($token->serialNumber)) {
                        $user = $this->getUserByEmail($token->serialNumber);
                        if (empty($this->token_id) || $this->token_id != $token->id)
                            $api->deleteToken($token->id);
                    }
                }
            }
        }catch(Exception\ProtectimusApiException $e) {
            return;
        }
        catch(Exception $e){
            return;
        }
        return;
    }

    public function setPhone(){


    }
}