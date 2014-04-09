<?php
/**
 * Functions to add a drug to the database. The drug object should follow the 
 * same format as the drug returned in the get request with the exception of
 * the dose adjustments, where the chart is the name of the temperary file
 * added by the file upload and the additional key chart_type holds the type
 * of the picture (png, jpeg, etc) as told by the file upload object.
 *
 * @see ../get/getDrug.php
 */

/**
 * Calls putDrug and then encodes and echos the result
 *
 * @param drug The drug associative array
 */
function echoInsertDrug($drug) {
	$result = putDrug($drug, "Web API");
	
	echo(json_encode($result));
}

/**
 * Prepares the drug object ot be inserted by making sure that if any fields
 * have been ommited they are added with an empty string as a value. The only
 * required keys are g_name and risk. Then it calls all the insert functions/
 * Also ensures taht all of the drug or none of it is added by using a 
 * transaction
 *
 * @param drug The drug associative array to be added to the databse
 * @param username The username of the one doing the adding
 * @param dbhandle A way to hand the function a dbhandle in case the 
 *                 connectStart.php file has already been included.
 * @return True if the query succeeded, and false otherwise
 */
function putDrug($drug, $username = 'unknown', $dbhandle = null) {
	require_once __DIR__."/../dbConnect/connectStart.php";

	$dbhandle->beginTransaction();

	$drugName = $drug["g_name"];

	/* Adding a null value is okay, but selecting an unset field  from the
	 * drug object will cause it to crash, so we need to add any fields 
	 * that aren't there to the object, and set their values to Null
	 * 
	 * Unfortunately I couldn't think of a better way of getting the 
	 * column name then just manually inputing them, so if any changes are
	 * made to the structure of the drug object, it will need to be 
	 * updated here.
	 */
	$expectedColumns = array("g_name", "t_name", "risk", "last_revision",
	                         "classification", "pregnancy", "breastfeeding",
	                         "fertility", "metabolism", "excretion", 
	                         "available", "uo_dose", "contraindications",
	                         "monitoring", "administration", 
	                         "anti_neoplastic", "frequency", "sideEffects",
	                         "doseAdjusts", "drugInteracts", "oncUses",
	                         "precautions");

	foreach ($expectedColumns as $columnName) {
		if (!isset($drug[$columnName])) {
			$drug[$columnName] = "";
		}
	}
	
	// This status is used to determine if any of the queries fail
	$status = True;

	$status = $status && insertIntoDrugs($drug, $dbhandle, $username);
	$status = $status && insertIntoDrugInter($drugName, $drug["drugInteracts"], $dbhandle, $username);
	$status = $status && insertIntoDoseAdj($drugName, $drug["doseAdjusts"], $dbhandle, $username, $drug);
	$status = $status && insertIntoSideEffects($drugName, $drug["sideEffects"], $dbhandle, $username);
	$status = $status && insertIntoOncs($drugName, $drug["oncUses"], $dbhandle, $username);
	$status = $status && insertIntoPrec($drugName, $drug["precautions"], $dbhandle, $username);

	if ($status) {
		$dbhandle->commit();
	} else {
		$dbhandle->rollBack();
	}

	return($status);

}

/**
 * Inserts the relevant parts of the drug associative array into the drug table
 *
 * @param drug The drug associative array to be added to the databse
 * @param username The username of the one doing the adding
 * @param dbhandle The database connection
 * @return True if the query succeeded, and false otherwise
 */
function insertIntoDrugs($drug, $dbhandle, $username) {
	$findQuery = "SELECT * FROM compounds where g_name = ?";

	$insertQuery = "INSERT INTO compounds (g_name, who_updated) VALUES (?, ?)";

	// To make your life easier, there are 17 fields.
	$query = "INSERT INTO drugs (g_name, t_name, risk, last_revision, "
	                           ."classification, pregnancy, breastfeeding, "
	                           ."fertility, metabolism, excretion, "
	                           ."available, uo_dose, contraindications, "
	                           ."monitoring, administration, "
	                           ."anti_neoplastic, frequency, who_updated)"
	        ."VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

	//Find out if the drug name is already in the compounds table
	$findDrugQ = $dbhandle->prepare($findQuery);
	$status = $findDrugQ->execute(array($drug["g_name"]));

	$compound = $findDrugQ->fetchAll(PDO::FETCH_ASSOC);

	//If the drug isn't in the compounds table, add it
	if (empty($compound)) {
		$compQ = $dbhandle->prepare($insertQuery);
		$status = $status && $compQ->execute(array($drug["g_name"], $username));
	}

	//Now add the drug to the drugs table. Yes, it's monstrous
	$insertDrugQ = $dbhandle->prepare($query);
	$status = $status & $insertDrugQ->execute(array($drug["g_name"], 
		$drug["t_name"], $drug["risk"], $drug["last_revision"],
		implode("|", $drug["classification"]), $drug["pregnancy"], 
		$drug["breastfeeding"], $drug["fertility"],
		$drug["metabolism"], $drug["excretion"], $drug["available"],
		$drug["uo_dose"], implode("|", $drug["contraindications"]),
		implode("|", $drug["monitoring"]),
		implode("|", $drug["administration"]), 
		$drug["anti_neoplastic"], $drug["frequency"], $username));

	return($status);

}

