<?php
/**
 * This file contains two main functions that together return all the
 * information in the database about a certain enzyme. The first is echoEnzyme
 * and it calls the other and then prepares the results for sending over HTTP.
 * The second is getEnzyme, which is in charge of the assembling the info 
 * about the enzyme.
 */

/**
 * Calls getEnzyme, then encodes and echos the result
 *
 * @param enzymeName Name of the enzyme you wish to get
 */
function echoEnzyme($enzymeName) {
	
	$enzyme = getEnzyme($enzymeName);

	echo(json_encode($enzyme));

};

/**
 * Returns all the information about an enzyme whose name is taken as a
 * parameter.
 * 
 * @param enzymeName Name of the enzyme you wish to get
 * @param dbhandle Way to hand this function the dbhandle in case the
 *        dbConnect/connectStart.php file has already been included. Defaults
 *        to null.
 */
function getEnzyme($enzymeName, $dbhandle = null) {

	require_once __DIR__."/../dbConnect/connectStart.php";

	// This will contain all the information about the enzyme
	$enzyme = array();

	// Prepare before executing to prevent sql injection
	$enzymeq = $dbhandle->prepare("select * "
				     ."from cyp_enzymes "
				     ."where name = ?");
	$enzymeq->execute(array($enzymeName));
	$enzymer = $enzymeq->fetch(PDO::FETCH_ASSOC);

	// Return the name of the enzyme as part of the object
	$enzyme["name"] = $enzymer["name"];
	$enzyme["last_updated"] = $enzymer["last_updated"];
	$enzyme["who_updated"] = $enzymer["who_updated"];

	/* Return in the object three arrays as well, one for each type of 
	 * enzyme to compound interaction.
	 */
	$enzyme["Substrate"] = array();
	$enzyme["Inducer"] = array();
	$enzyme["Inhibitor"] = array();
	
	$interactsq = $dbhandle->prepare("select compound,  "
					."interaction, severity "
	 				."from enzyme_interacts "
					."where enzyme = ?");
	$interactsq->execute(array($enzymeName));

	/* Put each compound interaction into the array it belongs to, and
	 * record both the enzyme name and the severity of the interaction.
	 */
	while ($row = $interactsq->fetch(PDO::FETCH_ASSOC)) {
		array_push($enzyme[$row["interaction"]], 
				   array("compound" => $row["compound"],
					 "severity" => $row["severity"]));
	}

	return($enzyme);
};

