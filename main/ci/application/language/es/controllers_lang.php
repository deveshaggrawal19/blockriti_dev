<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

// Engine
$lang['invalid_order_book']           = 'Libro de pedidos inválido %1$s';
$lang['invalid_signature']            = 'Firma inválida';
$lang['invalid_user_credentials']     = 'Credenciales de usuario inválidas';
$lang['incorrect_amount']             = 'Monto incorrecto';
$lang['incorrect_price']              = 'Precio incorrecto';
$lang['incorrect_value']              = 'Valor incorrecto';
$lang['is_below_the_minimum_of']      = 'está por debajo del mínimo de %1$s';
$lang['is_above_the_maximum_of']      = 'está por encima del máximo de %1$s';
$lang['incorrect_field']              = 'Incorrecto %1$s: %2$s';
$lang['exceeds_available_balance']    = 'supera el monto de %1$s disponible';
$lang['is_either_invalid_or_missing'] = 'Campo %1$s (%2$s) no es válido o faltante';
// NEW
$lang['too_many_requests']            = 'Too many requests';
$lang['request_not_found']            = 'Cannot perform request - not found';

// Fund
$lang['need_to_login_before_funding'] = 'Deberás iniciar sesión para poder depositar fondos en tu cuenta.';
$lang['fund_unverified_user']         = 'Necesitas ser miembro verificado para poder depositar fondos en tu cuenta via %1$s. Presiona <a href="/verify">aquí</a> para verificar tu cuenta.';

$lang['account_funding']              = 'Fondear cuenta - Pesos';
$lang['cannot_fund_with_currency']    = 'No puedes depositar fondos en tu cuenta con esa divisa.';
$lang['fund_request_received']        = 'Tu solicitud para fondear tu cuenta via %1$s ha sido recibida. Recibirás un correo électronico con instrucciones para hacer la transferencia en las proximas 24 horas.';
$lang['issue_with_funding']           = 'Hubo un problema con el fondeo de tu cuenta.';
$lang['voucher_code']                 = 'Código de Cupón';
$lang['has_now_been_redeemed']        = 'ha sido canjeado';
$lang['was_added_to_account']         = 'esta en su cuenta!';
$lang['invalid_voucher_code']         = 'Código de Cupón Inválido';
$lang['not_authorized_to_use']        = 'Tu cuenta no está autorizada para el uso de %1$s. Para más información, favor de contactar al departamento de Atención al Cliente.';
$lang['address_invalid']              = 'Dirección Inválida.';
$lang['amount_is_above_maximum']      = 'La cantidad que ingresaste está por arriba del máximo en este método para fondear tu cuenta.';
$lang['amount_is_below_minimum']      = 'La cantidad que ingresaste está por debajo del mínimo en este método para fondear tu cuenta.';

// History
$lang['need_to_login_to_access_page'] = 'Necesitas iniciar sesión para poder acceder a esta página';

// Main
$lang['request_could_not_be_sent'] = 'Tu petición no pudo ser enviada, favor intente de nuevo ...';
$lang['support_email']             = $config["support_email_es"];
$lang['support_email_subject']     = 'Contacto/Envío de Formulario de Soporte';
$lang['contact_success']           = 'Tu mensaje ha sido recibido y nuestro equipo tratará de responder dentro de las próximas 48 horas';
$lang['enter_full_name']           = 'Introduzca su Nombre Completo';
$lang['enter_valid_email']         = 'Introduzca una dirección válida de correo electrónico';
$lang['your_phone_number']         = 'Teléfono incluyendo prefijo internacional';
$lang['your_message']              = 'Tu Mensaje...';

// User
$lang['unexpected_error']            = 'Se ha producido un error inesperado al intentar guardar los datos. Por favor, póngase en contacto con nuestro equipo de soporte.';
$lang['welcome_to']                  = 'Bienvenido a ';
$lang['need_to_be_logged_in']        = 'Necesitas iniciar sesión para poder acceder a esta página';
$lang['no_changes_were_made']        = 'No se hicieron cambios a sus preferencias';
$lang['incorrect_password']          = 'Contraseña incorrecta ingresada';
$lang['settings_updated_logged_out'] = 'Su configuración ha sido actualizada y ahora se han cerrado la sesión debido al cambio de contraseña.';
$lang['settings_updated']            = 'Sus ajustes han sido actualizados!';
$lang['error_updating']              = 'Hubo un problema al actualizar la datos. Por favor, inténtelo de nuevo o contacte con nuestro equipo de soporte para más información.';
$lang['error_processing']            = 'Hubo un problema al procesar tus datos. Por favor, inténtelo de nuevo o contacte con nuestro equipo de soporte para más información.';
$lang['error_saving_data']           = 'Hubo un problema guardando tus datos. Por favor, inténtelo de nuevo o contacte con nuestro equipo de soporte para más información.';
$lang['problem']                     = 'Ha habido un problema, favor intente de nuevo.';
$lang['2fa_disabled']                = 'La Autenticación de Dos Factores se ha deshabilitado de su cuenta ' . $config['site_full_name'];
$lang['2fa_problem']                 = 'Hubo un problema tu Autenticación de Dos Factores. Favor de contactar con nuestro equipo de soporte.';
$lang['error_api']                   = 'Sólo se le permite 3 APIs';
$lang['the_api']                     = 'Tu API <strong>';
$lang['was_created']                 = '</strong> ha sido creada exitosamente';
$lang['api_deleted']                 = 'Tu API ha sido eliminada';
$lang['api_delete_problem']          = 'Ha surgido un error al borrar tu API. Favor de contactar con nuestro equipo de soporte.';
$lang['successful']                  = 'Éxito';
$lang['unsuccessful']                = 'Fallido';
$lang['sorry_problem']               = 'Lo sentimos, ha habido un problema';

