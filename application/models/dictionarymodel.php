<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Dictionarymodel extends CI_Model {

    public function __construct()
    {

        $this->load->database();

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function userSearchedVocab($uid, $vocab)
    {

        $this -> db -> select('id');
        $this -> db -> from('vocabularies');
        $this -> db -> where("vocab", $vocab);
        $query = $this -> db -> get();

        if($query -> num_rows() == 0){

            return -2;

        }else
        {

            $result = $query -> result_array();
            $vid    = $result[0]['id'];

            $this -> db -> select("count");
            $this -> db -> from("searched");
            $this -> db -> where("uid", $uid);
            $this -> db -> where("vid", $vid);
            $query1 = $this -> db -> get();

            if($query1 -> num_rows() > 0){

                $result1 = $query1 -> result_array();
                $count  = $result1[0]['count'];

                $data = array(
                   'count' => $count + 1,
                );
                $this -> db-> where('vid', $vid);
                $this -> db-> where('uid', $uid);
                $this -> db-> update('searched', $data);

                return 0;

            }else{

                $data = array(
                   'count' => 1,
                   'vid' => $vid,
                   'uid' => $uid
                );
                $this -> db -> insert('searched', $data);

            }
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
};
