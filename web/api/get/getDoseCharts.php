<?php
/**
 * Functions for returning a list of the paths to dose adjustment charts
 */

/**
 * Calls getCarts and then encodes and echos the response
 */
function echoCharts() {
	$chartList = getCharts();

	echo(json_encode($chartList));
}

/**
 * Gets the path to all charts referenced by the dose adjusts table, then
 * calculates the name of the drug and the name of the problem that the 
 * chart is refereced by from the name of the file.
 *
 * @param dbhandle A way to hand a dbhandle to the function in case you have
 *                 already included dbConnect/connectStart.php.
 *                 Defaults to null
 * @return An array of associative arrays, where each element is a chart, and
 *         contains a the following keys: drug, problem, chart
 *         drug conatins the name of the drug for the chart, problem is the
 *         name of the problem the dose is being adjusted for, and chart is
 *         the path from the web root to the chart. 
 */
function getCharts($dbhandle = null) {
	require_once(__DIR__."/../dbConnect/connectStart.php");

	$chartsQ = $dbhandle->prepare("SELECT DISTINCT chart from dose_adjusts where chart is not null and chart <> '' and deleted = 0");
	$chartsQ->execute();

	$results = $chartsQ->fetchAll(PDO::FETCH_ASSOC);

	$charts = array();

	foreach ($results as $item) {
		$folders = explode("/", $item["chart"]);
	
		// end($folders) should be the document name
		$parts = explode(".", end($folders));

		$parts = explode("_", $parts[0]);

		// The naming of the files is standardized, so we can trust that
		// the correct values are going to the correct key
		$temp = array();
		$temp["drug"] = ucfirst($parts[0]);
		$temp["problem"] = ucfirst($parts[1]);
		$temp["chart"] = $item["chart"];

		array_push($charts, $temp);
	}

	return($charts);

}
