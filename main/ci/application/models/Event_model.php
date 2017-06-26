<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Event_model extends Redis_model {

    private $entries;

    public function __construct() {
        parent::__construct();
    }

    public function get($id) {
        $id   = _numeric($id);
        $log = $this->flatten($this->redis->hgetall('event:' . $id));

        if ($log)
            $log->id = $id;

        return $log;
    }

    public function add($userId, $type) {
        if (!in_array($type,array(
            'login',
            'logout',
            'loginfail',
            'vcomplete',
            'vfail',
            'register',
            '2faon',
            '2faoff',
            '2faproblem',
            'pwchange',
            'ipchange',
            'setpin'
        )))
            return;

        $id = $this->newId('event');
        $key = 'events:' . $type;

        $created = $this->now;

        $data = array(
            '_id'      => $id,
            '_created' => $created,
            'client'   => $userId,
            'type'     => $type,
            'ip'       => getip(),
            'ua'       => $this->input->user_agent()
        );

        $this->redis->hmset($id, $data);

        $this->redis->zadd($key, $created, $id);
        $this->redis->zadd('events:all', $created, $id);
        $this->redis->zadd('user:' . $userId . ':events:all', $created, $id);
        $this->redis->zadd('user:' . $userId . ':events:' . $type, $created, $id);

        $this->caching_model->delete($key);
        $this->caching_model->delete('events:all');
        $this->caching_model->delete('user:' . $userId . ':events:all');
        $this->caching_model->delete('user:' . $userId . ':events:' . $type);
    }

    public function getCount($type = 'all') {
        $this->entries = $this->caching_model->get('events:' . $type);
        if (!$this->entries) {
            $this->entries = $this->redis->zrevrange('events:' . $type, 0, -1);
            $this->caching_model->save($this->entries, ONE_DAY);
        }
        return count($this->entries);
    }

    public function getSubset($page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage;
        $result = array();

        for ($i = $start; $i < $end; $i++) {
            if (!isset($this->entries[$i])) break;

            $itemId = _numeric($this->entries[$i]);
            $event = $this->get($itemId);

            if (!empty($event->client))
                $event->user = $this->user_model->getUser($event->client);

            $result[] = $event;
        }
        return $result;
    }

    public function getCountForUser($userId, $type = 'all') {
        $this->entries = $this->caching_model->get('user:' . $userId . ':events:' . $type);
        if (!$this->entries) {
            $this->entries = $this->redis->zrevrange('user:' . $userId . ':events:' . $type, 0, -1);
            $this->caching_model->save($this->entries, ONE_DAY);
        }
        return count($this->entries);
    }

    public function getSubsetForUser($page = 1, $perPage = 30) {
        $start = ($page - 1) * $perPage;
        $end   = $start + $perPage;

        $result = array();
        for ($i = $start; $i < $end; $i++) {
            if (!isset($this->entries[$i])) break;

            $_entryId = $this->entries[$i];
            $event = $this->get($_entryId, true);

            $result[] = $event;
        }

        return $result;
    }
}