<?php

class MY_Controller extends CI_Controller {

    public $user;
    public $language;

    protected $userId;

    public function __construct() {
        parent::__construct();
        show_404();
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->library('layout');
        $this->load->library('redis');
        $this->load->library('api_security');
        $this->load->library('email');

        $this->load->model('admin_model');
        $this->load->model('user_model');
        $this->load->model('user_balance_model');
        $this->load->model('email_queue_model');
        $this->load->model('bitcoin_model');
        $this->load->model('order_model');
        $this->load->model('trade_model');
        $this->load->model('meta_model');
        $this->load->model('permissions_model');
        $this->load->model('caching_model');
        $this->load->model('referral_model');
        $this->load->model('notification_model');

        $this->load->helper('widget');

        // Force a redirect when the session is either invalid or expired
        $this->user = $this->user_model->loadFromSession();
        
        if ($this->user !== 'guest' && $this->user->_status == 'authenticated' && ($this->router->method != 'authenticate' && $this->router->method != 'logout')) {
            redirect('/authenticate', 'refresh');
        }
        

        $website = $this->permissions_model->get('website');
        if ($website == 'disabled' && $this->router->class != 'admin')
            redirect('/maintenance');

        if (defined('TEMPLATE'))
            $this->layout->setTemplate(TEMPLATE);

        // Check if we get a switch on the query string
        $language = $this->input->get('l');

        // If it is a valid switch, then assign the language
        if ($language && in_array($language, array('en', 'es')) !== false) {
            if ($this->user !== 'guest' && $this->user->language != $language)
                $this->user_model->setLanguage($language);

            $this->session->set_userdata('language', $language);
        }
        // Check in the session if this has already been set
        else if ($this->session->userdata('language'))
            $language = $this->session->userdata('language');
        // Or check if the user has a preference
        else if ($this->user !== 'guest')
            $language = $this->user->language;
        // Otherwise default to the default language set in the config
        else $language = $this->config->item('default_lang');

        $this->lang->load('menu', $language);
        $this->lang->load('views', $language);
        $this->lang->load('controllers', $language);
        $this->lang->load('models', $language);
        $this->lang->load('misc', $language);
        $this->lang->load('forms', $language);

        $this->language = $language;

        // Pretty errors
        $this->form_validation->set_error_delimiters('<p class="form-control-static inline-error">', '</p>');

        $this->form_validation->set_message('required',           $this->lang->line('e_required'));
        $this->form_validation->set_message('min_length',         $this->lang->line('e_min_length'));
        $this->form_validation->set_message('max_length',         $this->lang->line('e_max_length'));
        $this->form_validation->set_message('exact_length',       $this->lang->line('e_exact_length'));
        $this->form_validation->set_message('matches',            $this->lang->line('e_matches'));
        $this->form_validation->set_message('valid_email',        $this->lang->line('e_invalid'));
        $this->form_validation->set_message('less_than',          $this->lang->line('e_less_than'));
        $this->form_validation->set_message('greater_than',       $this->lang->line('e_greater_than'));
        $this->form_validation->set_message('numeric',            $this->lang->line('e_invalid'));
        $this->form_validation->set_message('is_natural_no_zero', $this->lang->line('e_invalid'));
        $this->form_validation->set_message('is_unique',          $this->lang->line('e_is_unique'));

        $this->userId = $this->user !== 'guest' ? _numeric($this->user->_id) : null;

        $code = $this->input->get('ref');

        if ($code) {
            $referrer = $this->referral_model->findByCode($code);
            if ($referrer) {
                $cookie = array(
                    'name'   => 'referrer',
                    'value'  => $code,
                    'expire' => '86500'
                );

                $this->input->set_cookie($cookie);
            }
        }
    }
}