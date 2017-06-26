<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// Bitso-specific
$lang['main_page_title'] = 'Mexican Bitcoin Exchange';
$lang['layout_unauth'] = 'Taurus: Trade Bitcoin For Free.';

// Account Funding
$lang['account_funding'] = 'Account Funding';
$lang['intro_funding']   = '
Bitso offers numerous funding options.
Certain funding options may require that your account be verified.
To fund your account, select your preferred funding option from the choices below.
';
$lang['limits']          = 'Limits';
$lang['timeframe']       = 'Timeframe';
$lang['instant']         = 'Instant';
$lang['amount']          = 'Amount';
$lang['voucher']         = 'Voucher';
$lang['coupon']          = 'Coupon';
$lang['cash-in-person']  = 'Cash In-Person';
$lang['bank_wire']       = 'International Bank Wire';
$lang['bank_wire_spei']  = 'SPEI Transfer (within Mexico)';
$lang['spei_header']     = 'SPEI Transfer';
$lang['business_days']   = 'Business Days';
$lang['hours']           = 'Hours';
$lang['coming_soon']     = 'Coming Soon';
$lang['commission']      = 'Commission';

// Deposit - bankwire
$lang['heading_bank_wire'] = 'Account Funding - Bank Wire';
$lang['bank_wire_intro']   = 'To fund your account via Bank Wire, please complete the following form:';
$lang['country']           = 'Country';
$lang['final_beneficiary'] = 'Final Beneficiary';
$lang['reference_code']    = 'Reference Code (Note Field)';


// Deposit - SPEI
$lang['heading_bank_spei'] = 'Account Funding - SPEI Transfer';
$lang['bank_spei_intro']   = 'To fund your account via SPEI (Mexican inter-bank), please complete the following form:';

// Deposit - voucher
$lang['heading_voucher'] = 'Account Funding - Redeem Voucher';
$lang['voucher_intro']   = 'To redeem a voucher, please enter the 16 characters code that was given to you in the following form:';
$lang['code']            = 'Code';

// Deposit - Bill Pay
$lang['heading_bill_pay'] = 'Account Funding - Bill Pay';

// Deposit - Money Order
$lang['heading_money_order'] = 'Account Funding - Money Order';

// Deposit - Western Union
$lang['heading_western_union'] = 'Account Funding - Western Union';
$lang['western_union_intro']   = 'To fund your account via Western Union, please complete the following form:';
$lang['sender_fullname']       = 'Nombre Completo del Remitente';
$lang['sender_fullname']       = 'Nombre Completo del Remitente';

// Deposit - Compropago
$lang['heading_compropago'] = 'Account Funding - Deposit Cash';
$lang['intro_compropago']   = 'You can finance your Bitso account with cash at more than 130,000 locations nationwide, using the ComproPago payment system, including Oxxo, Extra, 7/11, Chedraui, Elektra, Wallmart, among others. Simply enter the amount, select the store you would like to use, and click \'Proceed\'. The service fee charged by Compropago is 2.9% + 3.00 MXN. For limits per transaction, and store hours, please see <a target="_blank" href="https://compropago.com/documentacion/comisiones"><strong>here</strong></a>';
$lang['cash']               = 'Cash';
$lang['compropago_desc']    = 'Deposit in Cash: Oxxo, 7/11, Wallmart and more.';
$lang['amount_to_send']     = 'Amount to send';
$lang['amount_will_credit'] = 'Amount that will credit';
$lang['pay_at']             = 'Pay at';

// Deposit - Pademobile
$lang['heading_pademobile'] = 'Account Funding - ';
$lang['pademobile_intro']   = 'You can fund your Bitso account using your Pademobile wallet or your credit card. Simply enter the amount, click \'Proceed\', and follow the instructions inside Pademobile\'s secure gateway. Once complete, you will be returned to Bitso, with your account funded instantly. The service fee charged by Pademobile is 1.5% of the total amount.';
$lang['pademobile_desc']    = 'Pademobile: with Credit Card or Prepaid Balance';

