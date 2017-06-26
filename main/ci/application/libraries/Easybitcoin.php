<?php

class Easybitcoin {
    // Configuration options
    private $username;
    private $password;
    private $proto;
    private $host;
    private $port;
    private $url;
    private $certificate;

    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;
    private $id = 0;

    public function __construct() {
        $CI = &get_instance();

        $CI->load->config('creds_easybitcoin', TRUE);
        $config = $CI->config->item('creds_easybitcoin');

        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->host     = $config['host'];
        $this->port     = $config['port'];
        $this->url      = $config['url'];
        $this->proto    = 'http';

        $certificate = $config['certificate'];
        if ($certificate)
            $this->setSSL($certificate);
    }

    private function setSSL($certificate = null) {
        $this->proto       = 'https'; // force HTTPS
        $this->certificate = $certificate;
    }

    public function __call($method, $params) {
        $this->status       = null;
        $this->error        = null;
        $this->raw_response = null;
        $this->response     = null;

        // If no parameters are passed, this will be an empty array
        $params = array_values($params);

        // The ID should be unique for each call
        $this->id++;

        // Build the request, it's ok that params might have any empty array
        $request = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id'     => $this->id
        ));

        // Build the cURL session
        $curl    = curl_init("{$this->proto}://{$this->username}:{$this->password}@{$this->host}:{$this->port}/{$this->url}");
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request
        );

        if ($this->proto == 'https') {
            if ($this->certificate != '') {
                // Certificate should be placed in the root
                $options[CURLOPT_CAINFO] = $this->certificate;
                $options[CURLOPT_CAPATH] = DIRNAME($this->certificate);
            }
            else {
                $options[CURLOPT_SSL_VERIFYPEER] = FALSE;
            }
        }

        curl_setopt_array($curl, $options);

        // Execute the request and decode to an array
        $this->raw_response = curl_exec($curl);
        $this->response     = json_decode($this->raw_response, true);

        // If the status is not 200, something is wrong
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        // If there was no error, this will be an empty string
        $curl_error = curl_error($curl);

        curl_close($curl);

        if (!empty($curl_error)) {
            $this->error = $curl_error;
        }

        if ($this->response['error']) {
            // If bitcoind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        }
        elseif ($this->status != 200) {
            // If bitcoind didn't return a nice error message, we need to make our own
            switch ($this->status) {
                case 400:
                    $this->error = 'HTTP_BAD_REQUEST';
                    break;

                case 401:
                    $this->error = 'HTTP_UNAUTHORIZED';
                    break;

                case 403:
                    $this->error = 'HTTP_FORBIDDEN';
                    break;

                case 404:
                    $this->error = 'HTTP_NOT_FOUND';
                    break;
            }
        }

        if ($this->error)
            return false;

        return $this->response['result'];
    }
}