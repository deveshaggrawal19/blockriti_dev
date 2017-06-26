<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Emergency extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        show_404();
        $this->load->library('redis');
        $this->load->model('permissions_model');
    }

	public function maintenance() {
        if ($this->permissions_model->get('website') === 'enabled')
            redirect('/', 'refresh');

        $this->load->view('maintenance');
	}

    public function restore_site() {
        $this->permissions_model->set('website', 'enabled');

        redirect('/', 'refresh');
    }
}