<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Meta_model extends Redis_model {

    public $books;
    public $currencies;
    public $cryptoCurrencies;
    public $fiatCurrencies;

    public function __construct() {
        parent::__construct();
    }

    public function getBooks() {
        $books = $this->redis->lrange('books', 0, -1);

        foreach ($books as $book)
            $this->books[$book] = explode('_', $book);

        return $this->books;
    }

    public function getAllCurrencies() {
        $this->currencies = array_merge($this->getCryptoCurrencies(), $this->getFiatCurrencies());

        return $this->currencies;
    }

    public function getCryptoCurrencies() {
        if (!$this->cryptoCurrencies)
            $this->cryptoCurrencies = $this->redis->smembers('cryptocurrencies');

        sort($this->cryptoCurrencies);

        return $this->cryptoCurrencies;
    }

    public function getFiatCurrencies() {
        if (!$this->fiatCurrencies)
            $this->fiatCurrencies = $this->redis->smembers('currencies');

        rsort($this->fiatCurrencies);

        return $this->fiatCurrencies;
    }

    public function setup($defCurrency) {
        if (!is_array($defCurrency))
            $defCurrency = array($defCurrency);

        $this->redis->sadd('cryptocurrencies', 'btc');

        foreach ($defCurrency as $currency) {
            $this->redis->sadd('currencies', $currency);

            $this->redis->lpush('books', 'btc_' . $currency);
        }
    }

    public function setLimits($book, $data) {
        return $this->redis->hmset('order:' . $book . ':limits', $data);
    }

    public function getLimits($book) {
        return $this->flatten($this->redis->hgetall('order:' . $book . ':limits'));
    }
}