// Deposit - Astropay
$lang['heading_astropay']   = 'Account Funding - Astropay';
$lang['astropay_complete']  = 'Your funds have been received and will credit to your balance shortly.';
$lang['astropay_intro']     = 'Fund your account <b>instantly</b> direct from your bank account! The service fee charged by Astropay is 5%.';
$lang['astropay_cancelled'] = 'Transaction cancelled.';

// Deposit - bitcoin
$lang['heading_bitcoin']    = 'Account Funding - Bitcoin';
$lang['step']               = 'Step';
$lang['intro_bitcoin_a']    = 'To fund your account with Bitcoins, please complete the following form.';
$lang['intro_bitcoin_b']    = 'After submitting the form, you will be given a Bitcoin Address to send your btc to.';
$lang['intro_bitcoin_c']    = 'To fund your account with Bitcoin, send your payment to the Bitcoin address below.';
$lang['intro_bitcoin_d']    = 'Please note that Bitcoin account fundings are automatically completed after <strong>4 confirmations</strong>. As soon as the transaction appears in the blockchain, your transfer will appear in your "Pending" Bitcoin Balance.';
$lang['thank_you']          = 'Thank you!';
$lang['please_send']        = 'Please send your';
$lang['to']                 = 'to';
$lang['single_use_warning'] = 'NOTE: This is a single-use address. Do not re-use this address after this deposit.';

// Trade
$lang['heading_trade']        = 'Trade';
$lang['heading_market_data']  = 'Market Overview';
$lang['heading_market_live']  = 'Live Trades';
$lang['heading_market_trade'] = 'Market Trade';
$lang['heading_recent_data']  = 'Most Recent Trades';
$lang['heading_order_book']   = 'Order Book';
$lang['buy_orders']           = 'Buy Orders';
$lang['sell_orders']          = 'Sell Orders';
$lang['price']                = 'Price';
$lang['market']               = 'MARKET';
$lang['amount']               = 'Amount';
$lang['value']                = 'Value';
$lang['date']                 = 'Date';
$lang['time']                 = 'Time';
$lang['min_price']            = 'Minimum Price';
$lang['max_price']            = 'Maximum Price';
$lang['avg_price']            = 'Average Price';

$lang['amt_to_buy']   = 'Amount to Buy';
$lang['amt_to_sell']  = 'Amount to Sell';
$lang['per']          = 'per';
$lang['total']        = 'Total';
$lang['top10_buy']    = 'Top 10 Buy Orders';
$lang['top10_sell']   = 'Top 10 Sell Orders';
$lang['top10_recent'] = '10 Most Recent Trades';

$lang['statistics']         = 'Statistics';
$lang['account_balance']    = 'Balances';
$lang['last_price']         = 'Last Price';
$lang['24_hr_volume']       = '24 Hour Volume';
$lang['open_orders']        = 'Open Orders';
$lang['in']                 = 'In';
$lang['out']                = 'Out';
$lang['my_recent']          = 'My Recent';
$lang['trades']             = 'Trades';
$lang['more']               = 'more';
$lang['match']              = 'Match';

// History
$lang['intro_history_trades_a']    = 'Below is a history of all cryptocurrency trades performed within your account. You can also';
$lang['intro_history_trades_b']    = 'your trade history as a CSV file.';
$lang['intro_history_funding']     = 'Below is a history of all account fundings, both Bitcoin and Fiat, since the creation of your account.';
$lang['intro_history_withdrawals'] = 'Below is a history of all withdrawals, both Bitcoin and Fiat, since the creation of your account.';
$lang['intro_history_referrals']   = 'A detailed breakdown of your referral earnings can be found below.';
$lang['export']                    = 'export';
$lang['history_date']              = 'Date';
$lang['history_action']            = 'Action';
$lang['history_commission']        = 'Commission';
$lang['history_total']             = 'Total';
$lang['history_method']            = 'Method';
$lang['history_amount']            = 'Amount';
$lang['history_from']              = 'From';

// History - Deposits
$lang['funding_of']  = 'Funding of';
$lang['no_fundings'] = 'No fundings';

