<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leaderboard extends CI_Controller {

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


    }
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function getLeaderboard()
    {
    	if(!isset($_GET['ltype']) || !isset($_GET['Auth']) ||count($_GET) != 2)
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
				$ltype = $_GET['ltype'];
				
				$username = $this -> usermodel -> getUsername($uid);

				if($username == -1)
				{
					echo json_encode("5Invalid uid or username!");
					return;
				}

				switch ($ltype) {
					case 'total':
						
						$temp 		   = $this -> usermodel -> getTopxtilly(0,20,0);
						$userTotalRank = $this -> usermodel -> getUserTotalRanking($uid);
						$userTotalScore= $this -> usermodel -> getUserScore($uid);

						$result = "";
						$result .= strval($uid);
						$result .= ",";
						$result .= strval($username);
						$result .= ",";
						$result .= strval($userTotalScore);
						$result .= ",";
						$result .= strval($userTotalRank);
						$result .= ",";

						for($i = 0; $i < count($temp); $i++)
						{
							$result .= strval($temp[$i][0]);
							$result .= ",";
							$result .= strval($temp[$i][1]);
							$result .= ",";
							$result .= strval($temp[$i][2]);
							$result .= ",";
							$result .= strval($temp[$i][3]);

							if($i != count($temp) -1)
								$result .= ",";
						}

						echo json_encode("4leaderboard,".$result.",endend");
						break;

					case 'lastTimePeriod':

						$temp 			= $this -> usermodel -> getTopxtilly(0,20,1);
						$userRecentRank = $this -> usermodel -> getUserRecetTotalRanking($uid);
						$userRecentScore= $this -> usermodel -> getUserRecentScore($uid);
						$remainedTime   = $this -> usermodel -> getRemainedTime();

						$result = "";
						$result .= strval($uid);
						$result .= ",";
						$result .= strval($username);
						$result .= ",";
						$result .= strval($userRecentScore);
						$result .= ",";
						$result .= strval($userRecentRank);
						$result .= ",";

						for($i = 0; $i < count($temp); $i++)
						{
							$result .= strval($temp[$i][0]);
							$result .= ",";
							$result .= strval($temp[$i][1]);
							$result .= ",";
							$result .= strval($temp[$i][2]);
							$result .= ",";
							$result .= strval($temp[$i][3]);

							if($i != count($temp) -1)
								$result .= ",";
						}

						echo json_encode("4leaderboard,".$result.",remainedTime,".strval($remainedTime).",endend");
						break;

					case 'myLeague':

						$userLevel = $this -> usermodel -> getUserLevel($uid);
						if($userLevel != 0)
						{
							$temp 			   = $this -> usermodel -> getTopOfMyLeague($userLevel,0,20);
							$userLeagueRanking = $this -> usermodel -> getUserLeagueRanking($uid);
							$userScore   	   = $this -> usermodel -> getUserScore($uid);

							$result = "";
							$result .= strval($uid);
							$result .= ",";
							$result .= strval($username);
							$result .= ",";
							$result .= strval($userScore);
							$result .= ",";
							$result .= strval($userLeagueRanking);
							$result .= ",";

							for($i = 0; $i < count($temp); $i++)
							{
								$result .= strval($temp[$i][0]);
								$result .= ",";
								$result .= strval($temp[$i][1]);
								$result .= ",";
								$result .= strval($temp[$i][2]);
								$result .= ",";
								$result .= strval($temp[$i][3]);

								if($i != count($temp) -1)
									$result .= ",";
							}

							echo json_encode("4leaderboard,".$result.",endend");

						}else
						{
							echo json_encode("3fault in server");
						}

						break;
					
					default:
						echo json_encode("2ltype error");
						break;
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
}
