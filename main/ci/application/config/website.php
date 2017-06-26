<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// This file will contain the main website details - variables that are the most likely to be changed without being translated (hope that makes sense)
$config['site_name']        = 'blockriti';
$config['site_full_name']   = 'blockriti Bitcoin Exchange';
$config['site_domain']      = 'blockriti.appspot.com';
$config['site_url']         = 'blockriti.appspot.com';
$config['admin_email']      = 'devesh@blockriti.com';
$config['contact_email']    = 'contact@blockriti.com';
$config['support_email_en'] = 'support@blockriti.com';
$config['system_email']     = true;

$config['default_lang']    = "en";
$config['default_country'] = "CA";

$config['twitter']  = "blockriti";
$config['facebook'] = "blockriti";

$config['default_major'] = 'btc';
$config['default_minor'] = 'cad';
$config['default_book']  = 'btc_cad';

$config['default_redirect']  = '/account';

$config['admin_emails'] = array('');

$config['deposit_methods']    = array('cadio', 'cadbw', 'cadch', 'cadip', 'cadpz', 'cadca', 'btcbtc', 'btcba');
$config['withdrawal_methods'] = array('cadbw', 'cadpz', 'cadch', 'cadca', 'btcbtc', 'btcba');

$config['login_attempts']     = 25;
$config['login_fail_timeout'] = 900; // seconds

$config['bitcoin_deposit'] = 2; // 3 Confirm (0-2)

/*Google App Engine related Constant Values*/

$config['google_app_id'] = 'btcmonk-production';