/**
 * Adds the drug interactions to the appropriate tables. In specific, this 
 * function deals with four types of interactions: drug-compound interactions,
 * which are added to the drug_interacts table; drug-cyp interactions, which 
 * are added to the drug_cyp_interacts table; and qt and other interactions
 * which are added to the qt_interacts and other_interacts columns of the drug
 * table repectively.
 *
 * @param drug The g_name of the drug being added
 * @param interactions The array of interactions to be added
 * @param username The username of the one doing the adding
 * @param dbhandle The database connection
 * @return True if the query succeeded, and false otherwise
 */
function insertIntoDrugInter($drug, $interactions, $dbhandle, $username) {
	if (empty($interactions)) {
		return(True);
	}
	/* These are the queries that we will use to actually input the
	 * interaction data.
	 */
	$drugInterInsertQuery = "INSERT INTO drug_interacts (interaction, "
	                       ."compound, drug, who_updated) "
	                       ."VALUES (?, ?, ?, ?)";
	$enzInterInsertQuery = "INSERT INTO drug_cyp_interacts (enzyme, "
	                      ."drug, drug_effect_type, enzyme_effect_type, "
	                      ."who_updated) VALUES (?, ?, ?, ?, ?)";
	$otherInsQuery = "UPDATE drugs SET qt_prolonging = ?, other_interacts "
	                ."= ?, who_updated = ? WHERE g_name = ?";
	
	// Prepare these queries
	$drugInterQ = $dbhandle->prepare($drugInterInsertQuery);
	$enzInterQ = $dbhandle->prepare($enzInterInsertQuery);
	$otherInterQ = $dbhandle->prepare($otherInsQuery);

	// Here are the additional queries we will need to make this work
	$compoundInsertQuery = "INSERT INTO compounds (g_name, who_updated) "
	                      ."VALUES (?, ?)";
	$findCompounds = "SELECT * FROM compounds WHERE g_name = ?";

	// Prepare these queries too
	$insertCompQ = $dbhandle->prepare($compoundInsertQuery);
	$findQ = $dbhandle->prepare($findCompounds);

	$otherInteracts = "";
	$qtProlonging = "";

	$status = True;

	foreach ($interactions as $interact) {
		/* First, make sure the needed fields are present, and if they
		 * aren't, add them.
		 */
		if (!(isset($interact["interaction"]) &&
		     isset($interact["compound"]))) {
			continue;
		}

		if ($interact["interaction"] == "Other Interactions") {
			$otherInteracts .= $interact["compound"] . "\n";
			continue;
		} else if ($interact["compound"] == "QT-prolonging agents") {
			$qtProlonging .= $interact["interaction"] . "|";
			continue;
		}

		if (isset($interact["enzyme_effect_type"])) {
			$status = $status && $enzInterQ->execute(array(
				$interact["compound"], $drug,
				$interact["interaction"],
				$interact["enzyme_effect_type"], $username));
			
		} else {
			// If the compound isn't present in the compounds 
			// table, add it
			$status = $status && $findQ->execute(array(
				$interact["compound"]));
			$compsPresent = $findQ->fetchAll(PDO::FETCH_ASSOC);

			if (empty($compsPresent)) {
				$status = $status && $insertCompQ->execute(
					array($interact["compound"], $username));
			}

			$status = $status && $drugInterQ->execute(array(
				$interact["interaction"], $interact["compound"],
				$drug, $username));
		}
	}
	
	$qtProlonging = ($qtProlonging == "") ? "" : substr($qtProlonging, 0, -1);
	$otherInteracts = ($otherInteracts == "") ? "" : substr($otherInteracts, 0, -1);

	$status = $status && $otherInterQ->execute(array($qtProlonging,
		$otherInteracts, $username, $drug));

	return($status);

}

/**
 * Inserts dose adjustments to the dose_adjusts table, and moves any uploaded
 * charts to their appropriate folder.
 *
 * @param drug The g_name of the drug being added
 * @param doseAdj The array of dose adjustments to be added
 * @param username The username of the one doing the adding
 * @param dbhandle The database connection
 * @return True if the query succeeded, and false otherwise
 */
