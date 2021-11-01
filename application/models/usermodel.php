<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Usermodel extends CI_Model {

    public function __construct()
    {

        $this -> load -> database();

        $this -> load ->helper('date');

        $this -> load -> model("badgemodel");

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function createUser($username, $email, $deviceId, $userLoginUid)
    {
    	$this -> db -> from("user");
    	$this -> db -> where("email", $email);
    	$result1 = $this -> db -> count_all_results();

    	if($result1 != 0 && $email != "")
    	{
    		return -2;
    	}
    	else
    	{
            $datestring = "%Y-%m-%d";
            $time = time();
            $today = mdate($datestring, $time);

            if($email != "")
            {
    	    	$data = array(
    			   'username' => $username ,
    			   'email' => $email ,
    			   'deviceId' => $deviceId,
                   'lastLoginDay' => $today,
                   'loginUniqueId' => $userLoginUid
    			);
            }
            else
            {
                $data = array(
                   'username' => $username ,
                   'deviceId' => $deviceId,
                   'lastLoginDay' => $today,
                   'loginUniqueId' => $userLoginUid
                );
            }

	    	$result = $this->db->insert('user', $data);

	    	if($result == 1)
	    	{
	    		$uid = $this->db->insert_id();
                return $uid;
	    	}
	    	else
	    	{
	    		return -1;
	    	}

    	}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function randomStrUsedBefore($randomStr)
    {
    	$this -> db -> from("user");
    	$this -> db -> where("deviceId",$randomStr);
    	$result = $this -> db -> count_all_results();

    	if($result != 0)
    	{
    		return true;
    	}
    	else
    	{
    		return false;
    	}
    }

    public function randomLoginUidUsedBefore($randomStr)
    {
        $this -> db -> from("user");
        $this -> db -> where("loginUniqueId",$randomStr);
        $result = $this -> db -> count_all_results();

        if($result != 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function checkUserValidationInLogin($username, $deviceId, $loginUid)
    {
        $this -> db -> select("id");
    	$this -> db -> from("user");
        $this -> db -> where("deviceId", $deviceId);
        $this -> db -> where("username", $username);
        //$this -> db -> where("loginUniqueId", $loginUid);
        $query  = $this -> db -> get();
        $result = $query -> result_array();


        if($query -> num_rows() > 0){
            
            return $result[0]['id'];

        }else{

            return -1;
        }
    }

    public function createLoginUniqueIdForUser($uid, $newLoginUid)
    {
        $this -> db -> where("id", $uid);
        $this -> db -> set('loginUniqueId',$newLoginUid);
        $this -> db -> update('user');
    }

    public function checkUserValidationNotLogin($deviceId, $loginUid)
    {
        $this -> db -> select("id");
        $this -> db -> where("deviceId",$deviceId);

        // checking loginUid temporary changed to username.
        $this -> db -> where("username",$loginUid); // $this -> db -> where("loginUniqueId",$loginUid);
        $this -> db -> from("user");
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        if($query -> num_rows() > 0){

            return $result[0]['id'];
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
    public function getUserAccountData($uid)
    {
        $this -> db -> select("progressLevel, score, email, progressscore, age, gender");
        $this -> db -> from("user");
        $this -> db -> where("id", $uid);
        $query = $this -> db -> get();
        $result =$query -> result_array();

        $temp = "";
        if($query -> num_rows() > 0){
            
            $temp = $temp.$result[0]['score'].",";
            $temp = $temp.$result[0]['progressLevel'].",";
            $temp = $temp.$result[0]['email'].",";
            $temp = $temp.$result[0]['progressscore'].",";
            $temp = $temp.$result[0]['age'].",";
            $temp = $temp.$result[0]['gender'].",";

            //return $temp;
            $userPogressRanges      = $this -> getRangeOfUserProgress($result[0]['progressscore']);
            $userProgressPercentage = $this -> getUserProgressPercentag($uid);

            $temp = $temp.$userProgressPercentage.",";
            $temp = $temp.strval($userPogressRanges[0]).",".strval($userPogressRanges[1]).",".strval($userPogressRanges[2]);

            return $temp;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function addScoreToUser($uid, $scoreToAdd)
    {
        $this -> db -> select("score, recentScore");
        $this -> db -> from("user");
        $this -> db -> where("id", $uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        $prevScore      = $result[0]["score"];
        $prevRecentScore= $result[0]["recentScore"];

        $this -> db -> where("id", $uid);
        $this -> db -> set('score','score+'.strval($scoreToAdd),false);
        $this -> db -> set('recentScore','recentScore+'.strval($scoreToAdd),false);
        $this -> db -> update('user');

        $newScore = $prevScore + $scoreToAdd;

        $this -> badgemodel -> checkUserBadges("score",$uid);
        $this -> badgemodel -> checkUserBadges("topxleaderboard",$uid);

        return $newScore;
    }


    public function addProgressScoreToUser($uid, $progressScoreToAdd)
    {
        $this -> db -> where("id", $uid);
        $this -> db -> set('progressscore','progressscore+'.strval($progressScoreToAdd),false);
        $this -> db -> update('user');
        

        $this -> checkAndIncreaseUserProgress($uid);

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getRangeOfUserProgress($progressScore)
    {
        $defaultProgressIncrease = 20; //a
        //$defaultProgressIncrease = 30; //a
        //$defaultProgressIncrease = 40; //a
        $incrementingIncrease    = 40; //b
/*
        a       x+a
        a+b     x+2a+b
        a+2b    x+3a+3b
        a+3b    x+4a+6b
        a+4b    x+5a+10b
        .
        .
        .
*/
        $progressLevelThreshold  = 20;
        $lastProgressThreshold   = 0;
        $i = 1;
        while($progressScore >= $progressLevelThreshold){

            $lastProgressThreshold   = $progressLevelThreshold;
            $progressLevelThreshold += $defaultProgressIncrease + $i * $incrementingIncrease; 
            $i++;
        }

        $nextProgressLevelThreshold  = $progressLevelThreshold + $defaultProgressIncrease + $i * $incrementingIncrease;

        $result = array
                    (
                        $lastProgressThreshold,$progressLevelThreshold,$nextProgressLevelThreshold
                    );

        return $result;
    }

    public function calculateScoreProgressLevel($score)
    {
        $defaultProgressIncrease = 20; //a
        //$defaultProgressIncrease = 30; //a
        //$defaultProgressIncrease = 40; //a
        $incrementingIncrease    = 40; //b
/*
        a       x+a
        a+b     x+2a+b
        a+2b    x+3a+3b
        a+3b    x+4a+6b
        a+4b    x+5a+10b
        .
        .
        .
*/
        $progressLevelThreshold  = 20;
        $tempUserProgress        = 1;

        $i = 1;
        while($score >= $progressLevelThreshold){

            $tempUserProgress += 1;
            $progressLevelThreshold += $defaultProgressIncrease + $i * $incrementingIncrease; 

            $i++;
        }

        return $tempUserProgress;
    }

    public function calculateProgressPercentage($score)
    {
        $defaultProgressIncrease = 20; //a
        //$defaultProgressIncrease = 30; //a
        //$defaultProgressIncrease = 40; //a
        $incrementingIncrease    = 40; //b
/*
        a       x+a
        a+b     x+2a+b
        a+2b    x+3a+3b
        a+3b    x+4a+6b
        a+4b    x+5a+10b
        .
        .
        .
*/
        $progressLevelThreshold  = 20;
        $lastProgressThreshold   = 0;
        $i = 1;
        while($score >= $progressLevelThreshold){

            $lastProgressThreshold   = $progressLevelThreshold;
            $progressLevelThreshold += $defaultProgressIncrease + $i * $incrementingIncrease; 
            $i++;
        }

        return strval(round(($score-$lastProgressThreshold)/($progressLevelThreshold-$lastProgressThreshold),2));
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function checkAndIncreaseUserProgress($uid)
    {
        $progressScore = $this -> getUserProgressScore($uid);
        $userProgress  = $this -> calculateScoreProgressLevel($progressScore);

        $data = array('progressLevel' => $userProgress);
        $this -> db -> where('id', $uid);
        $this -> db -> update('user', $data);

        $this -> badgemodel -> checkUserBadges("reachLevel",$uid);

        return 1;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserProgressPercentag($uid)
    {
        $userPScore      = $this -> getUserProgressScore($uid);
        if($userPScore == 0){
            return "0";
        }

        $progressPercentage = $this -> calculateProgressPercentage($userPScore);

        return $progressPercentage;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserScore($uid)
    {
        $this -> db -> select("score");
        $this -> db -> from("user");
        $this -> db -> where("id",$uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['score'];
        }else
        {
            return 0;
        }

    }

    public function getUserRecentScore($uid)
    {
        $this -> db -> select("recentScore");
        $this -> db -> from("user");
        $this -> db -> where("id",$uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['recentScore'];
        }else
        {
            return 0;
        }
    }

    public function getUserProgressScore($uid)
    {
        $this -> db -> select("progressscore");
        $this -> db -> from("user");
        $this -> db -> where("id",$uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['progressscore'];
        }else
        {
            return 0;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserLevel($uid)
    {
        $this -> db -> select("progressLevel");
        $this -> db -> from("user");
        $this -> db -> where("id",$uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['progressLevel'];
        }else
        {
            return 0;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getTopxtilly($from, $to,$isRecent = 0)
    {
        if($isRecent == 1)
            $this -> db -> select("id, username, progressLevel, recentScore");
        else
            $this -> db -> select("id, username, progressLevel, score");

        $this -> db -> from("user");
        //$this -> db -> order_by("progressLevel", 'DESC');

        if($isRecent == 1)
            $this -> db -> order_by("recentScore", 'DESC');
        else
            $this -> db -> order_by("score", 'DESC');

        $this -> db -> limit($to);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        $count = $to - $from + 1;
        $data = array();
        if($query -> num_rows() > 0)
        {
            $count = min($count, $query -> num_rows());

            for($i = 0;$i < $count; $i++)
            {
                $data[$i][0] = $result[$from+$i]['id'];
                $data[$i][1] = $result[$from+$i]['username'];
                
                if($isRecent == 1)
                    $data[$i][2] = $result[$from+$i]['recentScore'];
                else
                    $data[$i][2] = $result[$from+$i]['score'];

                $data[$i][3] = $result[$from+$i]['progressLevel'];
            }
        }

        return $data;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getTopOfMyLeague($userLevel,$from, $to)
    {
        $levelFrom = $userLevel - 1;
        $levelTo   = $userLevel + 1;
        $this -> db -> select("id, username, progressLevel, score");
        $this -> db -> from("user");
        //$this -> db -> order_by("progressLevel", 'DESC');
        $this -> db -> order_by("score", 'DESC');
        $this -> db -> limit($to);
        $this -> db -> where("progressLevel >=",$levelFrom);
        $this -> db -> where("progressLevel <=",$levelTo);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        $count = $to - $from + 1;
        $data = array();
        if($query -> num_rows() > 0)
        {
            $count = min($count, $query -> num_rows());

            for($i = 0;$i < $count; $i++)
            {
                $data[$i][0] = $result[$from+$i]['id'];
                $data[$i][1] = $result[$from+$i]['username'];
                $data[$i][2] = $result[$from+$i]['score'];
                $data[$i][3] = $result[$from+$i]['progressLevel'];
            }
        }

        return $data;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserTotalRanking($uid)
    {
        $userTotalScore = $this -> getUserScore($uid);

        $this -> db -> from("user");
        $this -> db -> where("score >" , $userTotalScore);
        $count = $this -> db -> count_all_results();

        return $count + 1;
    }

    public function getUserRecetTotalRanking($uid)
    {
        $userRecentTotalScore = $this -> getUserRecentScore($uid);

        $this -> db -> from("user");
        $this -> db -> where("recentScore >" , $userRecentTotalScore);
        $count = $this -> db -> count_all_results();

        return $count + 1;
    }

    public function getUserLeagueRanking($uid)
    {
        $userLevel = $this -> usermodel -> getUserLevel($uid);
        $userScore = $this -> getUserScore($uid);

        $levelFrom = $userLevel - 1;
        $levelTo   = $userLevel + 1;
        $this -> db -> from("user");
        $this -> db -> where("progressLevel >=",$levelFrom);
        $this -> db -> where("progressLevel <=",$levelTo);
        $this -> db -> where('score > ',$userScore);
        $count = $this -> db -> count_all_results();

        return $count + 1;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function updateLoginRelatedData($uid)
    {
        $lastLoginDay = $this -> updateUserLastLogin($uid);
        $this -> updateUserLastLoginsInMonth($uid,$lastLoginDay);
    }

    public function updateUserLastLogin($uid)
    {
        $this -> db -> select("lastLoginDay, lastLoginStreak");
        $this -> db -> from("user");
        $this -> db -> where("id", $uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            $lastLoginDay   = $result[0]['lastLoginDay'];
            $lastLoginStreak= $result[0]['lastLoginStreak'];

            //echo json_encode(timespan($lastLoginTime));

            //$timeDif = strval(timespan($lastLoginDay));
            $datestring = "%Y-%m-%d";
            $time = time();
            $today = mdate($datestring, $time);

            $dayDif= $this -> getDaysDifference($today, $lastLoginDay);

            //$pos = strpos($timeDif, "Days");

            if($dayDif == 1)
            {
                $this -> db -> where("id", $uid);
                $this -> db -> set("lastLoginDay",$today,true);
                $this -> db -> set("lastLoginStreak","lastLoginStreak + 1",false);
                $this -> db -> update('user');
            }else if($dayDif > 1)
            {
                $this -> db -> where("id", $uid);
                $this -> db -> set("lastLoginDay",$today,true);
                $this -> db -> set("lastLoginStreak",0,true);
                $this -> db -> update('user');
            }

            return $lastLoginDay;
        }
    }

    public function getDaysDifference($today, $lastLogin)
    {
        $dStart = new DateTime($today);
        $dEnd   = new DateTime($lastLogin);
        $dDiff  = $dStart -> diff($dEnd);
        $result = $dDiff  -> days;
/*
        $result = 0;
        $result += ($todayArray[0] - $lastLArray[0]) * 365;
        $result += ($todayArray[1] - $lastLArray[1]) * 30;
        $result += ($todayArray[2] - $lastLArray[2]);
*/
        return $result;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserLoginStreak($uid)
    {
        $this -> db -> select("lastLoginStreak");
        $this -> db -> from("user");
        $this -> db -> where("id",$uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['lastLoginStreak'];
        }else
        {
            return 0;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function updateUserLastLoginsInMonth($uid,$lastLoginDay)
    {
        $this -> db -> select("lastLoginDay, lastmonthlogins");
        $this -> db -> from("user");
        $this -> db -> where("id", $uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            $lastmonthLog = $result[0]['lastmonthlogins'];

            $datestring = "%Y-%m-%d";
            $time = time();
            $today = mdate($datestring, $time);

            $dayDif= $this -> getDaysDifference($today, $lastLoginDay);

            if($dayDif > 30){

                $this -> db -> where("id", $uid);
                $this -> db -> set("lastLoginDay", $today, true);
                $this -> db -> set("lastmonthlogins","0000000000000000000000000000000",true);
                $this -> db -> update('user');

            }else if($dayDif != 0)
            {
                $temp = substr($lastmonthLog, $dayDif);

                for($i = 0; $i < $dayDif-1;$i++)
                {
                    $temp.= "0";
                }
                $temp .= "1";

                $this -> db -> where("id", $uid);
                $this -> db -> set("lastLoginDay", $today, true); //
                $this -> db -> set("lastmonthlogins",$temp,true);
                $this -> db -> update('user');
            }
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserLastMonthLogins($uid)
    {
        $this -> db -> select("lastmonthlogins");
        $this -> db -> from("user");
        $this -> db -> where("id",$uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['lastmonthlogins'];
        }else
        {
            return 0;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function checkAndAddUserLoginProgressScore($uid)
    {
        $this -> db -> select("lastLoginDay, lastLoginStreak");
        $this -> db -> from("user");
        $this -> db -> where("id", $uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            $lastLoginDay   = $result[0]['lastLoginDay'];
            $lastLoginStreak= $result[0]['lastLoginStreak'];

            $datestring = "%Y-%m-%d";
            $time = time();
            $today = mdate($datestring, $time);

            $dayDif= $this -> getDaysDifference($today, $lastLoginDay);

            if($dayDif == 0)
            {

                return "0,0";

            }else if($dayDif == 1){

                $scoreToAdd = round(log($lastLoginStreak + 1,2) * 50);
                $this -> addProgressScoreToUser($uid, $scoreToAdd);

                return strval($scoreToAdd).",".strval($lastLoginStreak);
            }
        }

        return "0,0";
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUsername($uid)
    {
        $this -> db -> select("username");
        $this -> db -> from("user");
        $this -> db -> where("id",$uid);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['username'];

        }else
        {
            return -1;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function resetLeagueLeaderboard()
    {
        $this -> db -> select("id, username, recentScore");
        $this -> db -> from("user");
        $this -> db -> limit(5);
        $this -> db -> order_by("recentScore","desc");
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            $temp = "";
            for($i = 0; $i < $query -> num_rows(); $i++)
            {
                $userid   = $result[$i]['id'];
                $username = $result[$i]['username'];
                $rScore   = $result[$i]['recentScore'];

                $this -> increaseTopLeagueCount($userid);
                
                $temp .= strval($userid);
                $temp .= ",";
                $temp .= strval($username);
                $temp .= ",";
                $temp .= strval($rScore);
                
                if($i != $query -> num_rows() - 1)
                {
                    $temp .= ",";
                }
            }

            $this -> db -> set("recentScore","0",false);
            $this -> db -> update("user");

            $tempLeagueDays = 3600 * 24 * 3;

            $data = array("setTime" => strval(time()), "finishTime" => strval(time() + $tempLeagueDays));
            $this -> db -> insert("leaguetimer", $data);

            return $temp;
        }
        else
        {
            return "-1";
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function increaseTopLeagueCount($uid)
    {
        $this -> db -> where("id", $uid);
        $this -> db -> set('topInLeagueCount','topInLeagueCount+1',false);
        $this -> db -> update('user');

        $this -> badgemodel -> checkUserBadges("topxleaguentimes",$uid);
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserTopLeagueCount($uid)
    {
        $this -> db -> select("topInLeagueCount");
        $this -> db -> where("id", $uid);
        $this -> db -> from("user");
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0]['topInLeagueCount'];

        }else
        {
            return -1;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function upadateUserGenderAndAge($uid, $age, $gender)
    {

        $data = array("age" => $age, "gender" => $gender);

        $this -> db -> where("id", $uid);
        $this -> db -> update("user", $data);

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getDesiredGameAdaption($userPersonalityType)
    {
        /*$eachPersonalityAdoptionCount = 10;

        $this -> db -> select('id, badgeonoff, leaderBoardonoff, feedBackonoff, usercount');
        $this -> db -> from('gameadaption');
        $this -> db -> where("pType", $userPersonalityType);
        $this -> db -> where("usercount <", $eachPersonalityAdoptionCount);
        $this -> db -> order_by("priority", "asc");
        $this -> db -> limit(1);
        $query = $this  -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return $result[0];

        }
        else
        {
            return array('-1' => -1);
        }*/

        $this -> db -> from("usergameadoption");
        $this -> db -> where("isControl" , 0);
        $testCount = $this -> db -> count_all_results();

        $this -> db -> from("usergameadoption");
        $this -> db -> where("isControl", 1);
        $controlCount = $this -> db -> count_all_results();

        $snPart = substr($userPersonalityType, 1,1);
        $tfPart = substr($userPersonalityType, 2,1);
        $pjPart = substr($userPersonalityType, 3,1);

        $badgeonoff = 0;
        $leaderBoardonoff = 0;
        $feedBackonoff = 0;

        if($controlCount < $testCount)
        {
            $isControl = 1;

            $tempRand = rand(1,3);

            if($tempRand == 1)
            {
                $badgeonoff = 1;

            }else if($tempRand == 2)
            {
                $leaderBoardonoff = 1;

            }else
            {
                $feedBackonoff = 1;
            }

        }else
        {
            $isControl = 0;

            $tempRand = rand(1,100);

            if($snPart == "N" && $tfPart == "T")
            {
                if($tempRand > 50)
                    $feedBackonoff = 1;
                else
                    $badgeonoff = 1;

            }else if($snPart == "N" && $tfPart == "F")
            {

                if($tempRand > 66)
                    $leaderBoardonoff = 1;
                else if($tempRand > 33)
                    $badgeonoff = 1;
                else
                    $feedBackonoff = 1;

            }else if($snPart == "S" && $pjPart == "J")
            {
                
                $badgeonoff = 1;

            }else // ($snPart == "S" && $pjPart == "P")
            {
                
                if($tempRand > 50)
                    $leaderBoardonoff = 1;
                else
                    $badgeonoff = 1;
            }
        }

        $this -> db -> select('id, badgeonoff, leaderBoardonoff, feedBackonoff, usercount');
        $this -> db -> from('gameadaption');
        $this -> db -> where("pType", $userPersonalityType);
        $this -> db -> where("badgeonoff" , $badgeonoff);
        $this -> db -> where("leaderBoardonoff" , $leaderBoardonoff);
        $this -> db -> where("feedBackonoff" , $feedBackonoff);
        $this -> db -> limit(1);
        $query = $this  -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            $result[0]['isControl'] = $isControl;
            return $result[0];

        }
        else
        {
            return array('-1' => -1);
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function setUserGameAdoption($uid, $userPersonalityType)
    {
        $gameAdaptionMode = $this -> getDesiredGameAdaption($userPersonalityType);

        if(count($gameAdaptionMode) != 1)
        {
            $this -> db -> from("usergameadoption");
            $this -> db -> where("uid", $uid);
            $result = $this -> db -> count_all_results();

            if($result == 0)
            {
                $data = array('uid' => $uid , 'gameAdoptionId' => $gameAdaptionMode['id'], 'isControl' => $gameAdaptionMode['isControl']);
                $this -> db -> insert("usergameadoption", $data);

                $this -> db -> set("usercount", "usercount + 1", false);
                $this -> db -> where("id", $gameAdaptionMode['id']);
                $this -> db -> update("gameadaption");

                return "3adaptby,".strval($gameAdaptionMode['badgeonoff']).",".strval($gameAdaptionMode['leaderBoardonoff']).",".strval($gameAdaptionMode['feedBackonoff']).",endend";
            }
            else
            {
                return "5RedundantError";
            }

        }else
        {
            return "6Error";
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserGameAdaptionMode($uid)
    {
        $this -> db -> select("badgeonoff, leaderBoardonoff, feedBackonoff");
        $this -> db -> from("gameadaption");
        $this -> db -> join("usergameadoption", "usergameadoption.gameAdoptionId = gameadaption.id");
        $this -> db -> where("usergameadoption.uid", $uid);
        $query = $this  -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            $temp = "";
            $temp .= strval($result[0]['badgeonoff']);
            $temp .= ",";
            $temp .= strval($result[0]['leaderBoardonoff']);
            $temp .= ",";
            $temp .= strval($result[0]['feedBackonoff']);

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
    public function getRemainedTime()
    {
        $this -> db -> select("finishTime");
        $this -> db -> from("leaguetimer");
        $this -> db -> order_by("id", "desc");
        $this -> db -> where("finishTime > ", time());
        $this -> db -> limit(1);
        $query = $this -> db -> get();
        $result= $query -> result_array();

        if($query -> num_rows() > 0)
        {
            return intval($result[0]['finishTime']) - intval(time());
        }else
        {
            return 0;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserLeaderboardStatus($uid)
    {
        $username = $this -> getUsername($uid);

        $userTotalRank = $this -> getUserTotalRanking($uid);

        $result = "";
        $result .= "totalRank,";
        $result .= strval($userTotalRank);
        $result .= ",";

        $userRecentRank = $this -> getUserRecetTotalRanking($uid);
        $userRecentScore= $this -> getUserRecentScore($uid);
        $remainedTime   = $this -> getRemainedTime();

        $result .= "recentRank,";
        $result .= strval($userRecentScore);
        $result .= ",";
        $result .= strval($userRecentRank);
        $result .= ",";
        $result .= strval($remainedTime);
        $result .= ",";

        $userLeagueRanking = $this -> getUserLeagueRanking($uid);

        $result .= "hamLevelsRank,";
        $result .= strval($userLeagueRanking);
        
        $result .= ",";
        $result .= "1"; // Leaderboard show notification threshold.

        return $result;
    }
};
