<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Game extends CI_Controller {

    public function __construct()
    {
        parent::__construct();


        //$this -> load -> library('session');
        
        //$this -> load -> helper('string');
        //$this -> load -> helper('email');

		$this -> load -> model("badgemodel");
		//$this -> load -> model("gamemodel");
		$this -> load -> model("vocabmodel");
		$this -> load -> model("exammodel");
        $this -> load -> model("usermodel");
        $this -> load -> model("gamemodel");
		$this -> load -> model("progressmodel");
		$this -> load -> model("toolsmodel");

    }
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function userGameResult()
    {
    	if( !isset($_GET['gstate']) || !isset($_GET['PS']) || !isset($_GET['Auth']) || count($_GET) != 3)
		{

			echo json_encode("0Invalid parameters");

		}
		else
		{
		//userGameResult?gstate=5,move,6,score,36000,time,1.5,star,1,1,1,vocab,ABANDON+p,2,2,0,2,ABANDON,1,1,0,DATA+p,1,0,1,2,DATA,2,0,1,GALLANT+p,0,1,1,2,GALLANT,0,2,1

			$authParams = $this -> input -> get("Auth");
			$authParams = explode(",", $authParams);
			$uid 		= $this -> usermodel -> checkUserValidationNotLogin($authParams[0],$authParams[1]);
			if($uid == -1)
			{
				echo json_encode("1Authentication Failed!");
			}
			else
			{
				$gState		  	 = $this -> input -> get("gstate");
				$gStates 		 = explode(",", $gState);

				$progressLog= $this -> input -> get("PS");
				$progressLog= explode(",", $progressLog);

				$gid 	 		 = $gStates[0];
				$movement		 = $gStates[2];
				$tscore 		 = $gStates[4];
				$gameRepeatCount = $this -> gamemodel -> getGameRepeatCount($gid);

				if($gameRepeatCount == -2)
				{
					echo json_encode("2Invalid gid.");
				}else
				{
					if($gameRepeatCount == 3)
					{
						$times  = array($gStates[6],$gStates[7],$gStates[8]);
						$stars	= array($gStates[10],$gStates[11],$gStates[12]);
						$faultVocabIndexs = 13;
					}
					else if($gameRepeatCount == 1)
					{
						$times  = array($gStates[6],0,0);//,$gStates[9],$gStates[10]);
						$stars	= array($gStates[8],$gStates[9],$gStates[10]);
						$faultVocabIndexs = 11;
					}

					$tempFVocabs    = array();
					$tempPicFVocabs = array();
					$tempFFreq      = array();

					if(count($gStates) > $faultVocabIndexs)
					{
						$tempFVocabsCounts = (count($gStates) - $faultVocabIndexs - 1) / 9;

						for($i = 0 ; $i < $tempFVocabsCounts; $i++)
						{
							$vocabFarsiEqual = $this -> toolsmodel -> getFarsiEquOfWord($gStates[$faultVocabIndexs + 6 +9*$i]);
							
							$tempPicFVocabs[$vocabFarsiEqual] =
									strval($gStates[$faultVocabIndexs + 1 + 9*$i+1]).','. // picture fault 1
									strval($gStates[$faultVocabIndexs + 1 + 9*$i+2]).','. // picture fault 2
									strval($gStates[$faultVocabIndexs + 1 + 9*$i+3]);  // picture fault 3

							$tempFFreq[$vocabFarsiEqual]   = $gStates[$faultVocabIndexs + 1 + 9*$i+4]; // Frequency

							$tempFVocabs[$vocabFarsiEqual] = 
									strval($gStates[$faultVocabIndexs + 1 + 9*$i+6]).','. // picture fault 1
							        strval($gStates[$faultVocabIndexs + 1 + 9*$i+7]).','. // picture fault 2
									strval($gStates[$faultVocabIndexs + 1 + 9*$i+8]);  // picture fault 3
						}
					}

					$check = true;
					$fVocabsIdAndfCount = array();
					$tempVocabKeys = array_keys($tempFVocabs);

					for($i =0; $i < count($tempFVocabs); $i++)
					{
						$temp = $this -> vocabmodel -> checkVocabValidity($tempVocabKeys[$i]);
						if($temp == -2)
						{
							$check = false;
							break;
						}else{

							$fVocabsIdAndfCount[$temp] = $tempVocabKeys[$i];
						}
					}

					$isScoreValid  = $this -> gamemodel -> checkScoreValidity($gid,$tscore);

					if($check){

						if($isScoreValid)
						{

							$this -> gamemodel -> setUserGameResult($uid ,$gid, $tscore, $times, $stars, $fVocabsIdAndfCount, $tempPicFVocabs, $tempFVocabs, $tempFFreq, $movement);

							$userGameStateResult = $this -> gamemodel -> updateUserGameState($uid, $gid, $tscore, $times, $stars);

							$this -> badgemodel -> checkUserBadges('reachminigame',$uid,$gid);

				    		$this -> usermodel -> addScoreToUser($uid, $tscore);

							$this -> progressmodel -> setUserProgressLog($uid,$progressLog);
							$this -> usermodel	   -> addProgressScoreToUser($uid, $progressLog[0]);

							$userBadgeLog = $this -> badgemodel -> getUserBadgeLog($uid);

				    		$userData = $this -> usermodel -> getUserAccountData($uid);

				    		$userLeaderboardState = $this -> usermodel -> getUserLeaderboardStatus($uid);

				    		$badgeCount	  = $this -> badgemodel -> getBadgeCount();

				    		///// Warning Warning Warning Warning Warning Warning Warning Warning 
				    		///// Warning Warning Warning Warning Warning Warning Warning Warning
				    		///// Warning Warning Warning Warning Warning Warning Warning Warning 
				    		///// This Change is Only for Competition server.

				    		$VocabForUserExam = $this -> exammodel -> getVocabsForUserExam($uid);
							
							$result   = "4gameLog,".$userGameStateResult.",badgeLog,".$badgeCount.",".$userBadgeLog.",userData,".$userData.",".$userLeaderboardState.",VocabForExam,".$VocabForUserExam.",endend";

							echo json_encode($result, JSON_UNESCAPED_UNICODE);

						}else
						{
							echo json_encode("5Invalid Score");
						}
					}else
					{
						echo json_encode("3Invalid fault Vocab");
					}
				}
			}
		}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //

    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function resetLeagueLeaderboard()
    {
 		if( !isset($_GET['Auth']) || count($_GET) != 1 )
		{

			echo json_encode("0Invalid parameters");

		}
		else
		{
			$authParams = $this -> input -> get("Auth");

			$authParams = explode(",", $authParams);
			$username   = $authParams[0];
			$password   = $authParams[1];

			if($username == "masterAdminEmadAbdoli" && $password == "HiItsEmadPleaseOpenTheDoor")
			{
				echo json_encode($this -> usermodel -> resetLeagueLeaderboard());
			}
		}
    }
}