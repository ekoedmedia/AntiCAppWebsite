<?php
/**
 * A process for returning all updates since the given date. Updates are 
 * returned per table, so an update in one row of the tablewill result in the
 * entire table being returned the next time a request is made.
 */

/**
 * Formats update data into json and echo's it for sending
 *
 * @param date The last time the requester received updates
 */
function echoUpdate($date) {
	$updates = getUpdate($date);

	echo(json_encode($updates));
}

/**
 * Checks each table in the database for any updates by pulling records with a 
 * last_update later than the request date. Then it returns all information 
 * (new and old) from those tables that returned at least one row.
 * This could be changed in the future so that only the updated rows are 
 * returned, but this is sufficient for now.
 *
 * @param date The last time the requester received updates
 * @param dbhanle A way to hand a dbhandle to this function in case you have
 *                already included selectStart.php. Defaults to null
 * @return A json string in the following format:
 *         {table_1: [row1, row2], table_2: [row1, row2, row3]}
 *
 *         where each row is of the following format:
 *         {column1_name: c1_value, column2_name: c2_value}
 */
function getUpdate($date, $dbhandle = null) {
	require_once __DIR__."/../dbConnect/selectStart.php";

	$tablesQ = $dbhandle->prepare("show tables");
	$tablesQ->execute();

	//Stores the tables that need to be returned.
	$updatedTables = array();

	$lastDate = $date;

	while ($row = $tablesQ->fetch(PDO::FETCH_ASSOC)) {

		$table = $row["Tables_in_CancerDrugDB"];

		// Must use sprintf to add table to the string because doing it like
		// we do for date puts quotes around the table name.
		$query = sprintf("select MAX(last_updated) as lastDate from %s"
				." where last_updated > ?", $table);

		# By preparing the query ahead of time we prevent sql injection.
		$updatedQ = $dbhandle->prepare($query);
		$updatedQ->execute(array($date));

		$response = $updatedQ->fetch(PDO::FETCH_ASSOC);

		/* Query will return {lastDate: "YYYY-MM-DD hh:mm:ss" if it has been
		 * updated since the provided date, or null if it has not.
		 **/
		if ($response["lastDate"] != null) {
			array_push($updatedTables, $table);
			$lastDate = ($lastDate > $response["lastDate"]) ? $lastDate : $response["lastDate"];
		}
	}

	// PHP calls hashs associative arrays and the are instantiated just like normal
	// arrays.
	$fullTables = array();

	foreach ($updatedTables as $table) {

		// Pull the entire table
		$query = sprintf("select * from %s where deleted = 0", $table);

		$contentsQ = $dbhandle->prepare($query);
		$contentsQ->execute();

		// This is the hash that will be returned.
		$fullTables[$table] = $contentsQ->fetchAll(PDO::FETCH_ASSOC);
	
	}

	$fullTables["lastUpdate"] = $lastDate;

	/* One thing I discovered is that if there is anything in the string that json
	* can't encode it will fail silently and send an empty string.
	 **/
	return($fullTables);
}
?>
	
