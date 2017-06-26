<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class News_model extends Redis_model {
    private $entries;

    public function get($id) {
        $id   = _numeric($id);
        $news = $this->flatten($this->redis->hgetall('newsitem:' . $id));

        if ($news)
            $news->id = $id;

        return $news;
    }

    public function save($data) {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $this->redis->hmset('newsitem:' . $id, $data);
        }
        else {
            $id = $this->newId('newsitem');

            $data['_id'] = $id;
            $this->redis->hmset($id, $data);

            $key = 'news:' . $data['language'];

            $this->redis->zadd($key, $data['published'], $id);

            $this->caching_model->delete($key);
        }

        $this->munge();

        return true;
    }

    public function delete($id) {
        $data = $this->get($id);

        $key = 'news:' . $data->language;

        $this->redis->zrem($key, $data->_id);
        $this->redis->del($data->_id);

        $this->caching_model->delete($key);

        $this->munge();
    }

    public function getCount($language = 'en') {
        $this->entries = $this->caching_model->get('news:' . $language);
        if (!$this->entries) {
            $this->entries = $this->redis->zrevrange('news:' . $language, 0, -1);

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

            $result[] = $this->get($this->entries[$i]);
        }

        return $result;
    }

    private function munge() {
        // This function creates an aggregated sorted set with all the languages used
        $keys = array();
        foreach (array_keys(languages()) as $lang) {
            $key = 'news:' . $lang;
            if ($this->redis->exists($key)) {
                $keys[] = $key;
            }
        }

        call_user_func_array(
            array($this->redis, "zunionstore"),
            array_merge(array("news:all", count($keys)), $keys)
        );

        $this->caching_model->delete('news:all');
    }
}