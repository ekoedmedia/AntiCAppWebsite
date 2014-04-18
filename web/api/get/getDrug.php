<?php
/**
 * Functinos for returning all the information about a drug 
 */

/**
 * Calls getDrug and the encodes and echos the response
 * 
 * @param name Name of the drug to be returned
 */
function echoDrug($name) {
	$drug = getDrug($name);

	$keys = array_keys($drug);

	foreach ($keys as $key) {
		if (strpos($key, ".") !== false) {
			$drug[$key] = (string) $drug[$key];
		}
	}

	echo(json_encode($drug));
}

/**
 * Combines the drug information from various tables into one associative array
 * and returns that array.
 *
 * @param name Name of the drug to be returned
 * @param dbhandle A way to hand the function a dbhandle in case you have 
 *                 already included dbConnect/connectStart.php. Defaults to
 *                 null.
 * @return An associative array with all the information about the drug. It
 *         contains the following keys:
 *           g_name - String - The generic or scientific name of the drug
 *           t_name - String - The trade or commercial name of the drug
 *           risk - One of Low, Moderate, or High
 *           last_revision - String - The last revision by a professional
 *           pregnancy - String - The drugs effects on pregnancy
 *           breastfeeding - String - The drugs effects on breastfeeding
 *           fertility - String - The drugs effects on fertility
 *           classification - Array - The drugs classification
 *           contraindications - Array - When not to use the drug
 *           metabolism - String - How the drug is metabolized
 *           excretion - String - How the drug is excreted
 *           available - String - What forms the drugs is available in
 *           uo_dose - String - The usual dose for the drug
 *           monitoring - Array - Things to monitor in the patient
 *           administration - Array - How the drug is administered
 *           anti_neoplastic - 1 if anti-neoplastics should be avoided, else 0
 *           frequency - String - When are side effects considered important
 *           sideEffects - Array of associative arrays - keys of 2nd arrays are
 *               name - String - The side effect
 *               severe - 1 if side effect is frequent, else 0
 *           doseAdjusts - Array of associative arrays - keys of 2nd arrays are
 *               problem - String - when to use dose adjustment
 *               note - String - information about adjustment
 *               chart - String - File stream to the chart for the adjustment
 *           drugInteracts - Array of Associative Arrays - 2nd array's keys are
 *               compound - String - What drug interacts with
 *               interaction - String - How they interact
 *               enzyme_effect_type - String - Present if compound is an enzyme
 *           oncUses - Array of Associative arrays - keys of 2nd arrays are
 *               cancer_type - String - Type of cancer
 *               approved - 1 if use on this type of cancer is approved, else 0
 *           precautions - Array of Associative Arrays - keys of 2nd arrays are
 *               name - String - name of the precaution
 *               note - String - Explanation of the precaution
 */
function getDrug($name, $dbhandle = null) {
	require_once __DIR__."/../dbConnect/connectStart.php";

	$fromDrugs = extractFromDrugs($name, $dbhandle);

	// This has most of the information about the drug
	$drug = $fromDrugs[0];

	// This has the other interactions
	$other_interacts = $fromDrugs[1];

	// This has the qt prolonging stuff
	$qt_prolonging = $fromDrugs[2];

	$drug["sideEffects"] = extractFromSideEffects($name, $dbhandle);

	// Dose 
	$doseResults = extractFromDoseAdjust($name, $dbhandle);
	$drug["doseAdjusts"] = $doseResults[0];

	for ($i = 0; $i < count($doseResults[1]); $i++) {
		$drug[$doseResults[1][$i]] = $doseResults[2][$i];
	}

	$drug["drugInteracts"] = extractFromDrugInteracts($name, $dbhandle);

	$drug["oncUses"] = extractFromOncUses($name, $dbhandle);

	$drug["precautions"] = extractFromPrecautions($name, $dbhandle);

	$other_interacts = explode("\n", $other_interacts);
	foreach ($other_interacts as $oInteract) {
		array_push($drug["drugInteracts"], 
			   ["interaction" => "Other Interactions",
			    "compound" => $oInteract]);
	}

	$qts = explode("|", $qt_prolonging);
	foreach ($qts as $qt) {
		array_push($drug["drugInteracts"], ["interaction" => $qt,
			   "compound" => "QT-prolonging agents"]);
	}

	return($drug);
}

/**
 * Pulls all the information about the drug with the given generic name in the
 * drugs table of the database.
 * 
 * @param name Name of the drug
 * @param dbhandle
 * @return An associative array with most of the drug information keys.
 */
function extractFromDrugs($name, $dbhandle) {
	//pull the drug from the drug table and add it to the drug object
	$qDrugs = $dbhandle->prepare("select * from drugs where g_name = ?");

	$qDrugs->execute(array($name));

	$rDrugs = $qDrugs->fetch(PDO::FETCH_ASSOC);

	/* split classifications into an array by | and add them back into
	 * rDrugs
	 */
	$temp = explode("|", $rDrugs["classification"]);
	$rDrugs["classification"] = $temp;

	/* split contraincications on '|'. Add the resulting array back into
	 * rDrugs.
	 */
	$temp = explode("|", $rDrugs["contraindications"]);
	$rDrugs["contraindications"] = $temp;

	/* split monitoring on '|'. Add the resulting array back into rDrugs */
	$temp = explode("|", $rDrugs["monitoring"]);
	$rDrugs["monitoring"] = $temp;

	/* split administration on '|'. Add the resulting array back to rDrugs*/
	$temp = explode("|", $rDrugs["administration"]);
	$rDrugs["administration"] = $temp;

	/* split other_interacts on '|' and add resulting array back to rDrugs.
	 */
	$others = $rDrugs["other_interacts"];
	unset($rDrugs["other_interacts"]);

	$qts = $rDrugs["qt_prolonging"];
	unset($rDrugs["qt_prolonging"]);

	return(array($rDrugs, $others, $qts));

}

