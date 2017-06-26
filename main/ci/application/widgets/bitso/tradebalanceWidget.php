<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tradebalance extends Widget
{
    function run()
    {

        $this->load->model('trade_model');

        $books = $this->meta_model->getBooks();
        $args = func_get_args();

        if (!$args) {
            // Check the route
            $uriString = $this->router->uri->uri_string;
            foreach ($books as $bookId=>$currencies) {
                if ((strpos($uriString, $currencies[0]) !== FALSE && strpos($uriString, $currencies[1]) !== FALSE) || (strpos($uriString, $bookId) !== FALSE)) {
                    $selected = $bookId;
                    break;
                }
            }

            if (!isset($selected))
                $selected = $this->config->item('default_book');
        }
        else $selected = $args[0];

        $data['currencies'] = array_reverse(explode('_', $selected));
        //$data['currencies'] = $this->meta_model->getAllCurrencies();

        // ==========================================

        $data['user'] = $this->user;

        $this->render('tradebalance', $data);
    }
}