<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include_once('Redis_model.php');

class Note_model extends Redis_model {
    public $id;
    public $userId;
    public $message;
    
    public $entries;
    
    public function addNote($data) {
        $id = $this->newId("notes");
        $this->redis->sadd("user:".$data['userId'].":notes", $id);
        $this->redis->hmset("note:"._numeric($id), $data);
    }
    
    public function removeNote($id) {
        $data = $this->get(_numeric($id));
        $this->redis->srem("user:".$data->userId.":notes", $id);
        $this->redis->del("note:"._numeric($id));
    }
    
    public function getNotesByUser($userId) {
        $entries = $this->redis->smembers("user:".$userId.":notes");
        if (!$entries)
            return false;
        
        $this->entries = array();
        foreach ($entries as $entryId) {
            $data = $this->get(_numeric($entryId));
            if ($data)
                $this->entries[] = $data;
        }
        return $this->entries;
    }
    
    public function getCountNotesByUser($userId) {
        $entities = $this->getNotesByUser($userId);
        if(!$entities){
            return 0;
        }
        return count($entities);
    }
    
    public function get($id) {
        $object = $this->flatten($this->redis->hgetall('note:' . $id));

        if ($object)
            $object->id = $id;

        return $object;
    }
    
    public function newId() {
        return parent::newRandomId('note');
    }
}
?>