function insertIntoDoseAdj($drug, $doseAdj, $dbhandle, $username) {
	if (empty($doseAdj)) {
		return(True);
	}

	$insertQuery = "INSERT INTO dose_adjusts (drug, problem, note, chart, "
	              ."who_updated) VALUES (?, ?, ?, ?, ?)";

	$status = True;

	foreach ($doseAdj as $adjustment) {
		// Simply ignore dose adjustments that are missing a note or a
		// problem field.
		if (empty($adjustment["problem"]) && 
		     empty($adjustment["note"])) {
			continue;
		}

		/* Vary few dose adjustments have a chart, but if they do, we
		 * save it as a new file and add the full path to the image
		 * directory to it. Otherwise we add the field for them so it
	 	 * exists.
		 */
		if (!empty($adjustment["chart"])) {
			$chart = $adjustment["chart"];

			$type = explode("/", $adjustment["chart_type"]);
			$type = end($type);

			if (substr($chart,0,5) != "api/d") {
				$parts = explode(".", $chart);
				$chart_value = "api/doseAdjustCharts/".$drug."_"
					      .$adjustment["problem"]."."
					      .$type;
				$destination = "/var/www/web/" . $chart_value;
				move_uploaded_file($chart, $destination);
				$adjustment["chart"] = $chart_value;
			}

		} else {
			$adjustment["chart"] = "";
		}

		$insertQ = $dbhandle->prepare($insertQuery);
		$status = $status && $insertQ->execute(
			array($drug, $adjustment["problem"],
		              $adjustment["note"], $adjustment["chart"], 
			      $username));
		
	}

	return($status);
}

/**
 * Inserts the drugs side effects into the side_effects table.
 *
 * @param drug The g_name of the drug being added
 * @param effects The array of side effects to be added
 * @param username The username of the one doing the adding
 * @param dbhandle The database connection
 * @return True if the query succeeded, and false otherwise
 */
function insertIntoSideEffects($drug, $effects, $dbhandle, $username) {
	if ($effects == Null) {
		return(True);
	}

	$insertQuery = "INSERT INTO side_effects (drug, name, severe, "
	              ."who_updated) VALUES (?, ?, ?, ?)";

	// Tracks the status of the db operations, and will return false if one
	// fails
	$status = True;

	foreach ($effects as $sideEffect) {
		// Don't insert a side effect without a name
		if (!isset($sideEffect["name"])) {
			continue;
		}
		
		// Default the severity of the side effect to not severe
		if (!isset($sideEffect["severe"])) {
			$sideEffect["severe"] = False;
		}

		$insertQ = $dbhandle->prepare($insertQuery);
		$status = $status && $insertQ->execute(
			array($drug, $sideEffect["name"], 
			      $sideEffect["severe"], $username));

	}

	return($status);
}

/**
 * Inserts the drugs oncology uses into the onc_uses table.
 *
 * @param drug The g_name of the drug being added
 * @param uses The array of oncology uses to be added
 * @param username The username of the one doing the adding
 * @param dbhandle The database connection
 * @return True if the query succeeded, and false otherwise
 */
function insertIntoOncs($drug, $uses, $dbhandle, $username) {
	if ($uses == Null) {
		return(True);
	}

	$insertQuery = "INSERT INTO onc_uses (drug, cancer_type, approved, "
	              ."who_updated) VALUES (?, ?, ?, ?)";

	// Tracks the status of the db operations, and will return false if one
	// fails
	$status = True;

	foreach ($uses as $oncUse) {
		// Don't insert a onc_use without a cancer_type
		if (!isset($oncUse["cancer_type"])) {
			continue;
		}
		
		// Default the onc_use to not approved
		if (!isset($oncUse["approved"])) {
			$oncUse["approved"] = False;
		}

		$insertQ = $dbhandle->prepare($insertQuery);
		$status = $status && $insertQ->execute(
			array($drug, $oncUse["cancer_type"], 
			      $oncUse["approved"], $username));

	}

	return($status);
}

/**
 * Inserts the drugs precations into the precautions table.
 *
 * @param drug The g_name of the drug being added
 * @param precautions The array of oncology precautions to be added
 * @param username The username of the one doing the adding
 * @param dbhandle The database connection
 * @return True if the query succeeded, and false otherwise
 */
function insertIntoPrec($drug, $precautions, $dbhandle, $username) {
	if ($precautions == Null) {
		return(True);
	}

	$insertQuery = "INSERT INTO precautions (drug, name, note, "
	              ."who_updated) VALUES (?, ?, ?, ?)";

	// Tracks the status of the db operations, and will return false if one
	// fails
	$status = True;

	foreach ($precautions as $prec) {
		// Don't insert a precaution without a name or a note
		if (!(isset($prec["name"]) &&
		     isset($prec["note"]))) {
			continue;
		}

		$insertQ = $dbhandle->prepare($insertQuery);
		$status = $status && $insertQ->execute(
			array($drug, $prec["name"], 
			      $prec["note"], $username));

	}

	return($status);
}
?>
