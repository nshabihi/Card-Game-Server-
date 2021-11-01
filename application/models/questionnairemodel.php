<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Questionnairemodel extends CI_Model {

    public function __construct()
    {

        $this->load->database();

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function insertUserResult($uid,$qtype,$qresult,$results)
    {

        $this -> db -> from('questionarieresult');
        $this -> db -> where('uid',$uid);
        $this -> db -> where('qid',$qtype);
        $result = $this -> db -> count_all_results();

        if($result == 0){

            $data = array(
                'uid' => $uid,
                'qid' => $qtype,
                'answer' => $results,
                'ptype' => $qresult
            );

            $result = $this->db->insert('questionarieresult', $data);

            return 0;

        }
        else
        {
            return -1;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserPersonalityType($uid)
    {
        $this -> db -> select("qid, ptype");
        $this -> db -> from("questionarieresult");
        $this -> db -> where("uid", $uid);
        $this -> db -> order_by("qid","asc");
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        $alaki = "";

        if($query -> num_rows() > 0){
            for($i = 0; $i<count($result);$i++)
            {
                //$alaki .= $result[$i]['qid'].",";
                $alaki .= $result[$i]['ptype'];

                if($i != count($result) - 1)
                    $alaki .= ",";
            }
        }

        return $alaki;
    }
};
