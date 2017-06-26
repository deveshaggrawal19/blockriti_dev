<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Migrate_model extends Redis_model {

    public function __construct() {
        parent::__construct();
    }

    public function getAlreadyRun() {
        return $this->redis->smembers('migrations');
    }

    public function canRun($key) {
        if ($key == 'index') return TRUE; // to prevent forever loops

        $isMember = $this->redis->sismember('migrations', $key);
        if ($isMember == '1') return FALSE; // already exists - eject!

        $this->redis->sadd('migrations', $key);
        return TRUE;
    }

    public function setLimits() {
        $limits = array(
            'min_rate'   => '10.00',
            'max_rate'   => '5000.00',
            'min_amount' => '0.00500000',
            'max_amount' => '1000.00000000',
            'min_value'  => '1.00',
            'max_value'  => '1000000.00'
        );

        $this->redis->hmset('order:btc_usd:limits', $limits);
        $this->redis->hmset('order:btc_cad:limits', $limits);
    }

    public function setFees() {
        $this->redis->set('trades:fee', '0.005');
    }

    public function feeStructure() {
        $this->redis->zadd('fee:structure', 10,     0.0050);
        $this->redis->zadd('fee:structure', 25,     0.0045);
        $this->redis->zadd('fee:structure', 50,     0.0040);
        $this->redis->zadd('fee:structure', 100,    0.0035);
        $this->redis->zadd('fee:structure', 250,    0.0030);
        $this->redis->zadd('fee:structure', 500,    0.0025);
        $this->redis->zadd('fee:structure', 1000,   0.0020);
        $this->redis->zadd('fee:structure', 2000,   0.0015);
        $this->redis->zadd('fee:structure', 3000,   0.0010);
        $this->redis->zadd('fee:structure', 4000,   0.0005);
        $this->redis->zadd('fee:structure', 999999, 0);
    }

    public function updateGraph() {
        $keys = $this->redis->keys('stats:trades:*');
        foreach ($keys as $key)
            $this->redis->del($key);

        $keys = $this->redis->keys('stats:user:*');
        foreach ($keys as $key)
            $this->redis->del($key);

        $this->caching_model->delete('user:*:volume');
        $this->caching_model->delete('orders:user:*:*');

        $books = $this->meta_model->getBooks();
        foreach ($books as $book => $bookarr) {
            $this->caching_model->delete('trades:graph:' . $book . ':*');

            $tradeIds = $this->redis->lrange('trades:' . $book, 0, -1);
            $tradeIds = array_reverse($tradeIds);
            foreach ($tradeIds as $tradeId) {
                $tradeId = _numeric($tradeId);
                $trade = $this->trade_model->get($tradeId);
                if ($trade) {
                    $this->trade_model->addTradeToGraph($book, (array)$trade);
                }
            }
        }
    }

    public function importHistoricalData() {
        foreach (array('cad') as $book) {
            $data = array_map('str_getcsv', file($book . "_history.csv"));

            $graph = array();

            foreach ($data as $row) {
                $date  = substr($row[0], 0, strpos($row[0], ' '));
                $open  = $row[1];
                $high  = $row[2];
                $low   = $row[3];
                $close = $row[4];
                $value = $row[5];

                // Check if the date has already been set by our system
                $key = 'stats:trades:btc_' . $book . ':' . $date;
                if (!$this->keyExists($key)) {
                    $key = $key . ':imported';
                    $stat = array(
                        'low'     => $low,
                        'high'    => $high,
                        'average' => $value,
                        'volume'  => 0,
                        'open'    => $open,
                        'last'    => $close
                    );

                    $this->redis->hmset($key, $stat);
                }

                $graph[] = $key;
            }

            $this->redis->del('stats:trades:btc_' . $book . ':graph');
            $this->redis->rpush('stats:trades:btc_' . $book . ':graph', $graph);
        }
    }

    public function setLimits() {
        $this->deposit_model->setLimits('ep', 'usd', 50, 5000);
        $this->deposit_model->setLimits('pz', 'usd', 50, 5000);
        $this->deposit_model->setLimits('pz', 'cad', 50, 5000);
    }
}