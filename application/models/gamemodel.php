<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Gamemodel extends CI_Model {

    public function __construct()
    {

        $this->load->database();

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserGameLog($uid)
    {

        $this -> db -> select("gid, score, time, star1, star2, star3");
        $this -> db -> from("usergamestate");
        $this -> db -> where("uid" , $uid);
        $this -> db -> group_by("gid");
        $this -> db -> order_by("gid","asc");
        $query  = $this -> db -> get();
        $result = $query -> result_array();
        
        $alaki = $this -> db -> last_query();

        $temp = "";
        if($query -> num_rows() > 0){

            for($i = 0;$i < count($result); $i++){

                $temp = $temp.$result[$i]['gid'].",";
                $temp = $temp.$result[$i]['score'].",";
                $temp = $temp.$result[$i]['time'].",";
                $temp = $temp.$result[$i]['star1'].",";
                $temp = $temp.$result[$i]['star2'].",";
                $temp = $temp.$result[$i]['star3'];

                if($i != count($result) -1)
                    $temp.= ",nxtg,";

            }

        }

        return $temp;

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getGameRepeatCount($gid)
    {
        $this -> db -> select('minigame.repeatCount as repeatCount');
        $this -> db -> from("game");
        $this -> db -> where("game.id",$gid);
        $this -> db -> join("minigame","minigame.id = game.mgid");
        $query = $this -> db -> get();
        $result= $query-> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['repeatCount'];
        }else
        {
            return -2;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function setUserGameResult($uid, $gid, $score, $times, $stars, $fVocabs = array(),$tempPicFVocabs, $tempFVocabs, $tempFFreq,$movement)
    {
        $data = array(
            'score' => $score,
            'time1'  => $times[0],
            'time2'  => $times[1],
            'time3'  => $times[2],
            'star1'  => $stars[0],
            'star2'  => $stars[1],
            'star3'  => $stars[2],
            'uid'    => $uid,
            'gid'    => $gid,
            'movement' => $movement
        );

        $fVocabIds = array_keys($fVocabs);

        for($i = 1 ; $i <= count($fVocabs); $i++)
        {
            if($i <= 7)
            {
                $temp = strval($i);
                $data['vid'.$temp] = $fVocabIds[$i - 1];
                $data['failure'.$temp] = $tempFVocabs[$fVocabs[$fVocabIds[$i - 1]]];
                $data['freq'.$temp] = $tempFFreq[$fVocabs[$fVocabIds[$i - 1]]];
                $data['picfail'.$temp] = $tempPicFVocabs[$fVocabs[$fVocabIds[$i - 1]]];
            }
        }

        $this -> db -> insert("userplayedgame",$data);

        $miniGameType = $this -> getGameMinigameType($gid);

        if($miniGameType == "4")
        {
            $this -> db -> select("vocabularies.id as vocabId");
            $this -> db -> from("game");
            $this -> db -> join("vocabularies","vocabularies.lesson = game.lesson");
            $this -> db -> where("game.id", $gid);
            $this -> db -> where("vocabularies.isActive", 1);
            $query  = $this  -> db -> get();
            $result = $query -> result_array();

            if($query -> num_rows() > 0)
            {
                $vocabIdToCheck = $result[0]['vocabId'];

                $this -> db -> from('userexamstate');
                $this -> db -> where("vid",$vocabIdToCheck);
                $this -> db -> where("uid",$uid);
                $result2 = $this -> db -> count_all_results();

                if($result2 == 0)
                {

                    $data = array();

                    for($i = 0 ; $i < $query -> num_rows(); $i++)
                    {
                        $tempArray = array(
                            "uid" => $uid,
                            "vid" => $result[$i]['vocabId'],
                            "time"=> now(),
                            "vocabFaultCount" => 0,
                            "vocabTruthCount" => 0,
                            "lastExamsState"  => "00000"
                        );

                        array_push($data, $tempArray);
                    }

                    $this->db->insert_batch('userexamstate', $data); 

                }
                else
                {
                    return -2;
                }
            }else
            {
                return -1;
            }
        }


        return 0;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function updateUserGameState($uid, $gid, $score, $times, $stars)
    {
        $this -> db -> select("time, score, star1, star2, star3");
        $this -> db -> from("usergamestate");
        $this -> db -> where("uid", $uid);
        $this -> db -> where("gid", $gid);
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        $ttime  = $times[0] + $times[1] + $times[2];
        $tscore = $score;

        if($query -> num_rows() == 0)
        {

            $data = array
            (
                'uid' => $uid,
                'gid' => $gid,
                'time'=> $ttime,
                'score' => $tscore,
                'star1' => $stars[0],
                'star2' => $stars[1],
                'star3' => $stars[2]
            );

            $this -> db -> insert("usergamestate",$data);

            $result = $this -> getStringOfArrayInSeq($data);
            
            return $result;

        }else
        {
            $prevTime = $result[0]['time'];
            $prevScore= $result[0]['score'];
            $prevStar1= $result[0]['star1'];
            $prevStar2= $result[0]['star2'];
            $prevStar3= $result[0]['star3'];

            $tempResult= array();
            $data = array();
            $check = false;

            $tempResult['uid'] = $uid;
            $tempResult['gid'] = $gid;

            if($ttime < $prevTime){

                $data['time'] = $ttime;
                $tempResult['time'] = $ttime;
                $check = true;
            }else
            {
                $tempResult['time'] = $prevTime;
            }

            if($tscore > $prevScore){

                $data['score'] = $tscore;
                $data['star1'] = $stars[0];
                $data['star2'] = $stars[1];
                $data['star3'] = $stars[2];

                $tempResult['score'] = $tscore;
                $tempResult['star1'] = $stars[0];
                $tempResult['star2'] = $stars[1];
                $tempResult['star3'] = $stars[2];

                $check = true;
            }else
            {
                $tempResult['score'] = $prevScore;
                $tempResult['star1'] = $prevStar1;
                $tempResult['star2'] = $prevStar2;
                $tempResult['star3'] = $prevStar3;
            }

            if($check){

                $this -> db -> where("uid", $uid);
                $this -> db -> where("gid", $gid);
                $this -> db -> update("usergamestate",$data);
            }

            $result = $this -> getStringOfArrayInSeq($tempResult);

            return $result;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getStringOfArrayInSeq($tempResult)
    {
        $keys = array_keys($tempResult);
        $result = "";

        for($i = 0; $i < count($keys);$i++)
        {
            $result .= strval($tempResult[$keys[$i]]);
            
            if($i != (count($keys) - 1))
                $result .= ",";
        }

        return $result;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getGameMinigameType($gid)
    {
        $this -> db -> select("minigame.mgType as mgtype");
        $this -> db -> from("game");
        $this -> db -> join("minigame","minigame.id = game.mgid");
        $this -> db -> where("game.id",$gid);
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['mgtype'];

        }else
        {
            return 0;
        }
    }

    public function checkScoreValidity($gid, $tscore)
    {
        $this -> db -> select("minigame.maxScore as maxscore");
        $this -> db -> from("game");
        $this -> db -> join("minigame","minigame.id = game.mgid");
        $this -> db -> where("game.id",$gid);
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        if($query -> num_rows() > 0)
        {
            $maxScore = $result[0]['maxscore'];
            if($tscore <= $maxScore && $tscore >= 0)
            {
                return true;
            }
            else
            {
                return false;
            }
        }else
        {
            return false;
        }
    }
};
