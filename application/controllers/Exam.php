<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Exam extends CI_Controller {

    public function __construct()
    {
        parent::__construct();


        //$this -> load -> library('session');
        
        //$this -> load -> helper('string');
        //$this -> load -> helper('email');

		$this -> load -> model("badgemodel");
		$this -> load -> model("gamemodel");
		$this -> load -> model("vocabmodel");
		$this -> load -> model("exammodel");
        $this -> load -> model("usermodel");
        $this -> load -> model("progressmodel");
		$this -> load -> model("toolsmodel");


    }
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function userExamResult()
    {
    	if(!isset($_GET['vocab']) || !isset($_GET['score']) || !isset($_GET['faultCount']) || !isset($_GET['ff']) || !isset($_GET['sf']) || !isset($_GET['tf']) || !isset($_GET['PS']) || !isset($_GET['Auth']) ||count($_GET) != 8)
		{

			echo json_encode("0Invalid parameters");

		}
		else
		{
			$authParams = $this -> input -> get("Auth");
			$authParams = explode(",", $authParams);
			$uid 		= $this -> usermodel -> checkUserValidationNotLogin($authParams[0],$authParams[1]);
			if($uid == -1)
			{
				echo json_encode("1Authentication Failed!");
			}
			else
			{
				$score 		= $this -> input -> get("score");
				$vocab 		= $this -> input -> get("vocab");
				$faultCount = $this -> input -> get("faultCount");
				$firstFault = $this -> input -> get("ff");
				$secondFault= $this -> input -> get("sf");
				$thirdFault = $this -> input -> get("tf");

				$progressLog= $this -> input -> get("PS");
				$progressLog= explode(",", $progressLog);

				$time 		= "";

				$vocabFarsiEqual = $this -> toolsmodel -> getFarsiEquOfWord($vocab);
			
				$vocabId = $this -> vocabmodel -> checkVocabValidity($vocabFarsiEqual);

				if($vocabId != -2)
				{

					//$isScoreValid = $this -> exammodel -> checkExamScoreValidity($uid,$vocabId,$score);
					$isScoreValid = 0;
					if($score >= 0/* & $score <= 1000*/)
					{
						$isScoreValid = 1;
					}

					if($isScoreValid)
					{
						$result = $this -> exammodel -> setUserExamResult($uid, $vocabId, $score, $faultCount,$firstFault,$secondFault,$thirdFault,$time);

						if($result == -2)
						{

							echo json_encode("2Error in Inserting");

						}
						else
						{
							$this -> usermodel 	   -> addScoreToUser($uid, $score);

							$this -> progressmodel -> setUserProgressLog($uid,$progressLog);
							$this -> usermodel	   -> addProgressScoreToUser($uid, $progressLog[0]);
							$this -> badgemodel    -> checkUserBadges('learnxvocabs', $uid);

			    			$userExamLog  = $this -> exammodel  -> getThisExamLog($uid,$vocabId);

			    			$this -> badgemodel -> checkUserBadges('reachExam',$uid,0,$vocabId);

			    			$userBadgeLog = $this -> badgemodel -> getUserBadgeLog($uid);
			    			$badgeCount	  = $this -> badgemodel -> getBadgeCount();
			    			$userData     = $this -> usermodel  -> getUserAccountData($uid);
				    		$userLeaderboardState = $this -> usermodel -> getUserLeaderboardStatus($uid);

							$result   = "3examLog,".$userExamLog.",badgeLog,".$badgeCount.",".$userBadgeLog.",userData,".$userData.",".$userLeaderboardState.",endend";

							echo json_encode($result, JSON_UNESCAPED_UNICODE);
						}

					}else
					{
						echo json_encode("4Invalid Score");
					}

				}else
				{
					echo json_encode("5Invalid Vocab");
				}
			}
		}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function matchingExamResult()
    {
    	if(!isset($_GET['result']) || !isset($_GET['PS']) || !isset($_GET['Auth']) || count($_GET) != 3)
		{

			echo json_encode("0Invalid parameters");

		}
		else
		{
			$authParams = $this -> input -> get("Auth");
			$authParams = explode(",", $authParams);
			$uid 		= $this -> usermodel -> checkUserValidationNotLogin($authParams[0],$authParams[1]);
			if($uid == -1)
			{
				echo json_encode("1Authentication Failed!");
			}
			else
			{
				$examResult  	 = $this -> input -> get("result");
				$examResult      = explode(",", $examResult);

				$progressLog= $this -> input -> get("PS");
				$progressLog= explode(",", $progressLog);

				$examScore 		 = intval($examResult[0]);
				$vocabCount		 = intval($examResult[1]);

				$examVocabsIdArray 	  = array();
				$examVocabsCountArray = array();

				$vocabValidityCheck = 1;
				for($i =0;$i<$vocabCount;$i++)
				{
					$vocabFarsiEqual = $this -> toolsmodel -> getFarsiEquOfWord(strval($examResult[2*$i+2]));		

					$vocabId = $this -> vocabmodel -> checkVocabValidity($vocabFarsiEqual);
					
					if($vocabId != -2){
						$examVocabsIdArray[$i]		= $vocabId;
						$examVocabsCountArray[$i]	= strval($examResult[2*$i+3]);
					}else{
						$vocabValidityCheck = 0;
						break;
					}
				}

				if($vocabValidityCheck == 1)
				{
					$userFaultPairsIndex = 2 * $vocabCount + 2;

					$userFaultPairsString = "";
					for($i = $userFaultPairsIndex;$i<count($examResult)-1;$i++)
					{
						$userFaultPairsString .= strval($examResult[$i]);

						if($i != (count($examResult) - 2))
						{
							$userFaultPairsString .= ",";
						}
					}

					$this -> exammodel -> setMatchingExamResult($uid, $examVocabsIdArray, $examVocabsCountArray, $userFaultPairsString);

					$this -> usermodel -> addScoreToUser($uid, $examScore);
	    			//$userExamLog  = $this -> exammodel  -> getThisExamLog($uid,$vocabId);
	    			//$this -> badgemodel -> checkUserBadges('reachExam',$uid,0,$vocabId);
					$this -> progressmodel -> setUserProgressLog($uid,$progressLog);
					$this -> usermodel	   -> addProgressScoreToUser($uid, $progressLog[0]);
					$this -> badgemodel    -> checkUserBadges('learnxvocabs', $uid);

	    			$userBadgeLog = $this -> badgemodel -> getUserBadgeLog($uid);
	    			$badgeCount	  = $this -> badgemodel -> getBadgeCount();
	    			$userData     = $this -> usermodel  -> getUserAccountData($uid);
	    			$userLeaderboardState = $this -> usermodel -> getUserLeaderboardStatus($uid);

					$result   = "3badgeLog,".$badgeCount.",".$userBadgeLog.",userData,".$userData.",".$userLeaderboardState.",endend";

					echo json_encode($result);
				}
				else
				{
					echo json_encode("2Invalid Vocab");
				}
			}
		}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function testing()
    {
    	//$uid = $this -> session -> userdata('uid');
    	//$this -> usermodel 	-> getTopxtilly(0,10);
    	//$this -> usermodel 	-> addScoreToUser(10,1000);
    	//$this -> badgemodel -> checkUserBadges("topxleaderboard",$uid);
    	//$this -> usermodel -> updateUserLastLoginsInMonth(10);
    	//echo $this -> gamemodel -> getGameRepeatCount(15);
    	//$this -> badgemodel -> checkUserBadges('reachminigame',10,7);
    	//$this -> usermodel -> checkAndIncreaseUserProgress(10);
    	//echo $this -> usermodel -> getUserProgressPercentag(10);
    	//print_r($this -> usermodel -> getRangeOfUserProgress(150000));
    	//$this -> exammodel -> getVocabsForUserExam(10);
    	//$this -> badgemodel -> checkUserBadges('learnxvocabs',10);
    	//echo $this -> usermodel  -> calculateScoreProgressLevel(320);
    	//print_r($this -> usermodel -> getRangeOfUserProgress(180));
    	//echo $this -> usermodel -> calculateProgressPercentage(180);
    	//echo $this -> usermodel -> checkAndAddUserLoginProgressScore(28);
    	//$this -> usermodel -> upadateUserGenderAndAge(28, 24, 2);
    	//echo json_encode($this -> usermodel -> getDesiredGameAdaption("ISTJ"));
    	//echo json_encode($this -> usermodel -> setUserGameAdoption(36, "ISTJ"));
    	//echo json_encode($this -> usermodel -> getUserGameAdaptionMode(43));
    	//echo json_encode($this -> usermodel -> getRemainedTime());
    	//echo json_encode($this -> usermodel -> getUserLeaderboardStatus(37));
    	//$this -> usermodel -> getDesiredGameAdaption("ISTJ");
    	//echo json_encode($this -> usermodel -> getDesiredGameAdaption("ISTP"));
    	//echo json_encode($this -> usermodel -> setUserGameAdoption(45,"ISFP"));
    }
}
