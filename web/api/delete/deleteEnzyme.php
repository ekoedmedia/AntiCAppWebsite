<?php
/**
 * Functions to hide and show all rows of cyp_enzyme and 
 * enzyme_interacts that reference the sent enzyme. This will hide the enzymes
 * from get calls, effectively deleting them, but while still preserving the
 * data.
 */

/**
 * Calls deleteEnzyme with showHide = 1, then encodes and echos the status of 
 * the delete query.
 *
 * @param name Name of the enzyme you wish to hide
 */
function echoDeleteEnzyme($name) {
	$response = deleteEnzyme($name, 'API user', 1);

	echo(json_encode($response));
}

/**
 * Starts a transaction so all updates are done or none are, and then sets
 * the deleted column to showHide for all rows relating to the enzyme.
 *
 * @param name Name of the enzyme to show or hide
 * @oaram username Name of the user doing the change
 * @param showHide 1 to hide the enzyme and 0 to show it
 * @param dbhandle A way to hand this function a dbhandle in case 
 *        connectStart.php has already been included
 * @return True if the query suceeded, and false otherwise
 */
function deleteEnzyme($name, $username, $showHide, $dbhandle = null) {
	require_once __DIR__."/../dbConnect/connectStart.php";

	$dbhandle->beginTransaction();

	$status = True;

	// Call a function for each table and updates status to false if the
	// status fails
	$status = $status && showHideEnzymeInter($name, $dbhandle, $username, $showHide);
	$status = $status && showHideEnzyme($name, $dbhandle, $username, $showHide);

	if ($status) {
		$dbhandle->commit();
	} else {
		$dbhandle->rollBack();
	}

	return ($status);

}

/**
 * Set deleted = showHide for all interactions involving the enzyme
 *
 * @param name Name of the enzyme to show or hide
 * @oaram username Name of the user doing the change
 * @param showHide 1 to hide the enzyme and 0 to show it
 * @param dbhandle The database connection
 * @return True if the query suceeded, and false otherwise
 */
function showHideEnzymeInter($name, $dbhandle, $username, $showHide) {
	$qEnzInter = $dbhandle->prepare("update enzyme_interacts "
				       ."set deleted = ?, who_updated = ? "
				       ."where enzyme = ?");
	$status = $qEnzInter->execute(array($showHide, $username, $name));

	return($status);
}

/**
 * Set deleted = showHide for the given enzyme
 *
 * @param name Name of the enzyme to show or hide
 * @oaram username Name of the user doing the change
 * @param showHide 1 to hide the enzyme and 0 to show it
 * @param dbhandle The database connection
 * @return True if the query suceeded, and false otherwise
 */
function showHideEnzyme($name, $dbhandle, $username, $showHide) {
	$qEnz = $dbhandle->prepare("update cyp_enzymes "
				  ."set deleted = ?, who_updated = ? "
				  ."where name = ?");
	$status = $qEnz->execute(array($showHide, $username, $name));

	return($status);
}
