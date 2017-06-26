<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Layout {

    private $CI;
    private $layout   = 'default';
    private $template = null;
    private $title    = '';
    private $js       = '';
    private $css      = array();

    public function __construct() {
        $this->CI = & get_instance();
    }

    public function setTemplate($template) {
        $this->template = $template;

        return $this;
    }

    public function setLayout($layout) {
        $this->layout = $layout;

        return $this;
    }

    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    public function view($view, $data = null, $return = false) {
        $layout = 'layouts/' . $this->layout;

        // Check if there is a template folder
        if ($this->template) {
            // if the templated layout exist then use it
            if (file_exists (APPPATH . "views/{$this->template}/layouts/" . $this->layout . EXT))
                $layout = $this->template . '/layouts/' . $this->layout;

            // if the templated view exist then use it
            if (file_exists(APPPATH . "views/{$this->template}/$view" . EXT))
                $view = $this->template . '/' . $view;
        }

        $layoutData                = array();
        $layoutData['__title__']   = $this->title;
        $layoutData['__css__']     = $this->processStyleSheet();
        $layoutData['__js__']      = $this->js;
        $layoutData['__content__'] = $this->CI->load->view($view, $data, true);

        $siteDomain = $this->CI->config->item('domain_name');
        $this->CI->output->set_header("Strict-Transport-Security: max-age=31536000; includeSubdomains");
        $this->CI->output->set_header("Content-Security-Policy: default-src 'self' wss://* ".$siteDomain.":8081 whitebarter.freshdesk.com fonts.gstatic.com *.bootstrapcdn.com ;img-src 'self' data: *;style-src 'self' *.bootstrapcdn.com *.cloudflare.com *.amazonaws.com *.googleapis.com 'unsafe-inline';script-src 'self' *.google.com *.google-analytics.com *.gstatic.com maps.google.com *.amazonaws.com *.cloudflare.com *.googleapis.com ".$siteDomain.":8081 'unsafe-inline' 'unsafe-eval';frame-src 'self' *.google.com *.freshdesk.com");
        //$this->CI->output->set_header("Content-Security-Policy: default-src 'self' ws://* ".$siteDomain.":8081 whitebarter.freshdesk.com fonts.gstatic.com *.bootstrapcdn.com ;img-src *;style-src 'self' *.bootstrapcdn.com *.cloudflare.com *.amazonaws.com *.googleapis.com 'unsafe-inline';script-src 'self' *.google.com *.google-analytics.com *.gstatic.com maps.google.com *.amazonaws.com *.cloudflare.com *.googleapis.com ".$siteDomain.":8081 'unsafe-inline' 'unsafe-eval';frame-src 'self' *.google.com *.freshdesk.com");
        
        //$this->CI->output->set_header('Public-Key-Pins: pin-sha256="base64+primary=="; pin-sha256=X3pGTSOuJeEVw989IJ/cEtXUEmy52zs1TZQrU06KUKg="; pin-sha256="MHJYVThihUrJcxW6wcqyOISTXIsInsdj3xK8QrZbHec="; max-age=5184000; includeSubDomains');
        $this->CI->output->set_header('Public-Key-Pins: pin-sha256="aRcbWw/7hKEreDOqP63rq3YdzPf8QLXJxbWRsQpBXWA="; pin-sha256="tSDBmcbVWEPIt5WyvGIf2UDZX0K6iEmiA39cF+SeE3A="; max-age=5184000; includeSubDomains');
        
        $this->CI->output->set_header("X-Frame-Options: SAMEORIGIN");
        $this->CI->output->set_header("X-XSS-Protection: 1;mode=block");
        $this->CI->output->set_header("X-Content-Type-Options: nosniff");
        
        return $this->CI->load->view($layout, $layoutData, $return);
    }

    public function partialView($view, $data = null) {
        if ($this->template) {
            // if the templated view exist then use it
            if (file_exists(APPPATH . "views/{$this->template}/$view" . EXT))
                $view = $this->template . '/' . $view;
        }

        return $this->CI->load->view($view, $data, true);
    }

    public function js($script) {
        if ((substr($script, 0, 4) == 'http') || ($script{0} == '/')) $this->js .= '<script type="text/javascript" src="' . $script . '"></script>';
        else
            $this->js .= '<script type="text/javascript">' . $script . '</script>';

        return $this;
    }

    // ieVersion can be any of the following:
    // IE, !IE, IE 6, IE 7, ..., IE lt 7, IE gt 7, ..., IE lte 7, IE gte 7, etc...
    public function css($style, $ieVersion = null) {
        if ((substr($style, 0, 4) == 'http') || ($style{0} == '/')) $css = '<link rel="stylesheet" href="' . $style . '" type="text/css" media="all">';
        else $css = '<style type="text/css" media="all">' . $style . '</style>';

        $this->css[$ieVersion ? $ieVersion : ''][] = $css;

        return $this;
    }

    private function processStyleSheet() {
        $res = '';
        foreach ($this->css as $ver => $css) {
            $temp = implode("\n", $css);

            if ($ver) $temp = "\n<!--[if $ver]>\n" . $temp . "\n<![endif]-->";
            $res .= $temp . "\n";
        }

        return $res;
    }
}