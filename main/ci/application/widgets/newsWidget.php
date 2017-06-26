<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class News extends Widget {
    function run($amount = 3, $page = 'home') {
        $this->load->model('news_model');

        $count = $this->news_model->getCount();
        $p = $this->input->get('page');
        if (!$p)
            $p = 1;

        $maxPages = ceil($count / 10);

        $data['news'] = $this->news_model->getSubset($p, $amount);
        $data['page'] = $page;
        $data['next'] = $p < $maxPages ? $p + 1 : 0;
        $data['prev'] = $p > 1 ? $p - 1 : 0;

        $this->render('news', $data);
    }
}