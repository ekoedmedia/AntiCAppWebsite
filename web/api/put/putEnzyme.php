<?php
/**
 * Functions allowing the user to add a new enzyme. The enzyme
 * should be sent as an associative array with four keys: "name", "Substrates",
 * "Inhibitors" and "Inducers". name is the name of the enzyme. The others are
 * the different type of interactions the enzyme can have, and their values are
 * arrays of compounds that interact in that way with that enzyme.
 */

/**
 * Calls insertEnzyme and then encodes and echos the response it receives
 *
 * @param enzyme The enzyme associative array to be added
 */
function echoInsertEnzyme($enzyme) {
	$result = insertEnzyme($enzyme, 'Web API');

	echo(json_encode($result));
}

/**
 * Inserts the enzyme associative array in enzyme into the cyp_enzyme and 
 * enzyme_interacts table. Everything is done in one transaction so that either
 * everything is added or none of it is.
 *
 * @param enzyme The enzyme associative array to be added
 * @param username The name of the user doing the adding
 * @param dbhandle A way to hand this function a dbhandle in case 
 *                 connectStart.php has already been included.
 * @return True if the query suceeded and false otherwise
 */
function insertEnzyme($enzyme, $username = 'unknown', $dbhandle = null) {
	require_once __DIR__."/../dbConnect/connectStart.php";

	$dbhandle->beginTransaction();

	$status = True;

	$status = $status && insertCypEnzyme($enzyme, $dbhandle, $username);
	$status = $status && insertInteractions($enzyme, $dbhandle, $username);

	if ($status) {
		$dbhandle->commit();
	} else {
		$dbhandle->rollback();
	}

	return($status);
}

/**
 * Inserts the enzyme name into cyp_enzymes and sets the user who last modified
 * the row to username
 *
 * @param enzyme The enzyme associative array to be added
 * @param dbhandle The database connection
 * @param username The user doing the updating
 * @return True if the query succeeded and false otherwise
 */
function insertCypEnzyme($enzyme, $dbhandle, $username) {
	$insertQuery = "INSERT INTO cyp_enzymes (name, who_updated) VALUES "
	              ."(?, ?)";

	$status = True;

	$insertQ = $dbhandle->prepare($insertQuery);
	$status = $status && $insertQ->execute(array($enzyme["name"], $username));

	return($status);
}

/**
 * Inserts the enzyme interactions into enzyme_interacts with the proper
 * enzyme interaction type and the username of the person doing the updating
 *
 * @param enzyme The enzyme associative array to be added
 * @param dbhandle The database connection
 * @param username The user adding the information
 * @return True if the query succeeded and false otherwise
 */
function insertInteractions($enzyme, $dbhandle, $username) {
	$insertQuery = "INSERT INTO enzyme_interacts (interaction, enzyme, "
	              ."compound, severity, who_updated) VALUES "
	              ."(?, ?, ?, ?, ?)";

	$findQuery = "SELECT * FROM compounds WHERE g_name = ?";
	$compInsertQuery = "INSERT INTO compounds (g_name, who_updated) "
	                  ."VALUES (?, ?)";

	$insertQ = $dbhandle->prepare($insertQuery, 
		array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => True));
	$findQ = $dbhandle->prepare($findQuery, 
		array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => True));
	$compQ = $dbhandle->prepare($compInsertQuery, 
		array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => True));

	$status = True;

	foreach ($enzyme["Substrate"] as $substrate) {
		$findQ->execute(array($substrate["compound"]));
		$compound = $findQ->fetchAll(PDO::FETCH_ASSOC);

		if (empty($compound)) {
			$status = $status && $compQ->execute(array(
				$substrate["compound"], $username));
		}

 	 	$status = $status && $insertQ->execute(array("Substrate", 
			$enzyme["name"], $substrate["compound"], 
			$substrate["severity"], $username));
		
	}

	foreach ($enzyme["Inhibitor"] as $inhibitor) {
		$findQ->execute(array($inhibitor["compound"]));
		$compound = $findQ->fetchAll(PDO::FETCH_ASSOC);

		if (empty($compound)) {
			$status = $status && $compQ->execute(array(
				$inhibitor["compound"], $username));
		}

 	 	$status = $status && $insertQ->execute(array("Inhibitor", 
			$enzyme["name"], $inhibitor["compound"], 
			$inhibitor["severity"], $username));
	}

	foreach ($enzyme["Inducer"] as $inducer) {
		$findQ->execute(array($inducer["compound"]));
		$compound = $findQ->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($compound)) {
			$status = $status && $compQ->execute(array(
				$inducer["compound"], $username));
		}

 	 	$status = $status && $insertQ->execute(array("Inducer", 
			$enzyme["name"], $inducer["compound"], 
			$inducer["severity"], $username));
	}

	return($status);
}