// Password Reset
$lang['password_has_been_reset']     = 'Tu contraseña a sido reseteada. Ya puedes iniciar sesión.';
$lang['password']                    = 'Contraseña';
$lang['confirm_password']            = 'Confirmar Contraseña';
$lang['erroneous_code_entered']      = 'Código invalido';
$lang['client_id_reminder']          = 'Client ID Reminder';
$lang['should_receive_email']        = 'Si tu dirección existe en nuestra base de datos recibirás un correo electrónico en los próximos 5 minutos.';
$lang['forgotten_client_id']         = 'Recordatorio de ID de Usuario';
$lang['forgotten_password']          = 'Recuperación de Contraseña';
$lang['problem_locating_record']     = 'Hubo un problema localizando tus récords. Favor de contactar a soporte.';
$lang['should_receive_pw_reset']     = 'Recibirás un correo electrónico en los próximos 5 segundos con una liga para resetear tu contraseña. Si no recibiste nuestro correo favor contacte a nuestro equipo de soporte.';
$lang['password_reset_request']      = 'Solicitud de Recuperación de Contraseña';

// Withdrawal
$lang['need_to_be_logged_in_withdrawal'] = 'Necesitas iniciar sesión antes de hacer retiros';
$lang['withdrawal_unverified_user']      = 'Necesitas ser miembro verificado para poder retirar fondos en tu cuenta via %1$s. Presiona <a href="/verify">aquí</a> para verificar tu cuenta.';

$lang['cannot_withdraw_currency']        = 'No puedes retirar con esa divisa';
$lang['request_received']                = 'Su solicitud de retiro a través de Transferencia Bancaria se ha recibido.';
$lang['withdrawal_issue']                = 'Hubo un problema con el retiro';
$lang['did_you_add_gateway']             = '¿Has añadido a ~bitso como un gateway en el cliente de RippleTrade?';
$lang['full_address']                    = 'Dirección Completa incluyendo Código Postal, Ciudad y País';
$lang['bank_full_address']               = 'Dirección Completa de Banco incluyendo Código Postal, Ciudad y País';
$lang['bic_swift']                       = 'Código CLABE / SWIFT';
$lang['any_specific']                    = 'Todas las recomendaciones específicas...';
$lang['wu_received']                     = 'Su solicitud de retiro a través de Western Union se ha recibida. Usted recibirá los detalles a través de correo electrónico dentro de las 24 horas.';
$lang['bank_transit']                    = 'Identificación de Tránsito Bancario';
$lang['dt_received']                     = 'Su solicitud de retiro por Direct Transfer ha sido recibida.';
$lang['cheque_received']                 = 'Su solicitud de retiro por cheque ha sido recibida. Usted debe recibir su cheque en 5 a 10 días hábiles.';
$lang['card_number']                     = 'Card Number';
$lang['visa_received']                   = 'Su solicitud de retiro a través de Tarjeta VISA ha sido recibida. Usted recibirá los detalles a través de correo electrónico dentro de las 24 horas.';
$lang['16_digit']                        = 'Número de Tarjeta VISA';
$lang['received_bitcoin']                = 'Su solicitud de retiro por Bitcoins ha sido recibida.';
$lang['bitcoin_address']                 = 'Tu Dirección de Bitcoin';
$lang['ripple_address']                  = 'Tu Dirección/Nombre de Ripple';

// Forms
$lang['first_name']                  = 'Nombre';
$lang['last_name']                   = 'Apellidos (Paterno y Materno)';
$lang['email_address']               = 'Correo Electrónico';
$lang['enter_valid_email']           = 'Introduzca una dirección válida de correo electrónico';
$lang['your_first_name']             = 'Tu Nombre - p. ej. Juan';
$lang['your_last_name']              = 'Tus Apellidos - p. ej. Pérez González';
$lang['transaction_pin_digits_only'] = 'Sólo dígitos (Como un PIN de cajero de banco) - acuérdate de este!';
$lang['you_are_logged_in']           = 'Ha iniciado la sesión!';
$lang['client_id_example']           = 'Número de Cliente - p. ej. 12345';
$lang['city']                        = 'Ciudad';

// Errors
$lang['required']              = 'Campo obligatorio';
$lang['min_length']            = 'Demasiado corto (min %2$s carácteres)';
$lang['max_length']            = 'Demasiado largo (max %2$s carácteres)';
$lang['exact_length']          = 'Deben ser %2$s carácteres';
$lang['matches']               = 'Discordancia';
$lang['invalid']               = 'Inválido';
$lang['less_than']             = 'Demasiado alto';
$lang['greater_than']          = 'Demasiado bajo';
$lang['is_unique']             = 'En uso';
$lang['email_in_use']          = 'Correo electrónico ya está en uso';
$lang['invalid_date']          = 'Fecha Inválida';
$lang['insufficient_balance']  = 'Balance %1$s Insuficiente';
$lang['appears_to_be_valid']   = 'No parece ser válido';
$lang['your_pin_is_incorrect'] = 'Tu PIN es incorrecto';

// NEW
$lang['api_disabled'] = 'The API has been disabled while we are performing some maintenance';