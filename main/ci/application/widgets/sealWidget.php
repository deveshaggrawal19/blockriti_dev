<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Seal extends Widget
{
    function run()
    {
        if ($this->router->class != 'main' || $this->router->method != 'index') return;

        $this->render('seal');
    }
}