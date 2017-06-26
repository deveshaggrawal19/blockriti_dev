<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Twitter extends Widget
{
    function run()
    {
        $data = array(
            'width'  => 350,
            'height' => 500
        );

        $this->render('twitter', $data);
    }
}