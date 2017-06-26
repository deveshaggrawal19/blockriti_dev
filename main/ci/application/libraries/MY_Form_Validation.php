<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MY_Form_validation extends CI_Form_validation {

    function MY_Form_validation($config = array()) {
        parent::__construct($config);
    }

    public function errors() {
        return $this->_error_array;
    }

    public function setError($field, $error) {
        $this->_field_data[$field]['error'] = $error;
        $this->_error_array[$field] = $error;
    }

    public function validNumber($number) {
        if (ctype_digit($number))
            return true;

        $this->set_message('validNumber', _l('e_only_digits'));

        return false;
    }

    public function valid_currency_format($input, $currency) {
        if (!isCurrency($currency, $input)) {
            $this->set_message('valid_currency_format', _l('e_incorrect_currency', strtoupper($currency)));
            return FALSE;
        }

        return TRUE;
    }

    function alpha_dash_space($str_in) {
        if (!preg_match("/^([-a-z0-9_ ])+$/i", $str_in)) {
            $this->set_message('alpha_dash_space', _l('e_invalid_characters'));
            return FALSE;
        }

        return TRUE;
    }

    function multiline($str_in) {
        if (!preg_match("/^([-a-z0-9_\s\.,])+$/i", $str_in)) {
            $this->set_message('multiline', _l('e_invalid_characters'));
            return FALSE;
        }

        return TRUE;
    }

    function url($str) {
        if (!preg_match("/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+)\/?/i", $str)) {
            $this->set_message('url', _l('e_invalid_url'));
            return FALSE;
        }

        return TRUE;
    }

    function greater_than($str, $min) {
        if (!is_numeric($str))
            return FALSE;

        if (bccomp($str, (string)$min, 8) >= 0)
            return TRUE;

        $this->set_message('greater_than', _l('e_greater_than'));
        return FALSE;
    }

    function less_than($str, $max) {
        if (!is_numeric($str))
            return FALSE;

        if (bccomp($str, (string)$max) <= 0)
            return TRUE;

        $this->set_message('less_than', _l('e_less_than'));
        return FALSE;
    }

    function valid_country($code) {
        $countries = countries();
        if (isset($countries[$code]))
            return TRUE;

        $this->set_message('valid_country', _l('e_invalid_country'));
        return FALSE;
    }

    function valid_country_no_us($code) {
        $countries = countries();

        unset($countries['US'], $countries['PR'], $countries['GU'], $countries['VI']);

        if (isset($countries[$code]))
            return TRUE;

        $this->set_message('valid_country_no_us', _l('e_invalid_country_no_us'));
        return FALSE;
    }

    function valid_recaptcha($code) {
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $this->config->item('recaptcha_secret_key') . '&response=' . $code;

        if ($this->config->item('recaptcha_user_ip'))
            $url .= '&remoteip=' . getIp();

        $response = json_decode(file_get_contents($url));

        if ($response->success === FALSE)  {
            $this->form_validation->set_message('valid_recaptcha', _l('e_invalid_captcha'));
            return FALSE;
        }

        return TRUE;
    }

    function state($state, $country = null) {
        if (!$country)
            $country = $this->input->post('country');

        $states = state($country);
        if (count($states) != 0 && !isset($states[$state])) {
            $this->form_validation->set_message('valid_state', 'invalid state');
            return FALSE;
        }

        return TRUE;
    }

    function country($country) {
        $countries = countriesLocalised();
        if (count($countries) != 0 && !isset($countries[$country])) {
            $this->form_validation->set_message('valid_country', 'invalid country');
            return FALSE;
        }

        return TRUE;
    }
}