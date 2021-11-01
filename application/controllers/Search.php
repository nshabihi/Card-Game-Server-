<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends CI_Controller {

    public function __construct()
    {
        parent::__construct();


        //$this -> load -> library('session');
        
        $this -> load -> helper('string');
        $this -> load -> helper('email');

        $this -> load -> model("usermodel");
		//$this -> load -> model("badgemodel");
		//$this -> load -> model("exammodel");
		//$this -> load -> model("gamemodel");
		$this -> load -> model("dictionarymodel");

    }
	//
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function searchVocab()
    {
    	if(!isset($_GET['vocab']) || !isset($_GET['Auth']) || count($_GET) != 2)
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
				$searchedVocab = $this -> input   -> get("vocab");

				$result = $this -> dictionarymodel -> userSearchedVocab($uid, $searchedVocab);

				if($result == -2){

					echo json_encode("2No such a word.");

				}
				else{

					echo json_encode("3OK");
				}
			}
		}
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
}
