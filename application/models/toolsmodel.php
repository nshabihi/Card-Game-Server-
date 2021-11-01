<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Toolsmodel extends CI_Model {

    public function __construct()
    {

        $this -> load -> model("usermodel");

    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
    public function createNewLoginUniqueId()
    {
        $userLoginUid = random_string('alnum', 31);
        while(true)
        {
            if($this -> usermodel -> randomLoginUidUsedBefore($userLoginUid))
            {
                $userLoginUid = random_string('alnum', 31);
            }
            else
            {
                break;
            }
        }

        return $userLoginUid;
    }

    public function createNewDeviceId()
    {
        $userUniqueID = random_string('alnum', 31);
        while(true)
        {
            if($this -> usermodel -> randomStrUsedBefore($userUniqueID))
            {
                $userUniqueID = random_string('alnum', 31);
            }
            else
            {
                break;
            }
        }

        return $userUniqueID;
    }

    public function getFarsiEquOfWord($englishWord)
    {
        $englishToFarsi = array(
            "apple" => "KÃw",
            "cherry" => "¼²IL²A",
            "grape" => "n¼«ºH",
            "lemon" => "¸ÄoÃ{¼µÃ²",
            "strawberry" => "Â«ºoÎR¼U",
            "orange" => "ÏI£UoQ",
            "pear" => "ÂM°¬",

            "taxi" => "Âv¨IU",
            "bicycle" => "¾ioa»j",
            "train" => "nIõ¤",
            "airplane" => "IµÃQH¼À",
            "bus" => "t¼M¼UH",
            "ship" => "ÂTz¨",
            "motorcycle" => "n¼U¼¶",

            "pizza" => "HqTÃQ",
            "bread" => "·Iº",
            "fish" => "ÂÀI¶",
            "water" => "JA",
            "ice_cream" => "Â¹TvM",
            "milk" => "oÃ{",
            "chicken" => "ùo¶",

            "butterfly" => "¾ºH»oQ",
            "owl" => "kû]",
            "dog" => "ªw",
            "cow" => "»I¬",
            "monkey" => "·¼µÃ¶",
            "bear" => "toi",
            "lion" => "oÃ{",

            "t_shirt" => "RozÃU",
            "jacket" => "S¨",
            "socks" => "JHn¼]",
            "hat" => "½°¨",
            "glasses" => "¦¹Ãø",
            "shoes" => "yŸ¨",
            "pants" => "nH¼±{",

            "blue" => "ÂMA",
            "red" => "q¶o¤",
            "brown" => "ÁH½¼¿¤",
            "yellow" => "jnp",
            "Black" => "½Iãw",
            "pink" => "ÂUn¼‚",
            "violet" => "yŸ¹M",
        );

        return $englishToFarsi[$englishWord];
    }
    //
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
};
