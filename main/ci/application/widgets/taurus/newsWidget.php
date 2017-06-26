<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class News extends Widget {
    function run($amount = 3, $page = 'home') {
        $this->load->model('news_model');

        $this->news_model->getCount();
        $data['news'] = $this->news_model->getSubset(1, $amount);
        $data['page'] = $page;

        $this->render('news', $data);
    }
}