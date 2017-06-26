<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Jumio {

    private $un;
    private $pw;
    private $agent;
    private $siteURL;
    private $cidoffset;
    private $CI;

    public function __construct() {
        $this->CI =& get_instance();

        $this->CI->load->config('creds_jumio', TRUE);
        $this->CI->load->model('event_model');
        $this->CI->load->model('logging_model');

        $_config = $this->CI->config->item('creds_jumio');

        $this->pw        = $_config['password'];
        $this->un        = $_config['username'];
        $this->siteURL   = $_config['url'];
        $this->agent     = $_config['agent'];
        $this->cidoffset = $_config['cidoffset'];
    }

    function supportedDocumentTypes() {
        $url = "https://netverify.com/api/netverify/v2/supportedDocumentTypes";

        $response = $this->curlSend($url, "GET");

        return json_decode($response);
    }

    function createDocumentAcquisition($user, $docType) {
        $url = "https://netverify.com/api/netverify/v2/createDocumentAcquisition";

        $id                      = (int)(explode(":", $user->_id)[1]) + $this->cidoffset;
        $merchantIdScanReference = $id;

        $response = $this->curlSend($url, "POST", array(
            "documentType"          => $docType,
            "merchantScanReference" => '' . $merchantIdScanReference,
            "successUrl"            => $this->siteURL . "/user/verify_done",
            "errorUrl"              => $this->siteURL . "/user/verify_done",
            "customerID"            => $id . ""
        ), true);

        return json_decode($response);
    }

    function initiateNetverify($user) {
        $url = "https://netverify.com/api/netverify/v2/initiateNetverify";

        $id = (int)(_numeric($user->_id)) + $this->cidoffset; // We obfuscate the user ID a little before sending to Jumio..

        $this->CI->logging_model->log("Initiated verification process... Jumio #".$id, "jumio", $user->id);

        $merchantIdScanReference = $id;

        $dob = $user->dob;
        $dob = explode("/", $dob);
        $dob = $dob[2] . '-' . $dob[0] . '-' . $dob[1];

        $response = $this->curlSend($url, "POST", array(
            "merchantIdScanReference" => '' . $merchantIdScanReference,
            "successUrl"              => $this->siteURL . "/user/verifystart?iddone=true",
            "errorUrl"                => $this->siteURL . "/user/verifystart?iddone=true",
            "country"                 => countryName($user->country, 'iso3'),
            "dob"                     => $dob,
            "customerId"              => $id . ""
        ), true);
        /*Remove as not checking now
                    "firstName"               => $user->first_name,
                    "lastName"                => $user->last_name,
        */
        return json_decode($response);
    }

    function validateID($postvars) {
        try {
            $idScanStatus = $postvars["idScanStatus"];
            $userId       = ((int)$postvars["merchantIdScanReference"]) - $this->cidoffset;

            $this->CI->logging_model->log(array("Jumio call-back validate ID: ",$postvars), "jumio", $userId);

            $user = $this->CI->user_model->getUser($userId);
            if (!$user) {
                $this->CI->logging_model->log("Couldnt get user for Jumio call-back", "jumio", $userId);
                systemEmail("Problem with user verification response from Jumio..", json_encode($postvars));
                exit;
            }

            $this->CI->lang->load('views',$user->language);

            switch ($idScanStatus) {
                case 'SUCCESS':
                    // Now let's check their details:
                    $errors = array();
                    $data   = array();
                    $details = array();

                    $diff = _l('jumio_different_to');

                    // Name
                    $uname = strtolower(replaceAccents(str_replace(" ", "", $user->first_name . $user->last_name)));
                    $jname = strtolower(replaceAccents(str_replace(" ", "", $postvars["idFirstName"] . $postvars["idLastName"])));

                    if ($uname != $jname) {

                        //$errors[]=_l('jumio_error_name')." (".$user->first_name.' '.$user->last_name.$diff.$postvars["idFirstName"]." ".$postvars["idLastName"].")";

                        $data["first_name"] = ucwords(strtolower($postvars["idFirstName"]));
                        $data["last_name"]  = ucwords(strtolower($postvars["idLastName"]));
                        $this->CI->logging_model->log("Updating name from ".$uname." to ".$jname, "jumio", $userId);
                    };

                    // DoB
                    if (!empty($postvars["idDob"])) {
                        $dob = $user->dob;
                        $dob = explode("/", $dob);
                        $dob = $dob[2] . '-' . $dob[0] . '-' . $dob[1];
                        if ($dob != $postvars["idDob"]) {
                            //$errors[]=_l('jumio_error_dob')." (".$dob.$diff.$postvars["idDob"].")";
                            $jdob        = explode("-", $postvars["idDob"]);
                            $data["dob"] = $jdob[1] . "/" . $jdob[2] . "/" . $jdob[0];
                            $this->CI->logging_model->log("Updating DoB from ".$user->dob." to ".$data["dob"], "jumio", $userId);
                        }
                    }

                    // Country
                    if (!empty($postvars["idCountry"])) {
                        // Country must match
                        if (countryName($user->country, 'iso3') != $postvars["idCountry"]) {
                            $errors[] = _l('jumio_error_country');
                            $details["country"] = countryCode($postvars["idCountry"], 'iso3');
                            $err = "Problem with user verification - country did not match ".countryName($user->country, 'iso3')." != ".$postvars["idCountry"];
                            $this->CI->logging_model->log($err, 'jumio', $userId);
                            systemEmail($err);
                        }
                    }

                    if (!empty($postvars["idAddress"])) {
                        $jaddress = json_decode($postvars["idAddress"]);
                        $this->CI->logging_model->log(array("Got address from ID: ", $jaddress), 'jumio', $userId);
                        if (isset($jaddress->city)) $details["city"] = $jaddress->city;
                        if (isset($jaddress->zip)) $details["zip"] = $jaddress->zip;
                        if (isset($jaddress->state)) $details["state"] = $jaddress->state;
                        $address = array();
                        if (isset($jaddress->line1)) $address[] = $jaddress->line1;
                        if (isset($jaddress->line2)) $address[] = $jaddress->line2;
                        if (isset($jaddress->line3)) $address[] = $jaddress->line3;
                        if (isset($jaddress->line4)) $address[] = $jaddress->line4;
                        if (isset($jaddress->line5)) $address[] = $jaddress->line5;
                        $details["address"] = implode(", ", $address);
                        $this->CI->logging_model->log(array("Updating user address to be: ", $address), 'jumio', $userId);
                    }

                    if (count($errors) == 0) {
                        $this->CI->logging_model->log("ID check completed OK", "jumio", $userId);
                        $data['verify_complete_a'] = 1;
                        $this->CI->user_model->update($userId, $data);
                        if (count($details)>0) $this->CI->user_model->updateDetails($userId, $details);
                        $this->checkAndVerify($userId);

                        // We need to double check if the face match was poor
                        $fmatchlevel = (int)$postvars["idFaceMatch"];
                        if (!empty($fmatchlevel) && $fmatchlevel < 70) {
                            $facematch = "Verification passed, but face match was < 70% (only " . $fmatchlevel . "%) for User ID " . $userId . " (Jumio ClientID: #" . $postvars["customerId"] . ")";
                            $this->CI->logging_model->log($facematch, "jumio", $userId);
                            systemEmail($facematch);
                        }
                    }
                    else {
                        $this->CI->logging_model->log(array("Got errors processing",$errors), "jumio", $userId);
                        $this->validationFail($userId, $postvars, $errors);
                    }

                    break;

                default:
                    $errors[] = _l('jumio_error_unknown');
                    $errorReason = json_decode($postvars["rejectReason"]);
                    $this->CI->logging_model->log(array("Error...", $errorReason), "jumio", $userId);
                    switch ($errorReason->rejectReasonDescription) {
                        case 'NOT_READABLE_DOCUMENT':
                            $errors[] = _l('jumio_unreadable');
                            break;
                        case 'PHOTOCOPY_BLACK_WHITE':
                            $errors[] = _l('jumio_photocopy');
                            break;
                        case 'MISSING_BACK':
                            $errors[] = _l('jumio_back_missing');
                    }
                    $details = $errorReason->rejectReasonDetails;
                    foreach ($details as $detail) {
                        switch ($detail->detailsDescription) {
                            case 'BLURRED':
                                $errors[] = _l('jumio_blurred');
                                break;
                            case 'BAD_QUALITY':
                                $errors[] = _l('jumio_poor_quality');
                                break;
                            case 'DAMAGED_DOCUMENT':
                                $errors[] = _l('jumio_damaged');
                                break;
                            case 'HIDDEN_PART_DOCUMENT':
                                $errors[] = _l('jumio_part_hidden');
                                break;
                            case 'MISSING_PART_DOCUMENT':
                                $errors[] = _l('jumio_part_missing');
                                break;
                        }
                    }

                    $vstatus = $postvars["verificationStatus"];
                    switch ($vstatus) {
                        case 'DENIED_UNSUPPORTED_ID_TYPE':
                            $errors[] = _l('jumio_unsupported_type');
                            break;
                    }

                    $this->validationFail($userId, $postvars, $errors);
            }

            // Record Jumio scan ID
            $data = array();
            $data['jumio_scan_a'] = $postvars["jumioIdScanReference"];
            $this->CI->user_model->update($userId, $data);

        } catch (Exception $e) {
            $this->CI->logging_model->log(array("Jumio callback code broke.. ".$e->getMessage(),$e->getTrace()), 'jumio', $userId);
            systemEmail("Jumio callback code broke.. ", $e->getMessage()."\n".json_encode($e->getTrace()));
            exit;
        }
    }

    function validateDoc($postvars) {
        $documentStatus = $postvars["documentStatus"];
        $userId         = ((int)$postvars["merchantScanReference"]) - $this->cidoffset;

        $user = $this->CI->user_model->getUser($userId);
        if (!$user) {
            $this->CI->logging_model->log("Couldnt get user for Jumio call-back", "jumio", $userId);
            systemEmail("Problem with user verification response from Jumio..", json_encode($postvars));
            exit;
        }
        
        $this->CI->logging_model->log(array("Jumio secondary doc callback- ",$postvars), "jumio", $userId);

        $user = $this->CI->user_model->getUser($userId);

        if ($documentStatus == 'DOCUMENT_PRESENT') {

            $data = array(
                'verify_complete_b' => 1,
                'jumio_scan_b' => $postvars["jumioScanReference"]
            );
            $this->CI->user_model->update($userId, $data);
            $this->CI->logging_model->log("Jumio secondary document OK", "jumio", $userId);

            $this->checkAndVerify($userId);
        }
        else {
            $this->CI->logging_model->log("Jumio secondary document upload problem - ".$documentStatus, "jumio", $userId);
            systemEmail("Document upload problem, User #" . $userId . " - " . $documentStatus);
        }
    }

    function checkAndVerify($userId) {
        try {
            $user = $this->CI->user_model->getUser($userId);
            if (!$user->verified && $user->verify_complete_a && $user->verify_complete_b) {
                // Yay! They've done both steps, so let's verify the,!
                $data = array(
                    'verified' => 1
                );
                $this->CI->user_model->update($user->id, $data);

                // Record event
                $this->CI->event_model->add($user->id,'vcomplete');

                // Send out email to user
                $data["name"] = $user->first_name;
                $this->CI->load->library('email');
                $this->CI->email_queue_model->email   = $user->email;
                $this->CI->email_queue_model->message = $this->CI->layout->partialView('emails/verificationOk_' . $user->language, $data);
                if ($user->language == 'es') $this->CI->email_queue_model->subject = 'Verficación Completada!';
                else $this->CI->email_queue_model->subject = 'Verification Complete!';
                $this->CI->email_queue_model->store();

                // Send email to admin
                systemEmail("User account verification complete - User #" . $user->_id . " (" . $user->first_name . " " . $user->last_name . ")");
            }
            else {
                $msg = "Checked User ".$user->_id.", but wasn't ready yet.. A: ".$user->verify_complete_a.', B: '.$user->verify_complete_b;
                $this->CI->logging_model->log($msg, "jumio", $user->id);

            }

        } catch (Exception $e) {

            systemEmail("User account verification ERROR - User #" . $user->_id, $e->getMessage());
        }
    }

    function validationFail($userId, $postvars, $errors) {
        $user = $this->CI->user_model->getUser($userId);

        systemEmail("User account verification FAILED - User #" . $userId . " (" . $user->first_name . " " . $user->last_name . ")", "

Status is: " . $postvars["idScanStatus"] . "

Reason for rejection was:  " . $postvars["rejectReason"] . "

Status Code: " . $postvars["verificationStatus"] . "
Scan was done from: " . $postvars["idScanSource"] . "
Face match % was: " . $postvars["idFaceMatch"] . "

Additional info provided: " . $postvars["additionalInformation"] . "

ID Type was: " . $postvars["idType"] . "
Country code: " . $postvars["idCountry"] . "
User IP was: " . $postvars["clientIp"] . "
ID image is here: " . $postvars["idScanImage"] . "
ID image back is here: " . $postvars["idScanImageBackside"] . "

Error messages sent to user were:

" . (count($errors) > 0 ? implode(",", $errors) : '') . "

            ");

        $data["errors"] = $errors;
        $data["name"]   = $user->first_name;

        $this->CI->load->library('email');
        $this->CI->email_queue_model->email   = $user->email;
        $this->CI->email_queue_model->message = $this->CI->layout->partialView('emails/verificationFail_' . $user->language, $data);

        if ($user->language == 'es') $this->CI->email_queue_model->subject = 'Problema con la Verificación';
        else $this->CI->email_queue_model->subject = 'Verification Problem';

        $this->CI->email_queue_model->store();

        // Record Event
        $this->CI->event_model->add($userId,'vfail');
    }

    function curlSend($url, $mode, $post = null, $jsonmode = false) {
        $timeout = 100;

        $ch = curl_init($url);

        if ($mode == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        if ($jsonmode) {
            // Send Json
            $data_string = json_encode($post);
            //print_r($data_string);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string)
                ));
        }
        else {
            // Send normal posted fields
            if (!is_null($post)) curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $mode);
        curl_setopt($ch, CURLOPT_USERPWD, $this->un . ":" . $this->pw);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PORT, 443);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); # required for https urls
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $result = curl_exec($ch);

        curl_close($ch); // Seems like good practice

        return $result;
    }
}