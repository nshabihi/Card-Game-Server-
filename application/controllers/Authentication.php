<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Authentication extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        //$this -> load -> library('session');
        
        //$this -> load -> helper('string');
        //$this -> load -> helper('email');

        $this -> load -> model("usermodel");
		$this -> load -> model("badgemodel");
		$this -> load -> model("exammodel");
		$this -> load -> model("gamemodel");
		$this -> load -> model("questionnairemodel");
		$this -> load -> model("toolsmodel");

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
	public function login()
	{
		if(!isset($_GET['username']) || !isset($_GET['deviceId']) || !isset($_GET['loginUid']) || count($_GET) != 3)
		{

			echo json_encode("0Invalid parameters");

		}
		else
		{
	    	/*if($this -> session -> userdata('username') != NULL && $this -> session -> userdata('username') != $_GET['username'])
	    	{

	    		echo json_encode("1It's not your username!");

	    	}*/
	    	//else
			//{
				$username = $_GET['username'];
				$deviceId = $_GET['deviceId'];
				$loginUid = $_GET['loginUid'];

				$uid = $this -> usermodel -> checkUserValidationInLogin($username, $deviceId, $loginUid);

				if($uid != -1)
				{
					/*if($this -> session -> userdata('username')  == NULL)
					{
			    		$this -> session -> set_userdata('username', $username);
			    		$this -> session -> set_userdata('deviceId', $deviceId);
			    		$this -> session -> set_userdata('uid', $uid);
		    		}*/


		    		$newLoginUid = $this -> toolsmodel -> createNewLoginUniqueId();
		    		$this -> usermodel -> createLoginUniqueIdForUser($uid, $newLoginUid);

		    		// Warning Warning Warning Warning Warning Warning Warning Warning Warning Warning 
		    		// Warning Warning Warning Warning Warning Warning Warning Warning Warning Warning 
		    		//
		    		// This function should be called before updateloginRelatedData.
		    		//
		    		$scoreAddedForLoginProgress = $this -> usermodel -> checkAndAddUserLoginProgressScore($uid);
		    		// Warning Warning Warning Warning Warning Warning Warning Warning Warning Warning 
		    		// Warning Warning Warning Warning Warning Warning Warning Warning Warning Warning 

		    		$this -> usermodel  -> updateLoginRelatedData($uid);

		    		$this -> badgemodel -> checkUserBadges('loginxdays',$uid);

		    		$this -> badgemodel -> checkUserBadges('loginxdaysinmonth',$uid);

		    		$userGameLog  = $this -> gamemodel  -> getUserGameLog($uid);

		    		$userBadgeLog = $this -> badgemodel -> getUserBadgeLog($uid);

		    		$userExamLog  = $this -> exammodel  -> getUserExamLog($uid);

		    		$VocabForUserExam = $this -> exammodel -> getVocabsForUserExam($uid);

		    		$userData     = $this -> usermodel  -> getUserAccountData($uid);

				    $userLeaderboardState = $this -> usermodel -> getUserLeaderboardStatus($uid);

		    		$userPtype	  = $this -> questionnairemodel -> getUserPersonalityType($uid);

		    		$gameAdaption = $this -> usermodel -> getUserGameAdaptionMode($uid);

		    		$badgeCount	  = $this -> badgemodel -> getBadgeCount();

		    		$result = "2loginUid,".strval($newLoginUid).",gameLog,".$userGameLog.",badgeLog,".$badgeCount.",".$userBadgeLog.",examLog,".$userExamLog.",userData,".$userData.",".$userLeaderboardState.",userPtype,".$userPtype.",gameAdaption,".$gameAdaption.",VocabForExam,".$VocabForUserExam.",loginPS,".$scoreAddedForLoginProgress.",endend";

		    		echo json_encode($result, JSON_UNESCAPED_UNICODE);

				}
				else
				{
					echo json_encode("3Wrong username or deviceId");
				}
	    	//}
	    }
	}
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
	public function register()
	{
		if(!isset($_GET['username']) /*|| !isset($_GET['email']) || count($_GET) != 2*/) // Email is Optional
		{

			echo json_encode("0Invalid parameters");

		}
		/*else if($this -> session -> userdata('username')  != NULL){

			echo json_encode("1You are logged in!");

		}*/
		else
		{

			$username   = $this -> input -> get("username");

			$email = "";
			if(isset($_GET['email']))
			{
				$email 		= $this -> input -> get("email");
			}

			if($email != "" && !valid_email($email))
			{
				echo json_encode("2Invalid Email");
			}
			else if(strlen(strval($username)) > 15 or strlen(strval($username)) < 4)
			{
				echo json_encode("6Invalid username length");
			}
			else
			{

 				$userUniqueID = $this -> toolsmodel -> createNewDeviceId();

 				$userLoginUid = $this -> toolsmodel -> createNewLoginUniqueId();

 				$uid = $this -> usermodel -> createUser($username, $email, $userUniqueID, $userLoginUid);

 				if($uid == -2)
 				{
 					echo json_encode("3email is userd before");
 				}
			    else if($uid == -1)
			    {
			    	echo json_encode("4Failed to create user");
			    }
			    else
			    {

			    	/*$this -> session -> set_userdata('username', $username);
			    	$this -> session -> set_userdata('deviceId', $userUniqueID);
					$this -> session -> set_userdata('uid', $uid);*/

			        echo json_encode("5userUniqueId,".$userUniqueID.",username,".$username.",userLoginId,".$userLoginUid.",endend"); // Success
			    }
			}
	    }
	}
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
	public function checkVersion()
	{
		if(!isset($_GET['version']) || count($_GET) != 1) // Email is Optional
		{
			echo json_encode("0Invalid parameters");
		}
		else
		{

			$versionToCheck = intval($this -> input -> get("version"));

			$mustUpdateVesion = 0;
			$ifYouWantUpdateVersion  = 0;

			$result = "1versionCheck,";

			if($versionToCheck < $mustUpdateVesion && $mustUpdateVesion != 0)
			{

				$result .= "1"; // must Update

				$result .= ",";

				$result .= "0"; // update if you want

			}else if($versionToCheck < $ifYouWantUpdateVersion)
			{

				$result .= "0"; // must Update

				$result .= ",";

				$result .= "1"; // update if you want

			}else
			{

				$result .= "0"; // must Update

				$result .= ",";

				$result .= "0"; // update if you want
			}

			$result .= ",endend";

			echo json_encode($result);

		}
	}
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function ping()
    {
    	echo json_encode("1PongPong");
    }
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function logout()
    {
    	//$this -> session -> sess_destroy();
    	//echo "2Done!";
    }
}
