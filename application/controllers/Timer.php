<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Timer extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

		$this -> load -> model("timermodel");
		$this -> load -> model("usermodel");

    }
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function getBadgeTimer()
    {
    	if(!isset($_GET['time']) || !isset($_GET['Auth']) || count($_GET) != 2)
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
				$timeInBadge   = $this -> input -> get("time");

				$this -> timermodel -> getBadgeTimer($uid, $timeInBadge);

				echo json_encode("20OK");
			}
		}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getRankingTimer()
    {
    	if(!isset($_GET['time']) || !isset($_GET['Auth']) || count($_GET) != 2)
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
				$timeInBadge   = $this -> input -> get("time");

				$this -> timermodel -> getLeaderboardTimer($uid, $timeInBadge);
				echo json_encode("20OK");
			}
		}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getExamTimer()
    {
    	if(!isset($_GET['time']) || !isset($_GET['Auth']) || count($_GET) != 2)
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
				$timeInBadge   = $this -> input   -> get("time");

				$this -> timermodel -> getExamTimer($uid, $timeInBadge);
				echo json_encode("20OK");
			}
		}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
}
