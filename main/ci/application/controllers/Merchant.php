<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Merchant extends CI_Controller {

    private $properties;
    private $store;

    public function __construct() {
        parent::__construct();

        $this->load->library('form_validation');
        $this->load->library('layout');
        $this->load->library('redis');
        $this->load->library('api_security');

        $this->load->helper('widget');

        $this->layout->setLayout('merchant');

        $this->lang->load('menu', 'en');

        $this->load->model('merchant_model');
        $this->load->model('meta_model');
        $this->load->model('bitcoin_model');
        $this->load->model('notification_model');
    }

    private function _error($code) {
        $args = func_get_args();
        array_shift($args);

        $errors = array(
            1 => 'Missing argument <strong>%s</strong>',
            2 => 'Argument <strong>%s</strong> is not the right format or does not contain the right value',
            3 => 'Store <strong>%s</strong> does not exists',
            4 => 'This transaction has either already been processed or cannot be found',
            5 => 'Argument <strong>%s</strong> is too long, max %d characters',
            6 => 'Argument <strong>%s</strong> is below the minimum of %s<small>%s</small>'
        );

        $data['message'] = vsprintf($errors[$code], $args);

        if ($this->store && $this->store->cancel)
            $data['return'] = $this->store->cancel;

        $this->layout->setTitle('Merchant - Error')->view('merchant/error', $data);

        return false;
    }

	public function index() {
        // Need to do a few checks and sanitize data before anything else
        // -- Required fields
        $required = array('key', 'amount');
        foreach ($required as $key) {
            if (!$value = $this->input->post($key))
                return $this->_error(1, strtoupper($key));

            $this->properties[$key] = trim($value);
        }

        if (!preg_match('/^[a-z]{10,20}$/i', $this->properties['key']))
            return $this->_error(2, 'KEY');

        // -- Check the Store exists
        $key   = $this->properties['key'];
        $store = $this->merchant_model->get($key);
        if (!$store)
            return $this->_error(3, $key);

        $this->store = $store;

        // -- Other Required fields
        $this->properties['currency'] = $this->input->post('currency') ? strtolower($this->input->post('currency')) : $store->currency;
        $requestedCurrency = $this->properties['currency'];

        $cryptoCurrencies = $this->meta_model->getCryptoCurrencies();
        $fiatCurrencies   = $this->meta_model->getFiatCurrencies();
        if ($requestedCurrency != $store->currency) {
            if ((in_array(strtolower($requestedCurrency), $cryptoCurrencies) !== false && in_array(strtolower($store->currency), $cryptoCurrencies) !== false)
                || (in_array(strtolower($requestedCurrency), $fiatCurrencies) !== false && in_array(strtolower($store->currency), $fiatCurrencies) !== false))
                return $this->_error(2, 'CURRENCY'); // We cannot have 2 same type of currencies here
        }

        if (!is_numeric($this->properties['amount']))
            return $this->_error(2, 'AMOUNT');

        $this->properties['amount'] = rCurrency($requestedCurrency, (float)$this->properties['amount'], '');

        $requestedAmount = $this->properties['amount'];

        if (bccomp($requestedAmount, '0', getPrecision($requestedCurrency)) <= 0)
            return $this->_error(2, 'AMOUNT');

        switch ($requestedCurrency) {
            case 'cad':
            case 'usd':
                if (bccomp($requestedAmount, '0.25', getPrecision($requestedCurrency)) < 0)
                    return $this->_error(6, 'AMOUNT', '0.25', strtoupper($requestedCurrency));

            default:
                if (bccomp($requestedAmount, '0.0001', getPrecision($requestedCurrency)) < 0)
                    return $this->_error(6, 'AMOUNT', '0.0001', strtoupper($requestedCurrency));
        }

        // -- Optional fields
        if ($field = $this->input->post('identifier')) {
            if (!preg_match('/^[a-z0-9\-]+$/i', $field))
                return $this->_error(2, 'IDENTIFIER');

            $this->properties['identifier'] = $field;
        }

        if ($field = $this->input->post('description')) {
            $field = strip_tags(trim($field));

            if (strlen($field) > 512)
                return $this->_error(5, 'DESCRIPTION', 512);

            $this->properties['description'] = $field;
        }

        if ($field = $this->input->post('custom')) {
            if (!preg_match('/^[a-z0-9_\-,]+$/i', $field))
                return $this->_error(2, 'CUSTOM');

            $this->properties['custom'] = strip_tags($field);

            $customFields = explode(',', $field);

            foreach ($customFields as $_f) {
                if ($field = trim($this->input->post($_f)))
                    $this->properties['custom_' . $_f] = $field;
            }
        }

        // We've made it so far so we can carry on with the payment

        // Calculate the Crypto amount
        $this->load->model('trade_model');

        // Calculate the amount in Crypto
        if (in_array($requestedCurrency, $cryptoCurrencies) !== false) {
            $cryptoCurrency = $requestedCurrency;
            $cryptoAmount   = $requestedAmount;
        }
        else {
            $currency = in_array($store->currency, $cryptoCurrencies) !== false ? $store->currency : 'btc';

            $book = $currency . '_' . $requestedCurrency;

            $rate = $this->trade_model->getLastTradePrice($book);

            $cryptoCurrency = $currency;
            $cryptoAmount   = bcdiv($requestedAmount, $rate, getPrecision($cryptoCurrency));
        }

        // Calculate the payout if not Crypto
        if (in_array($store->currency, $cryptoCurrencies) !== false) {
            $payoutCurrency = $cryptoCurrency;
            $payoutAmount   = $cryptoAmount;
        }
        else if (in_array($requestedCurrency, $cryptoCurrencies) === false) {
            $payoutCurrency = $requestedCurrency;
            $payoutAmount   = $requestedAmount;
        }
        else {
            $currency = $store->currency;

            $book = $requestedCurrency . '_' . $currency;

            $rate = $this->trade_model->getLastTradePrice($book);

            $payoutCurrency = $currency;
            $payoutAmount   = bcmul($cryptoAmount, $rate, getPrecision($currency));
        }

        // Get an address for the coins to br transferred
        $address = $this->bitcoin_model->getStoreAddress($key);
        $data['address'] = $address;

        // QR Code URL
        $bitcoinPaymentUrl = 'bitcoin:' . $address . '?amount=' . $cryptoAmount;// . '&label=Payment to ' . $store->name;
        // Removed the label part as it seems the Blockchain app does not handle them no more - to be checked

        // Now let's save all that in the database
        $callData = array(
            'amount'          => $requestedAmount,
            'currency'        => $requestedCurrency,
            'crypto_amount'   => $cryptoAmount,
            'crypto_currency' => $cryptoCurrency,
            'payout_amount'   => $payoutAmount,
            'payout_currency' => $payoutCurrency,
            'address'         => $address,
            'bitcoin_url'     => $bitcoinPaymentUrl,
            'store_key'       => $this->properties['key'],
            'store'           => json_encode($store),
            'properties'      => json_encode($this->properties),
            'status'          => 'pending',
            'received'        => 0,
            'transactions'    => 0
        );

        if ($rate)
            $callData['rate'] = $rate;

        $code = $this->merchant_model->saveTemp($callData);

        redirect('merchant/process/' . $code);
	}

    public function process($code) {
        $callData = $this->merchant_model->getTemp($code);

        if ($callData) {
            $store = json_decode($callData->store);
            $this->store = $store;
        }

        if (!$callData || $callData->status != 'pending')
            return $this->_error(4);

        $this->merchant_model->checkExpiry($callData);

        $data['data']       = $callData;
        $data['store']      = $store;
        $data['properties'] = json_decode($callData->properties);

        $boxData = '<p class="text-info">Waiting...</p>';
        if (bccomp($callData->received, '0', getPrecision($callData->crypto_currency)) > 0) {
            $boxData .= '<p>We have received a total of <strong>' . $callData->received . ' <small>' . $callData->crypto_currency . '</small></strong> out of <strong>' . $callData->amount . ' <small>' . $callData->crypto_currency . '</small></strong>.</p>';
            $boxData .= '<p>Remainder to send: <strong>' . bcsub($callData->crypto_amount, $callData->received, getPrecision($callData->crypto_currency)) . ' <small>' . $callData->crypto_currency . '</small></strong>.</p>';
        }

        $data['boxData'] = $boxData;

        $this->layout->setTitle('Merchant')->view('merchant/index', $data);
    }

    public function cancel($code) {
        $callData = $this->merchant_model->getTemp($code);

        $this->merchant_model->cancel($code);

        $data['store'] = json_decode($callData->store);

        $this->layout->setTitle('Merchant - cancelled')->view('merchant/cancel', $data);
    }
}