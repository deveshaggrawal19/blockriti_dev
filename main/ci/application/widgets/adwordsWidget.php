<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Adwords extends Widget
{
    function run()
    {
        if (ENVIRONMENT != 'q-production') return;

        $this->render('adwords');
    }
}