<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Firebase_token {

    public static function getFirebaseTokenObject($iSecretToken){
        return new Firebase\Token\TokenGenerator($iSecretToken);
    }

}
