<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// Engine
$lang['invalid_order_book']           = 'Invalid order book %1$s';
$lang['invalid_signature']            = 'Invalid signature';
$lang['invalid_user_credentials']     = 'Invalid user credentials';
$lang['request_not_found']            = 'Cannot perform request - not found';
$lang['incorrect_amount']             = 'Incorrect amount %1$s';
$lang['incorrect_price']              = 'Incorrect price %1$s';
$lang['incorrect_value']              = 'Incorrect value %1$s';
$lang['is_below_the_minimum_of']      = 'is below the minimum of %1$s';
$lang['is_above_the_maximum_of']      = 'is above the maximum of %1$s';
$lang['incorrect_field']              = 'Incorrect %1$s: %2$s';
$lang['exceeds_available_balance']    = 'exceeds available %1$s balance';
$lang['is_either_invalid_or_missing'] = 'The field %1$s (%2$s) is either invalid or missing';
$lang['too_many_requests']            = 'Too many requests';

// Fund
$lang['need_to_login_before_funding'] = 'You need to login before funding your account';
$lang['fund_unverified_user']         = 'You need to have your account verified in order to use %1$s. Click <a href="/verify">here</a> to verify now.';
$lang['disabled_deposit']             = 'Bitcoin deposits have been suspended temporarily. We apologize for the inconvenience. If you have questions, please contact us at <a href="mailto:support@taurusexchange.com">support@taurusexchange.com</a>.';
$lang['renovation_setting_in_enable'] = 'The website is under maintenance at the moment. If you have any questions or concerns, please contact us at <a href="mailto:support@taurusexchange.com">support@taurusexchange.com</a>. We apologize for any inconvenience.';

$lang['account_funding']              = 'Account Funding';
$lang['cannot_fund_with_currency']    = 'You cannot fund your account with that currency';
$lang['fund_request_received']        = 'Your request to fund your account via %1$s has been received. You will receive wire instructions via e-mail within 24 hours.';
$lang['issue_with_funding']           = 'There was an issue with the account funding';
$lang['voucher_code']                 = 'Coupon Code';
$lang['has_now_been_redeemed']        = 'has now been redeemed';
$lang['was_added_to_account']         = 'was added to your account!';
$lang['invalid_voucher_code']         = 'Invalid coupon code';
$lang['not_authorized_to_use']        = 'Your account is currently not authorized to use %1$s. For more information, please contact customer support.';
$lang['address_invalid']              = 'Address Invalid.';
$lang['amount_is_above_maximum']      = 'The amount you entered is above the maximum for this funding method.';
$lang['amount_is_below_minimum']      = 'The amount you entered is below the minimum for this funding method.';

// History
$lang['need_to_login_to_access_page'] = 'You need to login to access this page';

// Main
$lang['request_could_not_be_sent'] = 'Your request could not be sent, please try again...';
$lang['support_email']             = $config["support_email_en"];
$lang['support_email_subject']     = 'Contact/Support Form Submission';
$lang['contact_success']           = 'Your message has been received and our team will aim to reply within 48 hours';
$lang['enter_full_name']           = 'Enter your Full Name';
$lang['enter_valid_email']         = 'Enter a Valid Email Address';
$lang['your_phone_number']         = 'Your phone number including international code';
$lang['your_message']              = 'Your Message...';

// User
$lang['unexpected_error']            = 'There was an unexpected error when trying to save your data. Please contact support.';
$lang['welcome_to']                  = 'Welcome to ';
$lang['verified_to']                 = 'Account Verified';
$lang['need_to_be_logged_in']        = 'You need to login to be able to access this page';
$lang['no_changes_were_made']        = 'No changes were made to your Settings';
$lang['incorrect_password']          = 'Incorrect Password specified';
$lang['settings_updated_logged_out'] = 'Your settings have been updated and you have now been logged out due to the password change.';
$lang['settings_updated']            = 'Your settings have been updated!';
$lang['error_updating']              = 'There was a problem updating your data. Please try again or contact our support team for more information.';
$lang['error_processing']            = 'There was a problem processing your data. Please try again or contact our support team for more information.';
$lang['error_saving_data']           = 'There was a problem saving your data. Please try again or contact our support team for more information.';
$lang['problem']                     = 'There was a problem, please try again.';
$lang['2fa_disabled']                = 'The Google Authenticator has now been disabled from your ' . $config['site_full_name'] . ' account.';
$lang['protectimus_psmart_disabled'] = 'The Protectimus SMART has now been disabled from your ' . $config['site_full_name'] . ' account.';
$lang['protectimus_pmail_disabled']  = 'The Protectimus Email has now been disabled from your ' . $config['site_full_name'] . ' account.';
$lang['protectimus_psms_disabled']   = 'The Protectimus SMS has now been disabled from your ' . $config['site_full_name'] . ' account.';
$lang['2fa_problem']                 = 'There was a problem with the Google Authenticator. Please contact support.';
$lang['error_api']                   = 'You are only allowed 3 APIs';
$lang['the_api']                     = 'The API <strong>';
$lang['was_created']                 = '</strong> was successfully created';
$lang['api_deleted']                 = 'Your API was deleted';
$lang['api_delete_problem']          = 'There was an issue deleting your API. Please contact Customer Support for assistance.';
$lang['successful']                  = 'Successful';
$lang['unsuccessful']                = 'Unsuccessful';
$lang['sorry_problem']               = 'Sorry, there was a problem';
$lang['send_otp']                    = 'You will receive your one-time password within 5 minutes.';
$lang['create_ticket']               = 'You have successfully created a ticket.';
$lang['create_note']                 = 'You have successfully added a new message.';
$lang['incorrect_tiket']             = 'We could not find this ticket in our system.';
$lang['update_pgp_key']              = 'You have successfully added a new pgp key.';
$lang['incorrect_pgp_key']           = 'Your PGP key is not correct. Enter the correct key.';

