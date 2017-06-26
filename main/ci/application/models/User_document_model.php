<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class User_document_model extends Redis_model {

    public function __construct() {
        parent::__construct();
    }

    public function save($userId, $details) {
        $id = $this->newId('document');

        $data = array(
            '_id'      => $id,
            'client'   => $userId,
            'uid'      => $details['uid'],
            'filename' => $details['filename'],
            'mime'     => $details['mime'],
            'size'     => $details['size'],
            'ip'       => getIp(),
            'status'   => 'pending',
            'uploaded' => $this->now
        );

        $this->redis->hmset($id, $data);
        $this->redis->rpush('user:' . $userId . ':documents', $id);
        $this->redis->rpush('documents:pending', $id);
    }

    public function get($documentId) {
        $object = $this->flatten($this->redis->hgetall('document:' . $documentId));

        if ($object)
            $object->id = $documentId;

        return $object;
    }

    public function update($documentId, $action) {
        $key = 'document:' . $documentId;

        $data = array(
            'status'  => $action,
            'updated' => $this->now
        );

        $this->redis->hmset($key, $data);
        $this->redis->lrem('documents:pending', 0, $key);
    }

    public function getPendingCount() {
        return $this->redis->llen('documents:pending');
    }

    public function getPendingSubset() {
        $documents = array();

        $documentIds = $this->redis->lrange('documents:pending', 0, -1);
        foreach ($documentIds as $documentId) {
            $documentId = _numeric($documentId);
            $document   = $this->get($documentId);

            $document->user = $this->user_model->getUser($document->client);

            $documents[] = $document;
        }

        return $documents;
    }

    public function getForUser($userId) {
        $documentIds = $this->redis->lrange('user:' . $userId . ':documents', 0, -1);

        $result = array();
        foreach ($documentIds as $documentId) {
            $documentId = _numeric($documentId);
            $result[] = $this->get($documentId);
        }

        return $result;
    }
}