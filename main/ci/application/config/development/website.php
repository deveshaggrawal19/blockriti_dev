<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// This file will contain the main website details - variables that are the most likely to be changed without being translated (hope that makes sense)
$config['site_name']        = 'blockriti';
$config['site_full_name']   = 'blockriti Bitcoin Exchange';
$config['site_domain']      = 'blockriti.appspot.com';
$config['site_url']         = 'blockriti.appspot.com';
$config['admin_email']      = 'shrikant@blockriti.com';
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

$config['admin_emails'] = array('hanin.alexandre@gmail.com','nachiket.kulkarni@blockriti.com');

$config['deposit_methods']    = array('cadio', 'cadbw', 'cadip', 'cadpz', 'btcbtc');
$config['withdrawal_methods'] = array('cadbw', 'cadpz', 'btcbtc');

$config['login_attempts']     = 25;
$config['login_fail_timeout'] = 900; // seconds

/* Can this go anywhere? */
$config['currencies'] = array(
    'btc' => array(
        'name'      => 'Bitcoin',
        'display'   => 'BTC',
        'precision' => 8,
        'format'    => '%s<span class="e">BTC</span>'
    ),
    'cad' => array(
        'name'      => 'Canadian Dollars',
        'display'   => 'CAD',
        'precision' => 2,
        'format'    => '$%s<span class="e">CAD</span>'
    ),
    'usd' => array(
        'name'      => 'US Dollars',
        'display'   => 'USD',
        'precision' => 2,
        'format'    => '$%s<span class="e">USD</span>'
    )
);

/*Google App Engine related Constant Values*/

$config['google_app_id'] = 'btcmonk-production';
