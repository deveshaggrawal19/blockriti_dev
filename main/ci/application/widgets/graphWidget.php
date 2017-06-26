<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Graph extends Widget {
    function run() {
        $this->load->model('trade_model');

        $books = $this->meta_model->getBooks();

        $args = func_get_args();

        if (!$args) {
            // Check the route
            $uriString = $this->router->uri->uri_string;
            foreach ($books as $bookId => $currencies) {
                if ((strpos($uriString, $currencies[0]) !== FALSE && strpos($uriString, $currencies[1]) !== FALSE) || (strpos($uriString, $bookId) !== FALSE)) {
                    $book = $bookId;
                    break;
                }
            }

            if (!isset($book)) {
                if (!$major = $this->session->userdata('major')) {
                    $major = $this->config->item('default_major');
                }

                if (!$minor = $this->session->userdata('minor')) {
                    $minor = $this->config->item('default_minor');
                }

                $book = $major . '_' . $minor;
            }
        } else $book = $args[0];

        $plots = $this->trade_model->getGraph($book, 10000);
        $cdata = array();

        foreach ($plots as $date => $plotData) {
            $item = array(
                'date'   => $date,
                'value'  => $plotData['value'],
                'volume' => $plotData['volume'],
                'low'    => $plotData['low'],
                'high'   => $plotData['high'],
                'open'   => $plotData['open'],
                'close'  => $plotData['close']
            );

            $cdata[] = $item;
        }

        $data["chartdata"] = json_encode($cdata);

        $this->render('graph', $data);

        return;
    }
}