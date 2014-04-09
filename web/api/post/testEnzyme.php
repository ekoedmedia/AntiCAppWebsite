<?php
/*
 * Test suite to test drug post and enzyme post functionalities
 * the website includes this file first and runs its update function in order to
 * update a drug or an enzyme. Make sure to run tests insertions of sql for post
 * and comment on beginTransactions in postEnzyme.php
 */

require_once __DIR__."/../post/postDrug.php";
require_once __DIR__."/../post/postEnzyme.php";
require_once __DIR__."/../dbConnect/connectStart.php";

#include "updateDrug.php"
#$include "updateEnzyme.php"
#$include "postDrug.php"

function updateDrugController($array, $g_name) {

        for($i = 0; $i<length($array); $i++) {

                $updateStmt = $updateStmt."=".$array[$i].",";

        }
        updateDrug($updateStmt, $g_name);
}
//test if enzyme can be changed and substrate deleted from enzyme_interacts table
$enzyme = array("name" => "CYP 1A2", "substrate" => array("values" => array(array("enzyme"=>"CYP 1A2", "compound" => "Vinorelbine", "interaction" => "Substrate")), "options" => array(array("status"=>"deleted", "compound" => "Vinorelbine", "interaction" => "Substrate"))));
$status = updateEnzyme($enzyme, "test", "CYP 1A2", $dbhandle);
echo $status;
?>