/**
 * Pulls data about the requested drug from the dose_adjusts table and returns
 * the data as an array of associative arrays where each column name is a 
 * key in the associative array.
 *
 * @param name Name of the drug
 * @param dbhandle
 * @return Three arrays in an array:
 *           [0] - Array of Associative Arrays where each associative array
 *                 has information about the dose adjust
 *           [1] - Array of names of dose adjustment charts referenced by
 *                 the drug
 *           [2] - The path to the dose adjustment chars
 */
function extractFromDoseAdjust($name, $dbhandle) {
	//pull the dose adjustments and add them to the drug object
	$qDoseAdjusts = $dbhandle->prepare("select problem, note, chart from dose_adjusts where drug = ?");

	$qDoseAdjusts->execute(array($name));

	$rDoseAdjusts = $qDoseAdjusts->fetchAll(PDO::FETCH_ASSOC);

	// Find out which dose adjust charts we need and provide info on 
	// how to get them
	$filenames = array();
	$filepaths = array();
	foreach ($rDoseAdjusts as $doseAdjust) {
		if ($doseAdjust["chart"] != Null) {
			$directories = explode("/", $doseAdjust["chart"]);
			array_push($filenames, end($directories));
			array_push($filepaths, $doseAdjust["chart"]);
			$doseAdjust["chart"] = end($directories);
		}
	}

	return(array($rDoseAdjusts, $filenames, $filepaths));
}

/**
 * Pulls data about the requested drug from the drug_cyp_interacts table and
 * returns the data as an array of associative arrays where each column name is
 * a key in each associative array.
 * 
 * @param name Name of the drug
 * @param dbhandle
 * @return All drug cyp interactions for the drug
 */
function extractFromDrugCyp($name, $dbhandle) {

	// pull the drug cyp interactions, but rename some columns to fit the
	// drug interacts table column names.
	$qDrugCyp = $dbhandle->prepare("select enzyme as compound, drug_effect_type as interaction, enzyme_effect_type from drug_cyp_interacts where drug = ?");
	$qDrugCyp->execute(array($name));

	$rDrugCyp = $qDrugCyp->fetchAll(PDO::FETCH_ASSOC);

	return($rDrugCyp);

}

/**
 * Pulls data about the requested drug from the drug_interacts table and then
 * combines that data with the data from the drug_cyp_interacts table to give
 * an complete list of all drug interactions and returns thats an array of 
 * associative arrays where each column name is a key in each associative array.
 *
 * @param name Name of the drug
 * @param dbhandle
 * @return Array of Associative arrays where each associtive array is a drug
 *         interaction
 */
function extractFromDrugInteracts($name, $dbhandle) {

	$qDrugInter = $dbhandle->prepare("select interaction, compound from drug_interacts where drug = ?");
	$qDrugInter->execute(array($name));

	// This array will hold data from two tables.
	$rDrugInter = array();
	$rDrugInter = $qDrugInter->fetchAll(PDO::FETCH_ASSOC);

	$rDrugCyp = extractFromDrugCyp($name, $dbhandle);

	/* The two array are formatted similarily, so merging them will make a 
	 * nicely homogenous table, but the drug-cyp interactions can be 
	 * identified by the presence of a enzyme_effect_type key in the 
	 * associative array
	 * See extractFromDrugCyp.
	 */
	$rDrugInter = array_merge($rDrugInter, $rDrugCyp);

	return($rDrugInter);

}

/**
 * Pulls data about the requested drug from the onc_uses table and
 * returns the data as an array of associative arrays where each column name is
 * a key in each associative array.
 * 
 * @param name
 * @param dbhandle
 * @return Array of associative arrays where each associative array is an
 *         oncology use
 */
function extractFromOncUses($name, $dbhandle) {

	$qOncUses = $dbhandle->prepare("select cancer_type, approved from onc_uses where drug = ?");
	$qOncUses->execute(array($name));

	return($qOncUses->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Pulls data about the requested drug from the precautions table and
 * returns the data as an array of associative arrays where each column name is
 * a key in each associative array.
 *
 * @param name
 * @param dbhandle
 * @return Array of associative arrays where each associative arrays is a
 *         precaution
 */
function extractFromPrecautions($name, $dbhandle) {

	$qPrecautions = $dbhandle->prepare("select name, note from precautions where drug = ?");
	$qPrecautions->execute(array($name));

	return($qPrecautions->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Pulls data about the requested drug from the side_effects table and
 * returns the data as an array of associative arrays where each column name is
 * a key in each associative array.
 *
 * @param name
 * @param dbhandle
 * @return Array of associative arrays where each associative array is a
 *         side effect
 */
function extractFromSideEffects($name, $dbhandle) {

	$qSideEffects = $dbhandle->prepare("select name, severe from side_effects where drug = ?");
	$qSideEffects->execute(array($name));

	$rSideEffects = $qSideEffects->fetchAll(PDO::FETCH_ASSOC);

	return($rSideEffects);

}

?>