// History - Trades
$lang['sold']      = 'Sold';
$lang['bought']    = 'Bought';
$lang['for']       = 'for';
$lang['no_trades'] = 'No trades';

// History - Withdrawals
$lang['withdrawal_of']  = 'Withdrawal of';
$lang['no_withdrawals'] = 'No withdrawals';

// Register
$lang['ensure_name_matches_id'] = 'IMPORTANT: Please ensure you give your name <strong>exactly</strong> as it is on your ID, as this will make our user verification process more efficient.';
$lang['registration_complete']  = 'Registration Complete!';
$lang['thanks_for_joining']     = 'Thank you for joining ' . $config['site_name'] . ', Mexico\'s First Bitcoin Trading Platform.';
$lang['save_this_info']         = 'Please save the following info as you will require your Client ID to access your account.';
$lang['client_id']              = 'Client ID';
$lang['password']               = 'Password';
$lang['transaction_pin']        = 'Security PIN';
$lang['hidden_for_security']    = 'Hidden for your security';
$lang['info_sent_in_email']     = 'The above information has also been sent in a welcome e-mail.';
$lang['what_to_do_next']        = 'WHAT TO DO NEXT: <br>
1. Login using your <strong>Client ID</strong> sent to you via email.<br>
2. <strong>Verify</strong> your account to gain access to the full features of Bitso Exchange.<br>
3. <strong>Deposit funds</strong> into your Bitso account and begin trading.';
$lang['referrer']               = 'Your referrer';

// Register - phase 1
$lang['dob'] = 'Date of Birth';

// Register - phase 2
$lang['confirm_password'] = 'Confirm Password';
$lang['i_agree_to_the']   = 'I agree to the';
$lang['terms_of_service'] = 'Terms of Service';
$lang['and']              = 'and';
$lang['privacy_policy']   = 'Privacy Policy';

// 2fa Authenticate
$lang['heading_2fa']          = 'Two-Factor Authentication';
$lang['heading_authenticate'] = 'Authenticate';
$lang['intro_2fa']            = 'Please enter the Two-Factor Authentication to validate your login.';
$lang['2fa']                  = 'Two-Factor Authentication';
$lang['2fa_psmart']           = 'Protectimus SMART';
$lang['2fa_pmail']            = 'Protectimus Email';
$lang['2fa_psms']             = 'Protectimus SMS';
$lang['2fa_code']             = '2FA Code';
$lang['link_logout']          = 'Log Out';
$lang['back_to_settings']     = 'Back to Settings';


// 2fa Disable
$lang['heading_2fa_disable'] = 'Two-Factor Authentication';
$lang['2fa_disable_intro']   = 'Are you sure you want to disable the <strong>Two-Factor Authentication</strong> from your account?';
$lang['yes_understood']      = 'YES, I understand';
$lang['no_cancel']           = 'NO, take me back my settings';

// 2fa Done
$lang['heading_2fa_done'] = 'Two-Factor Authentication';
$lang['2fa_message_a']    = 'Two-Factor Authentication is now enabled on your account. For your
security, we recommend that you either save or print the following "Emergency Reset Code".';
$lang['2fa_message_b']    = 'In the event that you lose your phone or tablet, you will need this "Emergency
Reset Code" to regain access to your account.';
$lang['2fa_message_c']    = 'Emergency Reset Code:';
$lang['2fa_message_d']    = 'Next time you log in to ' . $config['site_name'] . ' you will be asked to provide the 6-digit
code generated by your Two-Factor Authentication app.';

// 2fa Enable
$lang['heading_2fa_enable'] = 'Two-Factor Authentication';
$lang['intro_2fa_enable']   = 'Two-Factor Authentication is one of the best ways to ensure that your
account stays safe. By activating Two-Factor Authentication, you ensure that
only you are able to access your account. If your password is stolen, your
account will remain secure.';
$lang['intro_2fa_enable_b'] = 'To Install Two-Factor Authentication, please follow these steps:';
$lang['2fa_install']        = '1. Install a Two-Factor Authentication application on your smartphone or tablet. We recommend Google Authenticator, which can be downloaded here:';
$lang['2fa_install_b']      = '2. After installing the Two-Factor software, you will need to configure the
application to work with ' . $config['site_name'] . '. To do so, scan the QR code to the right, or
type in the following secret code:';
$lang['2fa_install_c']      = '3. After scanning the QR code, you will be given a 6-digit code. Please enter
it below:';

