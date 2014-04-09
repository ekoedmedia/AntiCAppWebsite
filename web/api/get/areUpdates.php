<?php
/**
 * Functions for seeing if there have been updates since a given date
 */

/**
 * Calls getAreUpdates and encodes and echo the response
 *
 * @params date
 */
function echoAreUpdates($date) {
	$areUpdates = getAreUpdates($date);

	echo(json_encode($areUpdates));
}

/**
 * Takes a date and finds if there have been updates to the database since
 * that date.
 *
 * @param date
 * @param dbhandle
 * @return True if the database has been updated, False otherwise
 */
function getAreUpdates($date, $dbhandle = null) {

	// get the DB connection
	require_once __DIR__."/../dbConnect/selectStart.php";

	// get the list of tables in the database
	$qTables = $dbhandle->prepare("show tables");
	$qTables->execute();

	$answer = False;

	while ($row = $qTables->fetch(PDO::FETCH_ASSOC)) {

		$table = $row["Tables_in_CancerDrugDB"];

		// Check for entries since the date in each table
		$query = sprintf("select MAX(last_updated) as lastDate from %s"
				." where last_updated > ?", $table);
		$qUpdates = $dbhandle->prepare($query);
		$qUpdates->execute(array($date));

		$response = $qUpdates->fetch(PDO::FETCH_ASSOC);

		// If there updates change the response to true
		if ($response["lastDate"] != null) {
			$answer = True;
		}

	}

	return($answer);
}

?>
