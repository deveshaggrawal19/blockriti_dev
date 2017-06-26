<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

define('ASSETS', '');
//TODO url for socket deployment
define('API_BASE',    '/engine/');
define('IO_BASE',     '/');
define('IO_PATH',     '/socket.io/');
define('MAIN_DOMAIN', 'blockriti-163505.appspot.com');

define('ONE_MINUTE', 60);
define('ONE_HOUR',   3600);
define('ONE_DAY',    3600 * 24);

define('UPLOADPATH', '..' . DIRECTORY_SEPARATOR . 'tempupload' . DIRECTORY_SEPARATOR);
define('DEFAULT_URL',    'https://blockriti-163505.firebaseio.com/');
define('DEFAULT_TOKEN',    'GWoeKLMo4QPjIqgYLxzvAykoCfN2rVtS4crhnruq');
//define('REFERRAL_DOMAIN','');
//define('DEFAULT_TOKEN','ROlRg3WTsuzZ6rNeMMC8TlmvdFnKmY4PCORcTkzf');
define('REFERRAL_DOMAIN','https://blockriti-163505.appspot.com');
define('FB_PUB_URL',' https://blockriti-163505.firebaseio.com/');
define('FB_PUB_TOKEN','AIzaSyCwI8ZiC0gybYhDM9Q0B4kgJWtIcQktAns');

define('SITE_URL','https://blockriti-163505.firebaseapp.com');

define('JWT_ALGORITHM','HS256');

/* End of file constants.php */
/* Location: ./application/config/constants.php */