// API
$lang['heading_api_setup']          = 'API Setup';
$lang['intro_api_setup']            = 'The following settings are used to configure your account to use the ' . $config['site_name'] . ' API. These are advanced settings and should only be used by users who are familiar with our API system. To learn more about our API, click <a href="/api_info">here</a>.';
$lang['heading_api_keys']           = 'Your API keys';
$lang['api_name']                   = 'Name';
$lang['api_details']                = 'Details';
$lang['api_key']                    = 'API Key';
$lang['api_secret']                 = 'API Secret';
$lang['click_to_reveal']            = 'click to reveal';
$lang['api_name_2']                 = 'API Name';
$lang['add_new_api']                = 'Add new API';
$lang['save_new_api']               = 'Save new API';
$lang['edit_api']                   = 'Edit API';
$lang['withdrawal_bitcoin_address'] = 'Withdrawal Bitcoin Address';
$lang['api_setup_intro']            = 'The following form allows you to create a new API to be used on your website, or for a trading integration. If you need more information, you can refer to the %1$s page.';
$lang['api_information']            = 'API Information';

// History
$lang['heading_history']     = 'History';
$lang['heading_trades']      = 'Trades';
$lang['heading_fundings']    = 'Fundings';
$lang['heading_withdrawals'] = 'Withdrawals';

// Login
$lang['heading_login']      = 'Login';
$lang['intro_login']        = 'Please enter your client ID and password to access your account.';
$lang['if_forgotten_pw']    = 'Forgotten your <b>password</b>? %1$s to request a new password';
$lang['if_forgotten_cid']   = 'Forgotten your <b>client ID</b>? %1$s to receive a reminder';
$lang['login_click_here']   = 'Click here';

// Register
$lang['heading_register'] = 'Register';
$lang['intro_register']   = 'Complete the following form to join ' . $config['site_name'] . '.';

// Settings
$lang['heading_profile_settings'] = 'Profile Settings';
$lang['intro_profile_settings']   = 'To update your account details, please edit the fields below.';
$lang['label_2fa']                = 'Google Authenticator';
$lang['protectimus_smart_2fa']    = 'Protectimus SMART';
$lang['protectimus_mail_2fa']     = 'Protectimus Email';
$lang['protectimus_sms_2fa']      = 'Protectimus SMS';
$lang['disabled']                 = 'Disabled';
$lang['enabled']                  = 'Enabled';
$lang['disable']                  = 'disable';
$lang['enable']                   = 'enable';
$lang['avatar']                   = 'Avatar';
$lang['use_avatar']               = 'If you would like to update your avatar, please go to';

$lang['please_enter_current_password'] = 'In order to validate the changes, please enter your current password:';

// Transaction
$lang['heading_transaction']      = 'Transaction';
$lang['your_transaction_was']     = 'Your Transaction was';
$lang['intro_transaction']        = 'Your funding has been added to your account. The details of your transaction are displayed to the right. You may wish to print this page for your records.';
$lang['print_this_page']          = 'Print this page';
$lang['details_of_transaction']   = 'Details of the transaction';
$lang['your_order_was_cancelled'] = 'Your order has been cancelled. Please contact our customer support should you have any questions.';
$lang['transaction_id']           = 'Transaction ID';
$lang['transaction_date']         = 'Transaction Date';
$lang['financial_institution']    = 'Financial Institution';
$lang['confirmation_number']      = 'Confirmation Number';

// Verification
$lang['heading_verification']          = 'Member Verification';
$lang['choose_supporting_doc']         = 'Now please select a supporting document type:';
$lang['and_provide_your_phone_number'] = 'and provide your phone number:';

