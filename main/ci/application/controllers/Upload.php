<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Upload extends CI_Controller
{
    protected $_sGSBucketPath;
    public function __construct()
    {
        parent::__construct();
        $this->_setCORSHeaders();
        $this->load->library('redis');
        $sAppID = $this->config->item('google_app_id');
        $this->_sGSBucketPath = "gs://".$sAppID.".appspot.com";
    }

    private function _setCORSHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, AUTH_USER, AUTH_TOKEN, MERCHANT_CODE");
        header('Access-Control-Allow-Methods "PUT, GET, POST, DELETE, OPTIONS"');
    }


    public function photoID(){
        $this->_uploadFile();

    }

    public function UtilityBill(){
        $this->_uploadFile();
    }

    public function BankLetter(){
        $this->_uploadFile();
    }

    private function _uploadFile()
    {
        $name = 'uploaded_files';
        $file = $_FILES[$name];
        if ($file['error'] == 0) {
            $uid = generateRandomString(10, true);
            $size = (integer)$file['size'];
            $name = $file['name'];
            $temp_name = $file['tmp_name'];

            if($this->_getUserID() == 0 ){
                $this->_displayBadRequest(array('error' => 'Invalid User ID'));
            }
            if ($this->_checkFileSize($size) === FALSE) {
                $this->_displayBadRequest(array('error' => 'File Size Limit Exceeded'));
            }
            if ($this->_verifyTypes($name) === FALSE) {
                $this->_displayBadRequest(array('error' => 'File Type Not Allowed'));
            }
            $userID = $this->_getUserID();
            $options = ['gs' => ['acl' => 'public-read','Content-Type'=> $file['type']]];
            $context =  stream_context_set_default($options);
            file_put_contents($this->_sGSBucketPath . "/". $userID ."/" . $uid . '-' . strtolower($file['name']), file_get_contents($temp_name), 0, $context);

            $fileData = array(
                'userid' => $userID,
                'uid' => $uid,
                'filename' => $file['name'],
                'mime' => $file['type'],
                'size' => $file['size']
            );
            $this->load->model('user_document_model');

            $this->user_document_model->save($userID, $fileData);

            $this->_displaySuccess(array('msg' => "File Upload Successful"));
        } else {
            $this->_displayBadRequest(array('error' => $file['error']));
        }

    }

    private function _verifyTypes($sFileName){
        $aFileParts = explode('.', $sFileName);
        $ext = strtolower(trim($aFileParts[1]) );
        return in_array($ext, array('gif','jpg','jpeg','png','pdf') );
    }

    private function _checkFileSize($iFileSize){
        return (bool)((round($iFileSize/1024, 2) < 2048));
    }

    protected function _displayBadRequest($code) {
        header('Content-Type: application/json');
        header("HTTP/1.1 400 Bad Request");
        $this->_display($code);
    }

    protected function _displaySuccess($code) {
        header('Content-Type: application/json');
        header("HTTP/1.1 200 OK");
        $this->_display($code);
    }

    protected function _display($data) {
        $this->output->set_content_type('application/json')
            ->set_output(json_encode($data));
        $this->output->_display();

        exit();
    }

    private function _getUserID(){
        if(empty($_POST['user_id'])){
            return 0;
        }else{
            return (integer)$_POST['user_id'];
        }
    }

}
