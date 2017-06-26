<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migrate extends CI_Controller
{
    public function __construct() {
        parent::__construct();

        $this->load->model('migrate_model');
        $this->load->library('redis');
        
        $this->setupBitgo();

        // Migration will only be run once and cannot be re-run
        // So we use the name of the function name as a key in a set.
        // If it is in that set, it has already been run and we redirect to the homepage
        if ($this->router->method != 'test') {
            if (!method_exists($this, $this->router->method) || !$this->migrate_model->canRun($this->router->method)) {
                redirect('/migrate', 'refresh');
            }
        }
    }

    public function __destruct() {
        if ($this->router->method != 'index' && $this->router->method != 'test')
            redirect('/migrate', 'refresh');
    }

    public function index() {
        $methods = get_class_methods($this);
        $alreadyRun = $this->migrate_model->getAlreadyRun();

        $left = array();

        foreach ($methods as $method) {
            // Remove methods that are proprietary to PHP
            if (in_array($method, array('__construct', '__destruct', 'index', 'get_instance')) !== FALSE)
                continue;

            if (in_array($method, $alreadyRun))
                continue;

            $left[] = $method;
        }

        if (count($left) == 0) {
            redirect('/', 'refresh');
            return;
        }

        echo '<h2>Migrations to be run</h2>';
        echo '<ul>';
        foreach ($left as $method) {
            echo '<li><a href="/migrate/' . $method . '">' . $method . '</a></li>';
        }
        echo '</ul>';
    }

    public function setup() {
        $this->load->model('bitcoin_model');
        $this->load->model('meta_model');

        $this->bitcoin_model->setup();
        $this->meta_model->setup(array('cad'));
        $this->migrate_model->setLimits();
        $this->migrate_model->setFees();
        $this->migrate_model->feeStructure();
        //todo us bitcoind used for payment verification?
        $this->redis->set('bitcoind:lastblock', '');
        $this->redis->set('hotwallet:maximum', '10');
        $this->redis->set('trades:commission', '0.1');
    }
    
    public function setupBitgo() {
        $this->load->model('bitcoin_model');
        $this->bitcoin_model->reloadAddress();
    }

    public function updateGraph() {
        $this->load->model('meta_model');
        $this->load->model('trade_model');
        $this->load->model('caching_model');

        $this->migrate_model->updateGraph();
    }

    public function importHistoricalData() {
        $this->migrate_model->importHistoricalData();
    }

    public function setLimits() {
        $this->load->model('deposit_model');

        $this->migrate_model->setLimits();
    }
}