$lang['intro_verification']    = 'Some features of ' . $config['site_name'] . ' require that your account be verified. This is
done to comply with Mexican CNBV regulations, and to prevent fraud within
our system. In order to verify your account, we will require the following documents:';
$lang['intro_verification_a']  = '<h4>1. Valid ID. One of the following:</h4><ul><li>Passport</li><li>IFE for Mexican Citizens</li><li>Official National ID for International Clients</li></ul>';
$lang['intro_verification_b']  = '<h4>2. Supporting Document. One of the following:</h4><ul><li>Bank statement</li><li>Credit card statement</li><li>Utility bill</li><li>Phone bill</li><li>Vehicle title</li><li>Tax return</li><li>School enrolment letter</li></ul>';


$lang['jumio_fail']                  = 'Sorry, there was a problem with your verification attempt. Please contact our support team for help. You can quote verification attempt ID: ';
$lang['jumio_complete']              = 'Thank you, your details are being processed. As soon as they are confirmed, your account will be fully activated, and we will send you an email to let you know!'; // TODO - translate
$lang['jumio_error_name']            = 'ID name does not match registered name.';
$lang['jumio_error_dob']             = 'DoB does not match registered DoB.';
$lang['jumio_error_unknown']         = 'There was a problem with your ID.';
$lang['jumio_error_country']         = 'Country does not match country of registration.';
$lang['jumio_verification_problem']  = 'Verification Problem';
$lang['jumio_verification_complete'] = 'Verification Complete!';
$lang['jumio_different_to']          = " is different to ";
$lang['jumio_unreadable']            = 'Sorry, document was not readable.';
$lang['jumio_blurred']               = 'Document was blurred.';
$lang['jumio_photocopy']             = 'Photocopy or Black & White.';
$lang['jumio_poor_quality']          = 'Document is poor quality.';
$lang['jumio_damaged']               = 'Document is damaged.';
$lang['jumio_part_hidden']           = 'Part of the document was hidden.';
$lang['jumio_part_missing']          = 'Part of the document is missing.';
$lang['jumio_unsupported_type']      = 'Unsupported ID type.';
$lang['jumio_back_missing']          = 'Back of ID missing.';


// Withdrawal
$lang['heading_withdrawal']        = 'Withdrawal';
$lang['heading_withdrawal_wu']     = 'Withdrawal - Western Union';
$lang['heading_withdrawal_direct'] = 'Withdrawal - Direct Transfer';
$lang['heading_withdrawal_cheque'] = 'Withdrawal - Cheque';
$lang['heading_withdrawal_visa']   = 'Withdrawal - VISA';

// Withdrawal - Bank Wire
$lang['heading_withdrawal_bankwire'] = 'Withdrawal - Bank Wire';
$lang['intro_withdraw_bankwire']     = 'To withdraw via Bank Wire, please complete the following form:';
$lang['recipients_name']             = 'Recipient\'s Full Name';
$lang['account_holder_address']      = 'Account Holder\'s Address';
$lang['bank_name']                   = 'Bank Name';
$lang['bank_address']                = 'Bank Address';
$lang['account_number']              = 'Account Number';
$lang['swift']                       = 'SWIFT';
$lang['other_instructions']          = 'Other Instructions';
$lang['bank_wire_done']              = 'Your deposit request has been received. Now please print off and complete the following details to take to your financial institution for processing.';

// Withdrawal - SPEI
$lang['heading_withdrawal_spei'] = 'Withdrawal - SPEI';
$lang['intro_withdraw_spei']     = 'To withdraw via Mexican Bank SPEI, please complete the following form. SPEI withdrawals are completed within one business day.';
$lang['bank_spei_done']          = 'Your deposit request has been received. Now please print off and complete the following details to take to your financial institution for processing.';
$lang['beneficiary']             = 'Beneficiary';
$lang['notes']                   = 'Notes';
$lang['rfc_for_companies']       = 'RFC [Taxing ID] (only for institutions)';

// Withdrawal - Bitcoin
$lang['heading_withdrawal_bitcoin'] = 'Withdrawal - Bitcoin';
$lang['intro_withdraw_bitcoin']     = 'To withdraw bitcoin from your ' . $config['site_name'] . ' balance, please complete the following form:';
$lang['address']                    = 'Address';

