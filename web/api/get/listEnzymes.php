<?php
/**
 * This file contains functions for listing all the enzymes that aren't
 * hidden in the database.
 * Only the names of the enzymes are returned, and they are returned in an array
 * of strings.
 */

/**
 * Encodes and echos the list of cyp enzyme names
 */
function echoEnzymeList() {
	$list = getEnzymeList();

	echo(json_encode($list));
}

/**
 * Pulls all the names of enzymes, and then adds them to the array that is 
 * returned.
 *
 * @param dbhandle A way to hand this function a dbhandle in case you have 
 *        already included connectStart.php. Defaults to null
 * @return An array of enzyme names
 */
function getEnzymeList($dbhandle = null) {
	require_once __DIR__."/../dbConnect/connectStart.php";

	$qEnzymeList = $dbhandle->prepare("select name, deleted from cyp_enzymes");
	$qEnzymeList->execute();

	return($qEnzymeList->fetchAll(PDO::FETCH_ASSOC));
}
?>
