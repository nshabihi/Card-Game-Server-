<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Questionnaire extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        //$this -> load -> library('session');
        
        $this -> load -> helper('string');

        $this -> load -> model("usermodel");
		$this -> load -> model("questionnairemodel");

    }
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function Qresult()
    {
    	if(!isset($_GET['userResult']) || !isset($_GET['Auth']) ||count($_GET) != 2)
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
				$userResult	   = $this -> input -> get("userResult");

				$temp 		= explode(",", $userResult);
				$check		= 0;
				$userPersonalityType = "";

				if(count($temp) >= 17)
				{

					$results = "";
					for($i =0;$i<count($temp)/17 - 1;$i++)
					{
						$Qtype 		= $temp[$i*17];
						$Qresult 	= $temp[$i*17+16];
						for($j = 0;$j < 15;$j++)
						{
							$results.= $temp[$i*17+$j+1];
						}

						$tempCheck = $this -> questionnairemodel -> insertUserResult($uid,$Qtype,$Qresult,$results);

						if($tempCheck == -1)
							$check = 1;

						$results = "";
						$userPersonalityType .= $Qresult;
					}

					$this -> usermodel -> upadateUserGenderAndAge($uid, intval($temp[68]), intval($temp[69]));
					
					$result = $this -> usermodel -> setUserGameAdoption($uid, $userPersonalityType);

					if($check == 1)
						echo json_encode("2Some Redundant Data");
					else
						echo json_encode($result);
				}
				else
				{
					echo json_encode("4Wrong Data");
				}
			}
		}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
}