// Withdrawal - Coupon
$lang['heading_withdrawal_coupon'] = 'Withdrawal - Coupon';
$lang['withdrawal_coupon_code1'] = 'Your code is ';
$lang['withdrawal_coupon_code2'] = 'Please copy or write it down. A copy of your coupon has been emailed to you.';
$lang['currency'] = 'Currency';

// Withdrawal - Peso
$lang['heading_withdrawal_peso'] = 'Peso Withdrawal';
$lang['intro_withdrawal_peso']   = $config['site_name'] . ' offers several withdrawal options. To withdraw Mexican Peso from your account, select your preferred withdrawal option from the
choices below.';
$lang['select']                  = 'select';

// Contact Us
$lang['heading_contact_us'] = 'Contact Us';
$lang['verify_human']       = 'In order to verify you are a human, please enter the %1$s letter of the word %2$s';

// Refund Policy
$lang['heading_refund_policy']    = 'Refund Policy';
$lang['intro_refund_policy']      = 'Due to the nature of the Bitcoin and Cryptocurrency markets, all trades conducted on ' . $config['site_name'] . '
are final. Please review our official refund policy below.';
$lang['refund_policy_a']          = 'All fundings made to ' . $config['site_name'] . ' members\' accounts can be refunded, at the request of the user, to the original funding method, provided that the funds remain in the original fiat balance of that user. If only a portion of the funds remain in the user account, a partial refund may be completed at the request of the user.';
$lang['refund_policy_b']          = 'Funds which have been traded for Bitcoin or any other Cryptocurrency through the ' . $config['site_name'] . ' system are not eligible for a refund.';
$lang['refund_policy_c']          = 'Funds which have been withdrawn from the ' . $config['site_name'] . ' website, using any available method, are not eligible for a refund.';
$lang['refund_policy_d']          = 'Refund requests must be submitted within 7 days of completing the original funding. Official requests should be sent to ' . $config['contact_email'];
$lang['refund_policy_e']          = 'All refund requests will be processed using the same payment method as the original funding.';
$lang['refund_policy_additional'] = 'If you have additional questions regarding our Refund Policy, please contact the administration of ' . $config['site_name'] . ' at ' . $config['contact_email'];

// Widgets
$lang['locked']             = 'Locked For Orders';
$lang['locked_for_orders']  = 'Locked For Orders';
$lang['pending_funding']    = 'Pending Funding';
$lang['pending_deposit']    = 'Pending Deposit';
$lang['pending_withdrawal'] = 'Pending Withdrawal';

$lang['last_trade']   = 'LAST TRADE';
$lang['volume']       = 'Volume';
$lang['high']         = 'High';
$lang['low']          = 'Low';
$lang['daily_change'] = 'Daily Change';
$lang['market_cap']   = 'Market Cap';
$lang['total_xbt']    = 'Total XBT';
$lang['chart_note']   = 'Historical data before April, 6<sup>th</sup> 2014 sourced from ';

$lang['fund_account'] = 'Fund Account';
$lang['fund']         = 'Fund';
$lang['withdraw']     = 'Withdraw';

$lang['account_verified'] = 'Account Verified';
$lang['profile_settings'] = 'Profile Settings';
$lang['account_history']  = 'Account History';
$lang['verify']           = 'Verify';
$lang['api_setup']        = 'API Setup';
$lang['log_out']          = 'Log Out';

// Home
$lang['heading_latest_news'] = 'Latest News';
$lang['heading_featured_in'] = 'Featured In';

$lang['open_an_account'] = 'Open an Account';
$lang['slide_1']         = 'THE FUTURE OF MONEY IS HERE';
$lang['slide_1b']        = 'Join Mexico\'s secure Bitcoin & Ripple hub.';

