<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Badgemodel extends CI_Model {

    public function __construct()
    {

        $this->load->database();

        $this -> load -> model("usermodel");
        $this -> load -> model("exammodel");
        $this -> load -> model("gamemodel");

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getUserBadgeLog($uid)
    {

        $this -> db -> select("badge.inGameBadgeId");
        $this -> db -> from("userbadge");
        $this -> db -> join("badge","userbadge.bid = badge.id");
        $this -> db -> where("uid" , $uid);
        $query  = $this  -> db -> get();
        $result = $query -> result_array();
        
        $temp = "";

        for ($i = 0; $i < count($result); $i++) {

            $temp = $temp.$result[$i]['inGameBadgeId'];
            if($i != count($result) -1)
                $temp = $temp.",";

        } 
        return $temp;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getBadgeCount()
    {
        $this -> db -> from("badge");
        $count = $this -> db -> count_all_results();

        return $count;

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function checkUserBadges($badgeType,$uid,$gid = 0,$vid = 0)
    {
        $badgeLists = $this -> getBadgeConditions($badgeType);
        $badgeCount = count($badgeLists);
        //print_r($badgeLists);

        switch ($badgeType) {
            case 'score':

                $userScore  = $this -> usermodel -> getUserScore($uid);

                for($i = 0; $i < $badgeCount; $i++)
                {
                    $checkScore    = $badgeLists[$i]['badgeCondition'];
                    $badgeId       = $badgeLists[$i]['id'];
                    if($userScore >= $checkScore)
                    {
                        $this -> checkAndGiveUserBadge($badgeId,$uid);
                    }
                }

                break;
            
            case 'xdaysyplace':

                break;
            
            case 'topxleaderboard':

                $userRank = $this -> usermodel -> getUserTotalRanking($uid);
                //print_r("userRank = ".strval($userRank));

                for($i=0; $i < $badgeCount; $i++)
                {
                    $checkRankingPlace = $badgeLists[$i]['badgeCondition'];
                    $badgeId           = $badgeLists[$i]['id'];
                    if($userRank <= $checkRankingPlace)
                    {
                        $this -> checkAndGiveUserBadge($badgeId, $uid);
                    }
                }

                break;

            case 'topxleaguentimes':

                $userTopLeagueCount = $this -> usermodel -> getUserTopLeagueCount($uid);

                for($i=0; $i < $badgeCount; $i++)
                {
                    $checkLeagueTopCount = $badgeLists[$i]['badgeCondition'];
                    $badgeId             = $badgeLists[$i]['id'];
                    if($userTopLeagueCount >= $checkLeagueTopCount)
                    {
                        $this -> checkAndGiveUserBadge($badgeId, $uid);
                    }
                }

                break;
            
            case 'loginxdays':

                $userStreak = $this -> usermodel -> getUserLoginStreak($uid);

                for($i=0; $i < $badgeCount; $i++)
                {
                    $checkLoginStreak = $badgeLists[$i]['badgeCondition'];
                    $badgeId           = $badgeLists[$i]['id'];
                    if($userStreak >= $checkLoginStreak)
                    {
                        $this -> checkAndGiveUserBadge($badgeId, $uid);
                    }
                }

                break;

            case 'loginxdaysinmonth':

                $userLastMonthLogins = $this -> usermodel -> getUserLastMonthLogins($uid);

                $loginCounts = 0;
                for($i = 0;$i<strlen($userLastMonthLogins);$i++)
                {
                    if($userLastMonthLogins[$i] == "1")
                        $loginCounts++;
                }

                for($i=0; $i < $badgeCount; $i++)
                {
                    $checkLoginCounts = $badgeLists[$i]['badgeCondition'];
                    $badgeId           = $badgeLists[$i]['id'];

                    if($loginCounts >= $checkLoginCounts)
                    {
                        $this -> checkAndGiveUserBadge($badgeId,$uid);
                    }
                }

                break;
            
            case 'reachLevel':

                $userLevel = $this -> usermodel -> getUserLevel($uid);
                for($i = 0; $i < $badgeCount; $i++)
                {
                    $checkLevel = $badgeLists[$i]['badgeCondition'];
                    $badgeId    = $badgeLists[$i]['id'];
                    if($userLevel >= $checkLevel)
                    {
                        $this -> checkAndGiveUserBadge($badgeId,$uid);
                    }
                }

                break;
            
            case 'learnxvocabs':

                $userExamsStates = $this -> exammodel -> getUserLastExamsState($uid);
                $learnedVocabCount = 0;
                for($i = 0 ; $i < count($userExamsStates); $i++)
                {
                    $lastExamstate = $userExamsStates[$i]['lastExamsState'];
                    $count = 0;
                    for($j =0; $j < strlen($lastExamstate);$j++)
                    {
                        if($lastExamstate[$j] == '1')
                            $count++;
                    }
                    if($count >= 4)
                        $learnedVocabCount++;
                }

                for($i = 0; $i < $badgeCount; $i++)
                {
                    $checkVocabsCount   = $badgeLists[$i]['badgeCondition'];
                    $badgeId            = $badgeLists[$i]['id'];
                    if($learnedVocabCount >= $checkVocabsCount)
                    {
                        $this -> checkAndGiveUserBadge($badgeId,$uid);
                    }
                }
                

                break;

            case 'reachminigame':

                $gameMinigameNumber = $this -> gamemodel -> getGameMinigameType($gid);
                for($i = 0; $i < $badgeCount; $i++)
                {
                    $checkMiniGameNumber = $badgeLists[$i]['badgeCondition'];
                    $badgeId    = $badgeLists[$i]['id'];
                    if($gameMinigameNumber == $checkMiniGameNumber)
                    {
                        $this -> checkAndGiveUserBadge($badgeId,$uid);
                    }
                }


                break;

            case 'reachExam':

                for($i = 0; $i < $badgeCount; $i++)
                {
                    $checkExamNumber = $badgeLists[$i]['badgeCondition'];
                    $badgeId         = $badgeLists[$i]['id'];
                    
                    if($checkExamNumber == $vid)
                    {
                        $this -> checkAndGiveUserBadge($badgeId,$uid);
                    }
                }

                break;
            
            default:

                break;
        }
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getBadgeConditions($badgeType)
    {
        $this -> db -> select("badgeCondition, id");
        $this -> db -> from("badge");
        $this -> db -> where("name", $badgeType);
        $query  = $this -> db -> get();
        $result = $query -> result_array();

        return $result;
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function checkAndGiveUserBadge($badgeId, $uid)
    {
        $this -> db -> from("userbadge");
        $this -> db -> where("bid",$badgeId);
        $this -> db -> where("uid",$uid);
        $result = $this -> db -> count_all_results();

        if($result == 0)
        {

            $data = array(
                "uid" => $uid,
                "bid" => $badgeId
            );
            $this -> db -> insert('userbadge', $data);

        }else
        {
            return;
        }
    }
};