// Password Reset
$lang['password_has_been_reset']     = 'Your password has now been successfully reset, you may now login.';
$lang['password']                    = 'Password';
$lang['confirm_password']            = 'Confirm Password';
$lang['erroneous_code_entered']      = 'Erroneous code entered';
$lang['client_id_reminder']          = 'Client ID Reminder';
$lang['should_receive_email']        = 'If your email address is found in our database, you should receive an email within the next 5 minutes';
$lang['forgotten_client_id']         = 'Forgotten Client ID';
$lang['forgotten_password']          = 'Forgotten Password';
$lang['problem_locating_record']     = 'Sorry there is a problem locating your record. If you believe this is a problem, please contact support.';
$lang['should_receive_pw_reset']     = 'Within the next 5 minutes you should receive an email with a link to reset your password. If you do not receive this email, please contact our support team for assistance.';
$lang['password_reset_request']      = 'Password Reset Request';


// Withdrawal
$lang['need_to_be_logged_in_withdrawal'] = 'You need to login before making a withdrawal';
$lang['disabled_withdraw']               = 'Bitcoin withdrawals have been suspended temporarily. We apologize for the inconvenience. If you have questions, please contact us at <a href="mailto:'.$config["support_email_en"].'">'.$config["support_email_en"].'</a>.';
$lang['withdrawal_unverified_user']      = 'You need to be a verified member in order to withdraw from your account with %1$s. Click <a href="/verify">here</a> to verify now.';

$lang['cannot_withdraw_currency']        = 'You cannot withdraw with that currency';
$lang['request_received']                = 'Your request to withdraw via Bank Wire has been received.';
$lang['withdrawal_issue']                = 'Sorry, there was an issue with the withdrawal. Please try again later, or contact customer support.';
$lang['did_you_add_gateway']             = 'Have you added ~bitso as a gateway in your RippleTrade client?';
$lang['full_address']                    = 'Full Address including Postcode, City and Country';
$lang['bank_full_address']               = 'Bank Full Address including Postcode, City and Country';
$lang['bic_swift']                       = 'BIC/SWIFT Code';
$lang['any_specific']                    = 'Any Specific Instructions...';
$lang['wu_received']                     = 'Your request to withdraw via Western Union has been received. You will receive details via e-mail within 24 hours.';
$lang['bank_transit']                    = 'Bank Transit';
$lang['dt_received']                     = 'Your request to withdraw by Direct Transfer has been received.';
$lang['cheque_received']                 = 'Your request to withdraw by Cheque has been received. You should receive your cheque in 3 to 7 business days.';
$lang['card_number']                     = 'Card Number';
$lang['visa_received']                   = 'Your request to withdraw via VISA has been received. You will receive details via e-mail within 24 hours.';
$lang['16_digit']                        = '16-Digit Visa Card Number';
$lang['received_bitcoin']                = 'Your request to withdraw Bitcoins has been received.';
$lang['create_coupon']                   = 'You have successfully created a coupon.';
$lang['error_create_coupon']             = 'Sorry, but there was an error and we can not provide a voucher.';
$lang['bitcoin_address']                 = 'Your Bitcoin Address';
$lang['ripple_address']                  = 'Your Ripple Address/Name';
$lang['financial_institution']           = 'Financial Institution';
$lang['coupon_email_subject']            = 'Coupon code';

// Forms
$lang['first_name']                  = 'First Name';
$lang['last_name']                   = 'Last Name';
$lang['email_address']               = 'Email Address';
$lang['enter_valid_email']           = 'Enter a Valid Email Address';
$lang['your_first_name']             = 'Your First Name (e.g., John)';
$lang['your_last_name']              = 'Your Last Name (e.g., Doe)';
$lang['transaction_pin_digits_only'] = 'Digits only (like a bank ATM PIN) - remember this!';
$lang['you_are_logged_in']           = 'You are now logged in!';
$lang['client_id_example']           = 'Client ID (e.g., 12345)';
$lang['city']                        = 'City';
$lang['subject']                     = 'Subject';
$lang['description']                 = 'Description';
$lang['occupation']                  = 'Occupation';

// Errors
$lang['required']              = 'Required field';
$lang['min_length']            = 'Too short (min %2$s characters)';
$lang['max_length']            = 'Too long (max %2$s characters)';
$lang['exact_length']          = 'Should be %2$s chars long';
$lang['matches']               = 'Mismatch';
$lang['invalid']               = 'Invalid';
$lang['less_than']             = 'Too high';
$lang['greater_than']          = 'Too low';
$lang['is_unique']             = 'Already in use';
$lang['email_in_use']          = 'Email already in use';
$lang['invalid_date']          = 'Invalid Date';
$lang['insufficient_balance']  = 'Insufficient %1$s Balance';
$lang['appears_to_be_valid']   = 'Appears to be invalid';
$lang['your_pin_is_incorrect'] = 'Your PIN is incorrect';

// NEW
$lang['api_disabled'] = 'The API has been disabled while we are performing some maintenance';

