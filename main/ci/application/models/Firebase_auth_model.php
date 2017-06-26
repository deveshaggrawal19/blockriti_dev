<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Firebase_auth_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /* Function to verify the token with Firebase DB and hence fetch the user's session data
     * @param $sAuthToken string
     * @param $sUserID string
     * Return the following codes based on the response received from the
     *        firebase system.
     *  @return body
     *  @retunr status_code , having values
     *  1. AuthToken Empty
     *  2. UserID empty
     *  3. Session Expired
     *  4. Invalid Authtoken
     *  5. Internal server error
     *  6. Unknown error
     *  10. Success
     * */

    public function verifyToken($sAuthToken, $sUserID){
        $code = 0;
        $obj_UserData = null;
        $aReturnArray = array();
        if(empty($sAuthToken) === true ){
            $code = 1;
        }
        else if(empty($sUserID) === true ){
            $code = 2;
        }
        else
        {
            $aResposne = $this->firebase_lib->verifyAuthToken($sAuthToken, $sUserID);
            if(isset($aResposne['status']) === true && empty($aResposne['status']) === false ){
                $obj_Body = $aResposne['body'];
                switch($aResposne['status']) {
                    case 200 :
                        //Session data not found in Firebase. Implies session expired.
                        if(empty($obj_Body) === true){
                            $code = 3;
                        }
                        else if(empty($obj_Body) === false ){
                            if(isset($obj_Body['_token']) === true && trim($obj_Body['_token'] === trim($sAuthToken))) {
                                $code = 10;
                                $obj_UserData = $obj_Body;
                            }
                            else
                            {
                                $code = 3;
                            }
                        }
                        break;

                    case 401 :
                    case 400 :
                        if(count($obj_Body['error']) > 0 ){
                            $code = 4;
                            $obj_UserData = $obj_Body['error'];
                        }
                        break;

                    case 404:
                    case 500:
                        $code = 5;
                        break;

                    default:
                        $code = 6;
                        break;
                }

            }
        }
        return $aReturnArray = array('code' => $code,
                                     'body' => $obj_UserData);
    }

    public function genrateAuthToken($sUserID)
    {
        return $this->firebase_lib->getAuthToken($sUserID);
    }

    public function genrateAccessToken($sUserID)
    {
        return $this->firebase_lib->getAccessToken($sUserID);
    }

    public function setUserSessionData($iUserID, $data)
    {
        return $this->firebase_lib->setData($iUserID, $data);
    }

    public function getUserSessionData($iUserID)
    {
        return  $this->firebase_lib->getUserSessionObject($iUserID);
    }

    public function clearUserSession($iUserID)
    {   var_dump($iUserID);
        return $this->firebase_lib->clearUserSession($iUserID);
    }

}