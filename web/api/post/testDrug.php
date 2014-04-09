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
//test if drug can be changed and precaution tables can also be added with rows
$drug = array("drugs" => array("g_name" => "test1", "monitoring" => array("newtest", "notest")), "precautions" => array("values"=>array(array("drug"=>"test1", "name" => "newprec")), "options"=>array(array("status"=>"added", "pkey"=>"yes"))));
$status = updateDrug($drug,"unknown" ,"test1", $dbhandle);
echo $status;
echo "\n";

?>

