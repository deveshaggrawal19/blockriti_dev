<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Analytics extends Widget
{
    function run()
    {
        return;

        $code = $this->config->item('google_analytics_code');
        $site = $this->config->item('google_analytics_site');

        if (!$code || !$site) return;

        $data = array(
            'code' => $code,
            'site' => $site
        );

        $this->render('analytics', $data);
    }
}