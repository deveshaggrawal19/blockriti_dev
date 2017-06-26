<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MarketStats extends Widget {
    function run() {
        $this->load->model('trade_model');

        $books = $this->meta_model->getBooks();

        $args = func_get_args();
        if (!$args) {
            // Check the route
            $uriString = $this->router->uri->uri_string;
            foreach ($books as $bookId=>$currencies) {
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
        }
        else $book = $args[0];

        $data['currencies'] = explode('_', $book);
        $data['lastPrice'] = $this->trade_model->getLastTradePrice($book);
        $data['volume']    = $this->trade_model->getRollingVolume($book);

        $days = $this->trade_model->twentyFourHourChange($book);

        if (!$days)
            $days = array(5948, 5948);

        $data['change'] = $days[0] - $days[1];

        $perc = ($days[0] - $days[1]) / $days[1];

        if (empty($perc)) {
            $data['perc'] = 0;
        } else {
            $perc = $perc * 100;
            $data['perc'] = $this->roundSigDigs($perc, 2);
        }

        $data['arrow'] = "";
        if ($data['change']<0) {
            $data['arrow'] = "market-down";
        }
        if ($data['change']>0) {
            $data['arrow'] = "market-up";
        }

        $totalxbt = $this->trade_model->getTotalBTC();
        $data['market_cap'] = $this->trade_model->getMarketCap();//bcmul($totalxbt, $data['lastPrice'], 2);
        $data['total_xbt']  = $totalxbt;

        $stats = $this->trade_model->getCurrentStats($book);
        $data['low']  = $stats ? $stats->low : 0;
        $data['high'] = $stats ? $stats->high : 0;

        $this->render('marketstats', $data);
    }

    function roundSigDigs($number, $sigdigs) {
        $neg = 1;
        if ($number < 0) {
            $number = abs($number);
            $neg = -1;
        }

        $multiplier = 1;
        while ($number < 0.1) {
            $number *= 10;
            $multiplier /= 10;
        }

        while ($number >= 1) {
            $number /= 10;
            $multiplier *= 10;
        }

        return round($number, $sigdigs) * $multiplier * $neg;
    }
}