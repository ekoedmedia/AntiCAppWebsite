<?php
/**
 * This file is contains functions for listing all the drugs in the database.
 * The list is returned as an array of associative arrays, where each 
 * associative array represents one drug and contains g_name, t_name, and risk
 * keys.
 */

/**
 * Encodes and echos the list of drugs.
 */
function echoDrugList() {
	$list = getDrugList();

	echo(json_encode($list));
}

/**
 * Pull the g_name, t_name, and risk from each drug and returns them.
 *
 * @param dbhandle A way to hand this function a dbhandle in case you have
 *        already included connectStart.php. Defaults to null
 * @return An array of associative arrays where each associative array has
 *         the g_name, t_name, and rish of a given drug.
 */
function getDrugList($dbhandle = null) {
	require_once __DIR__."/../dbConnect/connectStart.php";

	$qDrugList = $dbhandle->prepare("select g_name, t_name, risk, deleted from drugs");
	$qDrugList->execute();

	return($qDrugList->fetchAll(PDO::FETCH_ASSOC));
}
?>
