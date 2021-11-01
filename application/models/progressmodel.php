<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Progressmodel extends CI_Model {

    public function __construct()
    {

        $this->load->database();

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function setUserProgressLog($uid, $progressLog)
    {
        $progressScore = $progressLog[0];
        $progressReasonString = "";

        for($i = 1; $i < count($progressLog); $i++)
        {
            $progressReasonString .= strval($progressLog[$i]);
            
            if($i != count($progressLog) -1 ){
                $progressReasonString .= ",";
            }
        }

        $data = array
                (
                    "uid" => $uid,
                    "score" => $progressScore,
                    "reason" => $progressReasonString
                );

        $this -> db -> insert("userprogress", $data);
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
};
