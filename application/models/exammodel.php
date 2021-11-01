<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Exammodel extends CI_Model {

    public function __construct()
    {

        $this -> load -> database();
        $this -> load -> helper('date');

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserExamLog($uid)
    {

        $this -> db -> select("vid ,vocabularies.vocab, Count(vid) as vcount, Sum(score) as vscore");
        $this -> db -> from("userexamedvocab");
        $this -> db -> where("uid" , $uid);
        $this -> db -> join("vocabularies","vocabularies.id = userexamedvocab.vid");
        $this -> db -> group_by("vid");
        $this -> db -> order_by("vid","asc");
        $query  = $this -> db -> get();
        $alaki = $this -> db -> last_query();
        $result = $query -> result_array();

        
        $temp = "";
        for ($i = 0; $i < count($result); $i++){

            $itemp = "";
            $itemp = $itemp.$result[$i]['vid'].",";
            $itemp = $itemp.$result[$i]['vocab'].",";
            $itemp = $itemp.$result[$i]['vcount'].",";
            $itemp = $itemp.$result[$i]['vscore'];

            $temp = $temp.$itemp;
            if($i != count($result) -1)
                $temp = $temp.",";

        }

        return $temp;

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getThisExamLog($uid, $vid)
    {

        $this -> db -> select("vid ,vocabularies.vocab, Count(vid) as vcount, Sum(score) as vscore");
        $this -> db -> from("userexamedvocab");
        $this -> db -> where("uid" , $uid);
        $this -> db -> where("vid" , $vid);
        $this -> db -> join("vocabularies","vocabularies.id = userexamedvocab.vid");
        $query  = $this -> db -> get();
        $result = $query -> result_array();
        
        if($query -> num_rows() > 0)
        {
            $temp = "";
            $temp = $temp.$result[0]['vid'].",";
            $temp = $temp.$result[0]['vocab'].",";
            $temp = $temp.$result[0]['vcount'].",";
            $temp = $temp.$result[0]['vscore'];

            return $temp;
        }
        else
        {
            return "";
        }

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function checkExamScoreValidity($uid,$vocabId,$score)
    {
        $this -> db -> select("Count(*) as vcount, Sum(score) as vscore");
        $this -> db -> from("userexamedvocab");
        $this -> db -> where("vid" , $vocabId);
        $this -> db -> where("uid" , $uid);
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        $prevScore = $result[0]['vscore'];
        $prevCount = $result[0]['vcount'];

        //echo json_encode("vscore = ".$prevScore." prevCount = ".$prevCount);

        $maxScoreEachExam = 1000;
        
        if($prevCount == 0)
        {
            if($score <= $maxScoreEachExam && $score >= 0)
                return true;
        }
        else if($prevCount <= 2)
        {
            if($score  <= (($maxScoreEachExam * $prevCount - $prevScore) / 10 + $maxScoreEachExam) && $score >= 0)
                return true;
        }
        else if($prevScore < 2900)
        {
            if( $score <= (( 3*$maxScoreEachExam - $prevScore ) / 2) && $score >= 0 )
                return true;
        }
        else
        {
            if($score <= (3 * $maxScoreEachExam - $prevScore) && $score >= 0 )
                return true;
        }
        return false;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function setUserExamResult($uid, $vocabId, $score, $faultCount, $firstFault, $secondFault, $thirdFault, $time = "")
    {
        $userExamResult = 
        array(
                "uid"        => $uid,
                "vid"        => $vocabId,
                "score"      => $score,
                "faultcount" => $faultCount,
                "time"       => $time,
                "firstFault" => $firstFault,
                "secondFault"=> $secondFault,
                "thirdFault" => $thirdFault
            );

        $result = $this->db->insert('userexamedvocab', $userExamResult);

        if($result == 1)
        {

            $this -> db -> select("*");
            $this -> db -> from("userexamstate");
            $this -> db -> where("uid", $uid);
            $this -> db -> where("vid", $vocabId);
            $query  = $this -> db -> get();
            $result = $query -> result_array();

            if($query -> num_rows() > 0)
            {
                $newVocabFault = $result[0]['vocabFaultCount'] + $faultCount;
                if($faultCount == 0)
                {
                    $newVocabTruthCount = $result[0]['vocabTruthCount'] + 1;
                    $newLastExamsState  = substr($result[0]['lastExamsState'], 1) . "1";
                }
                else
                {
                    $newVocabTruthCount = $result[0]['vocabTruthCount'];
                    $newLastExamsState  = substr($result[0]['lastExamsState'], 1) . "0";
                }

                $data = array(

                    "time" => now(),
                    "vocabFaultCount" => $newVocabFault,
                    "vocabTruthCount" => $newVocabTruthCount,
                    "lastExamsState"  => $newLastExamsState

                    );

                $this -> db -> where("uid", $uid);
                $this -> db -> where("vid", $vocabId);
                $this -> db -> update("userexamstate",$data);

                return 0;

            }
        }
        else
        {
            return -2;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserLastExamsState($uid)
    {
        $this -> db -> select("lastExamsState, vid, time");
        $this -> db -> where("uid",$uid);
        $this -> db -> from("userexamstate");
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        return $result;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function setMatchingExamResult($uid, $examVocabsIdArray, $examsVocabfCount, $examFaultsString)
    {
        $data = array
                (
                    "uid"        => $uid,
                    "faultPairs" => $examFaultsString
                );

        for($i = 0 ; $i < count($examVocabsIdArray);$i++)
        {
            $data["vid".strval($i+1)] = strval($examVocabsIdArray[$i]);
            $data["fcount".strval($i+1)] = strval($examsVocabfCount[$i]);
        }

        $this -> db -> insert("usermatchingexam",$data);


        $this -> db -> select("*");
        $this -> db -> from("userexamstate");
        $this -> db -> where("uid", $uid);
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        if($query -> num_rows() > 0)
        {
            $previousExamsData = array();
            for($i = 0 ; $i < count($result); $i++)
            {
                $previousExamsData[$result[$i]['vid']] = array(

                    'rowId'=> $result[$i]['id'],
                    'time' => $result[$i]['time'],
                    'vocabFaultCount' => $result[$i]['vocabFaultCount'],
                    'vocabTruthCount' => $result[$i]['vocabTruthCount'],
                    'lastExamsState'  => $result[$i]['lastExamsState']
                    );
            }

            //print_r($previousExamsData);

            $newUserExamsData = array();

            for($i = 0; $i < count($examVocabsIdArray); $i++)
            {
                if($examsVocabfCount[$i] == 0)
                {
                    $newVocabTruthCount = $previousExamsData[$examVocabsIdArray[$i]]['vocabTruthCount'] + 1;
                    $newLastExamsState  = substr($previousExamsData[$examVocabsIdArray[$i]]['lastExamsState'], 1) . "1";
                    $newVocabFault = $previousExamsData[$examVocabsIdArray[$i]]['vocabFaultCount'];
                }
                else
                {
                    $newVocabTruthCount = $previousExamsData[$examVocabsIdArray[$i]]['vocabTruthCount'];
                    $newLastExamsState  = substr($previousExamsData[$examVocabsIdArray[$i]]['lastExamsState'], 1) . "0";
                    $newVocabFault = $previousExamsData[$examVocabsIdArray[$i]]['vocabFaultCount'] + $examsVocabfCount[$i];
                }

                $data = array(

                    "id"   => $previousExamsData[$examVocabsIdArray[$i]]['rowId'],
                    "time" => now(),
                    "vocabFaultCount" => $newVocabFault,
                    "vocabTruthCount" => $newVocabTruthCount,
                    "lastExamsState"  => $newLastExamsState

                    );

                array_push($newUserExamsData, $data);
            }

            //print_r($newUserExamsData);
            $this -> db -> update_batch("userexamstate",$newUserExamsData,"id");
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getVocabsForUserExam($uid)
    {
        $this -> db -> where("uid",$uid);
        $this -> db -> where("gid",4);
        $this -> db -> from('usergamestate');
        $result = $this -> db -> count_all_results();

        if($result == 0)
            return "";

        $this -> db -> select("vid");
        $this -> db -> from("userexamstate");
        $this -> db -> where("uid",$uid);
        $this -> db -> where("vocabFaultCount", 0);
        $this -> db -> where("vocabTruthCount", 0);
        $this -> db -> order_by("time", "asc");
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        $orderedResultVids = array();

        for($i = 0; $i < $query -> num_rows(); $i++)
        {
            array_push($orderedResultVids, $result[$i]['vid']);
        }

        $this -> db -> select("vid, lastExamsState, time");
        $this -> db -> from("userexamstate");
        $this -> db -> where("uid", $uid);
        $where = "(vocabFaultCount !='0' OR vocabTruthCount !='0')";
        $this -> db -> where($where);
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        $vocabIds    = array();
        $vocabsScore = array();

        $faultAnswered = array();

        $k = 0;
        $t = 0;
        for($i =0 ; $i< $query -> num_rows(); $i++)
        {
            $tempLast   = $result[$i]['lastExamsState'];
            $tempStreak = 0;

            for($j = 4; $j >= 0; $j--)
            {
                if($tempLast[$j] == '1')
                    $tempStreak++;
                else
                    break;
            }

            if($tempStreak == 0)
            {
                $faultAnswered[$k]    = $result[$i]['vid'];
                $k++;
                continue;
            }
            $eachTime = $result[$i]['time'];
            $temp     = now() - $eachTime;

            $defaultTimeDiff = 7200;
            $eachScore= round($temp / (pow(2, $tempStreak - 1)*$defaultTimeDiff),2);

            //if($eachScore >= 1)
            //{
                $vocabIds[$t]    = $result[$i]['vid'];
                $vocabsScore[$t] = $eachScore;
                $t++;
            //}
        }

        for($i = count($vocabsScore)-1;$i>0;$i--)
        {
            for($j = 0; $j <$i;$j++)
            {
                if($vocabsScore[$j] < $vocabsScore[$j+1])
                {
                    
                    $temp              = $vocabsScore[$j];
                    $vocabsScore[$j]   = $vocabsScore[$j+1];
                    $vocabsScore[$j+1] = $temp;

                    $temp              = $vocabIds[$j];
                    $vocabIds[$j]      = $vocabIds[$j+1];
                    $vocabIds[$j+1]    = $temp;

                }
            }
        }

        $orderedResultVids = array_merge($orderedResultVids,$faultAnswered);
        $orderedResultVids = array_merge($orderedResultVids,$vocabIds);

        $possibleExamVocabCounts   = array(6,6,4,4,3,3,3,1,1,1);
        $ExamVocabCounts           = array();

        $maxTotalVocabCounts = min(20,count($orderedResultVids));

        while($maxTotalVocabCounts > 0)
        {
            if($maxTotalVocabCounts >= 6)
            {
                $rnd = rand(0,9);
                array_push($ExamVocabCounts, $possibleExamVocabCounts[$rnd]);
                $maxTotalVocabCounts -= $possibleExamVocabCounts[$rnd];

            }else if($maxTotalVocabCounts >=4 )
            {
                $rnd = rand(2,9);
                array_push($ExamVocabCounts, $possibleExamVocabCounts[$rnd]);
                $maxTotalVocabCounts -= $possibleExamVocabCounts[$rnd];

            }else if($maxTotalVocabCounts >=3 )
            {
                $rnd = rand(4,9);
                array_push($ExamVocabCounts, $possibleExamVocabCounts[$rnd]);
                $maxTotalVocabCounts -= $possibleExamVocabCounts[$rnd];

            }else
            {
                array_push($ExamVocabCounts, $possibleExamVocabCounts[9]);
                $maxTotalVocabCounts -= $possibleExamVocabCounts[9];
            }
        }

        $this -> db -> select("*");
        $this -> db -> from("vocabularies");
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        $allVocabs = array();
        for($i = 0 ; $i < $query -> num_rows(); $i++)
        {
            $allVocabs[$result[$i]['id']] = $result[$i]['vocab'];
        }

        $vocabForUserExamString = "";

        $lastIndex = 0;
        for($i = 0 ; $i < count($ExamVocabCounts) ; $i++)
        {
            $temp = $ExamVocabCounts[$i];
            $vocabForUserExamString .= strval($temp);
            $vocabForUserExamString .= ",";
            for($j = 0; $j < $temp ; $j++)
            {
                $vocabForUserExamString .= $allVocabs[$orderedResultVids[$lastIndex]];
                $lastIndex++;

                if($j != $temp -1)
                    $vocabForUserExamString .= ",";
            }

            if($i != count($ExamVocabCounts) - 1)
            {
                $vocabForUserExamString .= ",";
            }
        }
        
        return $vocabForUserExamString;

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
};