// Dash
$lang['intro_dashboard']              = '
<p>Welcome to the Dashboard! Here you can quickly buy and sell bitcoins at best market price, review your account balances, account details and most recent history.
<p>In order to buy bitcoins, you need to first <a href="/user/verify"><b>verify your account</b></a>, then <a href="/fund/mxn"><b>fund it</b></a>. Please see the <a href="/faq"><b>FAQ</b></a> for questions or follow our step-by-step tutorial on how to make your first trade.
';
$lang['heading_balances']             = 'Balances';
$lang['heading_quickbuy']             = 'Quick Buy';
$lang['heading_accountinfo']          = 'Account Info';
$lang['ripple_redeem']                = 'Redeem from Ripple';
$lang['ripple_send']                  = 'Send to Ripple';
$lang['not_verified']                 = 'Not Verified';
$lang['account_status']               = 'Account Status';
$lang['verify_account']               = 'Click here to verify your account.';
$lang['click_to_edit']                = 'Click here to edit.';
$lang['trading_fee']                  = 'Trading Fee';
$lang['ripple_fee']                   = 'Ripple In/Out Fee';
$lang['view_fee_schedule']            = 'View full fee schedule.';
$lang['account_settings']             = 'Profile<br/>Settings';
$lang['balance']                      = 'Balance';
$lang['i_want_to']                    = 'I want to';
$lang['buy_bitcoin']                  = 'Buy bitcoins';
$lang['sell_bitcoin']                 = 'Sell bitcoins';
$lang['amt_to_spend']                 = 'Amount to spend';
$lang['amt_to_sell']                  = 'Amount to sell';
$lang['maximum']                      = 'Maximum';
$lang['you_will_receive_approx']      = 'You will receive approximately';
$lang['looking_to_trade']             = 'Looking to trade using the limit order feature? <b><a href="/trade">Click here to go to the Trade page</a></b>.';
$lang['available']                    = 'Available';
$lang['most_recent_history']          = 'Most Recent History';
$lang['view_full_history']            = 'View Full History';
$lang['you_sent']                     = 'You sent';
$lang['to_the_ripple_network']        = 'to the Ripple Network.';
$lang['you_redeemed']                 = 'You redeemed';
$lang['from_the_ripple_network']      = 'from the Ripple Network.';
$lang['you_bought']                   = 'You bought';
$lang['you_sold']                     = 'You sold';
$lang['you_withdrew']                 = 'You withdrew';
$lang['from_your_account']            = 'from your account';
$lang['you_funded_your_account_with'] = 'You funded your account with';
$lang['you_got_a_referral_fee_from']  = 'You got referral fee from';
$lang['share_on_facebook']            = 'Share on Facebook';

// Referrals
$lang['heading_referrals']            = 'Referrals';
$lang['referrer_name']                = 'Name';
$lang['your_referral_link']           = 'Your Referral Link';
$lang['amount_of_referrals']          = 'Number of Referrals';
$lang['select_text']                  = 'Select Text';

// Password reminder
$lang['forgot_password_intro']        = 'Bitso takes your security seriously. To reset your password, complete the form below.';
$lang['forgot_password_heading']      = 'Forgotten Password';
$lang['forgotten_client_id_tip']      = 'If you have forgotten your client ID, %1$s to receive a reminder.';
$lang['click_here_pw']                = 'click here';
$lang['confirm']                      = 'Confirm';
$lang['forgotten_clientid_heading']   = 'Client ID Reminder';
$lang['to_receive_client_id']         = 'To receive your client ID, complete the form below.';
$lang['password_complete_heading']    = 'Forgotten Password Completion';
$lang['password_complete_intro']      = 'Please now set a new password.';

$lang['balance_available']            = 'Available %1$s';
$lang['complete']                     = 'Complete';

// Invalid Session
$lang['invalid_or_expired_session']   = 'Invalid or expired session';
$lang['invalid_session_intro']        = 'Invalid or expired sessionIt looks like your current session has expired. Please login again in order to continue.';

// Maintenance
$lang['well_be_back']                 = 'We\'ll be back soon!';
$lang['maintenance_message']          = 'Sorry for the inconvenience but we\'re performing some maintenance at the moment. To contact us, please e-mail <strong>contact at ' . $config['site_domain'] . '</strong> but we\'ll be back online shortly!';


//Support
$lang['have_not_ticket']              = 'You have no tickets yet.';