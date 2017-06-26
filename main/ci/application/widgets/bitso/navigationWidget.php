<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Navigation extends Widget
{
    function run()
    {

        // ==========================================
        $this->load->model('trade_model');

        $books = $this->meta_model->getBooks();

        $args = func_get_args();
        if (!$args) {
            // Check the route
            $uriString = $this->router->uri->uri_string;
            foreach ($this->meta_model->books as $bookId=>$currencies) {
                if ((strpos($uriString, $currencies[0]) !== FALSE && strpos($uriString, $currencies[1]) !== FALSE) || (strpos($uriString, $bookId) !== FALSE)) {
                    $selected = $bookId;
                    break;
                }
            }

            if (!isset($selected))
                $selected = $this->config->item('default_book');
        }
        else $selected = $args[0];

        $listBooks = '';
        foreach (array_keys($books) as $book) {
            $string     = makeSafeCurrency(str_replace('_', ' / ', $book));
            $listBooks .= ($listBooks == '' ? '' : ' ') . ($selected == $book ? strtoupper($string) : '<a href="/stats/' . $book . '">' . $string . '</a>');
        }

        $data['list']       = $listBooks;
        $data['currencies'] = explode('_', $selected);

        $data['lastPrice'] = $this->trade_model->getLastTradePrice($selected);
        $data['volume']    = $this->trade_model->getRollingVolume($selected);

        $stats = $this->trade_model->getCurrentStats($selected);
        $data['low']  = $stats ? $stats->low : 0;
        $data['high'] = $stats ? $stats->high : 0;
        // ==========================================

        $user = $this->user;
        $data['user']       = $user;
        $data['avatar']     = $user != 'guest' ? $this->gravatar->get_gravatar($data['user']->email, null, 20, null, true) : '';
        $data['currencies'] = $this->meta_model->getAllCurrencies();

        $this->render('navigation', $data);
    }
}