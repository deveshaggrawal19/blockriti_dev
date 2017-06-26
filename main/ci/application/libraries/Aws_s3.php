<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

use Aws\S3\S3Client;

class Aws_s3 {

    private $client;
    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('creds_aws');
        $this->client = S3Client::factory(array(
            'key'    => $this->CI->config->item('aws_key'),
            'secret' => $this->CI->config->item('aws_secret'),
            'region' => $this->CI->config->item('aws_region'),
        ));
    }

    public function __call($name, $arguments = null) {
        if (!property_exists($this, $name)) {
            return call_user_func_array(array($this->client, $name), $arguments);
        }
    }
}