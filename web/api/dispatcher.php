<?php
/**
 * This file is the landing point for outside access wanting to use the api. 
 * It can accept get, post, put and delete requests, analizes the request and
 * then includes the file to handle the request. 
 * To find the api specifications see the API documentation of the github wiki.
 *
 * @route "api/dispatcher.php"
 */

// This header allows the internet to access this file
header('Access-Control-Allow-Origin: *');

// This will keep a record of any errors caught at the dispatcher level
//$errors = array();

/** 
 * Handle the get requests. Get requests should only be sent with one key.
 * Acceptable key value pairs are:
 *   Update: date of last update received from server
 *   AreUpdates: date of last update received from server
 *   Enzyme: Name of an enzyme
 *   Drug: Name of a drug
 *   DrugList: Any value
 *   EnzymeList: Any value
 *   getAll: Any value
 *   ChartList: Any value
 */
if ($_SERVER["REQUEST_METHOD"] == "GET") {
	// Get all updated tables
	if (isset($_GET["Update"])) {
		require_once __DIR__."/get/getUpdates.php";
		echoUpdate($_GET["Update"]);
	// Return true if there are updates and false otherwise
	} else if (isset($_GET["AreUpdates"])) {
		require_once __DIR__."/get/areUpdates.php";
		echoAreUpdates($_GET["AreUpdates"]);
	// Return an associative array containing all the enzymes info
	} else if (isset($_GET["Enzyme"])) {
		require_once __DIR__."/get/getEnzyme.php";
		echoEnzyme($_GET["Enzyme"]);
	// Return an associative array containing all the drugs info
	} else if (isset($_GET["Drug"])) {
		require_once __DIR__."/get/getDrug.php";
		echoDrug($_GET["Drug"]);
	// Return a list of drugs
	} else if (isset($_GET["DrugList"])) {
		require_once __DIR__."/get/listDrugs.php";
		echoDrugList();
	// Return a list of enzymes
	} else if (isset($_GET["EnzymeList"])) {
		require_once __DIR__."/get/listEnzymes.php";
		echoEnzymeList();
	} else if (isset($_GET["getAll"])) {
		require_once __DIR__."/get/getUpdates.php";
		echoUpdate("0");
	} else if (isset($_GET["ChartList"])) {
		require_once __DIR__."/get/getDoseCharts.php";
		echoCharts("0");
	} else {
		#addError($REQUEST_KEY_ERROR);
		$a = 1; //Just to be sure the else doesn't complain for now
	}
}

/**
 * Handles request if it is a put request. Put requests should only have one
 * key in a json, though in the future we hope to add authentication, which
 * will use other keys.
 * Acceptable key value pairs are:
 *   Enzyme: An associative array with keys name, Substrate, Inhibitor, and
 *           Inducer
 *   Drug: An associative array with at least a g_name and risk key.
 */
if ($_SERVER["REQUEST_METHOD"] == "PUT") {

	$data = json_decode(file_get_contents("php://input"), True);
	
	include __DIR__."/tools/authenticate.php";
	$result = authenticate($data["email"],$data["password"]);

	if (!$result) {
		exit("");
	}

	if (isset($data["Enzyme"])) {
		require_once __DIR__."/put/putEnzyme.php";
		echoInsertEnzyme($data["Enzyme"]);
	} else if (isset($data["Drug"])) {
		require_once __DIR__."/put/putDrug.php";
		echoInsertDrug($data["Drug"]);
	} else {
		#addError($REQUEST_KEY_ERROR);
		$a = 1; //Just to be sure the else doesn't complain for now
	}
}

/**
 * Handle the delete requests. Deletes should only be sent with one key.
 * Acceptable key value pairs are:
 *   Enzyme: A name of an enzyme
 *   Drug: A name of a drug
 */
if ($_SERVER["REQUEST_METHOD"] == "DELETE") {

	/* The true in the second parameter makes the request into an 
	 * associative array so we can pull values from it. Otherwise its a 
	 * generic object.
	 */
	$data = json_decode(file_get_contents("php://input"), True);
	
	include __DIR__."/tools/authenticate.php";
	$result = authenticate($data["email"],$data["password"]);

	if (!$result) {
		exit("");
	}

	if (isset($data["Drug"])) {
		require_once __DIR__."/delete/deleteDrug.php";
		echoDeleteDrug($data["Drug"]);
	} else if (isset($data["Enzyme"])) {
		require_once __DIR__."/delete/deleteEnzyme.php";
		echoDeleteEnzyme($data["Enzyme"]);
	} else {
		#addError($REQUEST_KEY_ERROR);
		$a = 1; //Just to be sure the else doesn't complain for now
	}
}

/**
 * Handles post requests. Post requests only should contain one key.
 * Acceptable key value pairs are:
 *   enzyme: Information about what has changed in the enzyme
 *   drug: Information about what has changed in the drug
 */
if($_SERVER["REQUEST_METHOD"] == "POST") {

	$data = json_decode(file_get_contents("php://input"), True);

	include __DIR__."/tools/authenticate.php";
	$result = authenticate($data["email"],$data["password"]);

	if (!$result) {
		exit("");
	}

	if(isset($data["drug"])) {
		require_once __DIR__."/post/postDrug.php";
		echoPostDrug($data["drug"]);
	} else if(isset($data["enzyme"])) {
		require_once __DIR__."/post/postEnzyme.php";
		echoPostEnzyme($data["enzyme"]);
	} else {
		#addError($REQUEST_KEY_ERROR);
		$a = 1; //Just to be sure the else doesn't complain for now
	}
}
	
?>
