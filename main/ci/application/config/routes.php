<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = 'main';
$route['404_override']       = 'main/error';

$route['home']                       = 'main/index';

$route['home/([a-z]{3})/([a-z]{3})'] = 'main/index/$1/$2';

$route['about']    = 'main/about';
$route['faq']      = 'main/faq';
$route['support']  = 'main/support';
$route['terms']    = 'main/terms';
$route['privacy']  = 'main/privacy';
$route['irba']     = 'main/irba'; //todo what is irba -riplle get rid
$route['fees']     = 'main/fees';
$route['refund']   = 'main/refund';
$route['contact']  = 'main/contact';
$route['intro']    = 'main/intro';
$route['api_info(.*)'] = 'main/api_info$1';
$route['local']    = 'main/local';
$route['atms']     = 'main/atms';
$route['help']     = 'main/help';
$route['news']     = 'main/news';
$route['verify_email/([a-zA-Z0-9.%-_]*)/([a-zA-Z0-9]*)'] = 'main/verify_email/$1/$2';

$route['account-funding-withdrawal'] = 'main/options';

$route['merchant_info']       = 'main/merchant_info';
$route['merchant_setup_info'] = 'main/merchant_setup';

$route['btm']    = 'main/btm';

$route['stats/([a-z_]{7})']   = 'main/stats/$1';
$route['hpstats/([a-z_]{7})'] = 'main/hpstats/$1';
$route['marketstats/([a-z_]{7})'] = 'main/marketstats/$1';

$route['register']     = 'user/register';
$route['login']        = 'user/login';
$route['logout']       = 'user/logout';
$route['verify']       = 'user/verify';
$route['settings']     = 'user/settings';
$route['authenticate'] = 'user/authenticate';
$route['autosell']     = 'user/autosell';
$route['referral']     = 'user/referral';
$route['my_referrals'] = 'user/my_referrals';
$route['orders']       = 'user/orders';
$route['orders/([0-9]+)/([0-9]+)']       = 'user/orders/$1/$2';

$route['support']            = 'user/support';
$route['support/new_ticket'] = 'user/new_ticket';
$route['support/view_ticket/([0-9]+)'] = 'user/view_ticket/$1';

$route['reminder/clientid'] = 'user/forgot_client_id';
$route['reminder/password'] = 'user/forgot_password';
$route['reminder/complete/([a-z0-9]{16})'] = 'user/forgot_confirm/$1';

$route['settings/two_factor_authentication']          = 'user/two_factor_authentication';
$route['settings/two_factor_authentication/([a-z]+)'] = 'user/two_factor_authentication/$1';
$route['settings/two_factor_authentication/([a-z]+)/([1-9a-z]+)'] = 'user/two_factor_authentication/$1/$2';
$route['settings/two_factor_withdrawals']             = 'user/two_factor_withdrawals';

$route['settings/two_factor_authentication']          = 'user/two_factor_authentication';
$route['settings/two_factor_authentication/([a-z]+)'] = 'user/two_factor_authentication/$1';
$route['settings/two_factor_withdrawals']             = 'user/two_factor_withdrawals';

$route['api_setup']                  = 'user/api';
$route['api_setup/add']              = 'user/api_edit';
$route['api_setup/edit/([a-zA-Z]*)'] = 'user/api_edit/$1';
$route['api_setup/del/([a-zA-Z]*)']  = 'user/api/del/$1';
$route['api_setup/upd/([a-zA-Z]*)']  = 'user/api/upd/$1';

$route['merchant_setup']                 = 'user/merchant';
$route['merchant_setup/add']             = 'user/merchant/add';
$route['merchant_setup/del/([a-zA-Z]*)'] = 'user/merchant/del/$1';
$route['merchant_setup/upd/([a-zA-Z]*)'] = 'user/merchant/upd/$1';

$route['button_setup']                 = 'user/button';
$route['button_setup/add']             = 'user/button/add';
$route['button_setup/del/([a-zA-Z]*)'] = 'user/button/del/$1';
$route['button_setup/upd/([a-zA-Z]*)'] = 'user/button/upd/$1';

$route['fund/([a-z]{3})']       = 'fund/index/$1';
$route['withdrawal/([a-z]{3})'] = 'withdrawal/index/$1';

$route['trade/([a-z]{3})/([a-z]{3})']  = 'trade/index/$1/$2';
$route['market/([a-z]{3})/([a-z]{3})'] = 'trade/market/$1/$2';

$route['dash']                       = 'trade/dash';
$route['dash/([a-z]{3})/([a-z]{3})'] = 'trade/dash/$1/$2';

$route['account'] = 'trade/dash';

$route['getrate'] = 'rest/trade/get_rate';

// API Public
$route['api/ticker']                  = '_api/public_api_v2/info';
$route['api/order_book']              = '_api/public_api_v2/order_book';
$route['api/transactions']            = '_api/public_api_v2/transactions';

$route['engine/check_bitcoind']       = 'engine/check_bitcoind';

// API Private
$route['api/balance']                 = '_api/private_api_v2/balance';
$route['api/user_transactions']       = '_api/private_api_v2/transactions';
$route['api/open_orders']             = '_api/private_api_v2/open_orders';
$route['api/cancel_order']            = '_api/private_api_v2/cancel_order';
$route['api/lookup_order']            = '_api/private_api_v2/lookup_order';
$route['api/buy']                     = '_api/private_api_v2/buy';
$route['api/sell']                    = '_api/private_api_v2/sell';
$route['api/bitcoin_deposit_address'] = '_api/private_api_v2/bitcoin_deposit_address';
$route['api/bitcoin_withdrawal']      = '_api/private_api_v2/bitcoin_withdrawal';

// API Bitcoin Charts
$route['api/bcc/(.*)']       = '_api/bcc_api/$1';

$route['deposit']            = 'fund/index';
$route['deposit/([a-z]{3})'] = 'fund/index/$1';
$route['deposit/(.*)']       = 'fund/$1';

$route['withdraw']            = 'withdrawal/index';
$route['withdraw/([a-z]{3})'] = 'withdrawal/index/$1';
$route['withdraw/(.*)']       = 'withdrawal/$1';

$route['Upload/photoID']     = 'upload/photoID';
$route['Upload/UtilityBill'] = 'upload/UtilityBill';
$route['Upload/BankLetter']  = 'upload/BankLetter';

/* End of file routes.php */
/* Location: ./application/config/routes.php */
