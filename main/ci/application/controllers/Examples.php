<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Examples extends CI_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('layout');
        $this->load->library('redis');

        $this->layout->setLayout('shop');
    }

    public function quick() {
        $this->layout->setTitle('Advanced Pay Example')->view('examples/quick');
    }

	public function advanced() {
        $uniqueId = random_string();
        $data = array(
            'uniqueId' => $uniqueId
        );

        $this->session->set_userdata('unique_id', $uniqueId);

        $this->layout->setTitle('Advanced Pay Example')->view('examples/advanced', $data);
    }

    public function callback() {
        $uniqueId = $this->input->post('user_id');

        $key = 'test:data:' . $uniqueId;

        $this->redis->set($key, json_encode($this->input->post()));
        $this->redis->expire($key, ONE_DAY); // Expire after 1 day

        echo "ack";
    }

    public function cancel() {
        $this->session->set_flashdata('error', 'Your transaction was cancelled');
        redirect('/examples/advanced', 'redirect');
    }

    public function success() {
        $uniqueId = $this->session->userdata('unique_id');

        if (!$uniqueId)
            redirect('/examples/advanced', 'redirect');

        $key       = 'test:data:' . $uniqueId;
        $data['d'] = $this->redis->get($key);

        $this->layout->setTitle('Advanced Pay Example - Success')->view('examples/success_advanced', $data);
    }
}