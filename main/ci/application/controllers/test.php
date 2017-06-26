<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->library('redis');
    }

	public function index() {
        for ($i = 1; $i < 1000; $i++) {
            $this->redis->set('test:' . $i, milliseconds());
        }

        for ($i = 1; $i < 1000; $i++) {
            var_dump($this->redis->get('test:' . $i));
        }

        for ($i = 1; $i < 1000; $i++) {
            $this->redis->del('test:' . $i);
        }

        $words = explode(' ', 'Lorem ipsum dolor sit amet consectetur adipiscing elit Vivamus eu augue dictum sagittis arcu ac condimentum justo Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Sed nec tellus vulputate ultricies sapien id mattis velit Nunc sed ligula urna Cras tristique et ante ut varius Ut laoreet elit in nunc posuere ac dictum risus congue Nunc mi ex placerat sed sollicitudin ut rhoncus sit amet neque Duis id rhoncus risus Mauris ullamcorper ut sapien quis vehicula Aenean malesuada fermentum justo Nam consectetur lacinia enim non egestas');
        $keys  = array();
        for ($i = 0; $i < 100; $i++) {
            $key = 'test:' . milliseconds();

            $numKeys = rand(5, 30);
            $data = $words;
            shuffle($data);
            $arrayKeys = array_slice($data, 0, $numKeys);

            $data = $words;
            shuffle($data);
            $arrayValues = array_slice($data, 0, $numKeys);

            $data = array_combine($arrayKeys, $arrayValues);
            $this->redis->hmset($key, $data);

            echo $key;
            for ($j = 0; $j < rand(10, 30); $j++) {
                var_dump($this->redis->hgetall($key));
            }

            $keys[] = $key;
        }

        foreach ($keys as $key) {
            $this->redis->del($key);
        }
    }

    public function jumio() {
        //$this->logging_model->log("this is a test","jumio");

        $amt = '300.00';
        $avail = '3.01';

        // Ripple API withdrawal
        if (bccomp($amt, $avail, 2) > 0) echo 'Caught test #1<br/>';

        // Order placement
        if (bccomp($avail, $amt, 2) === -1) echo 'Caught test #2<br/>';
    }
}