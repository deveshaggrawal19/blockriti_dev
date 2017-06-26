<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

define('WORDS_FILE', FCPATH . md5('words') . '.json');

class Captcha {
    private $words;
    private $CI;

    public function __construct() {
        $this->CI    = &get_instance();
        $this->words = $this->getWords();
    }

    private function getWords() {
        return json_decode(file_get_contents(WORDS_FILE));
    }

    public function generate() {
        $word   = $this->words[rand(1, count($this->words)) - 1];
        $len    = strlen($word);
        $pos    = rand(1, $len);
        $letter = substr($word, $pos - 1, 1);

        $this->CI->session->set_flashdata('security', md5($letter . 'gibberish'));

        return array('word' => $word, 'position' => $pos);
    }

    public function check($post) {
        $session = $this->CI->session->flashdata('security');

        return md5($post . 'gibberish') == $session;
    }
}