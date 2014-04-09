<?php
/**
 * Functions for hiding and showing drug entries and all associated values in
 * other tables. No values are actually deleted, rather the "deleted" column in
 * the appropriate rows are set to True, so they will not be sent from the 
 * database on get requests.
 *
 * Delete drug requests should contain the key "Drug" with the value being the 
 * name of the drug that needs to be deleted.
 */

/* Tables that need to be updated for the delete request:
 *  - dose_adjusts
 *  - drug_cyp_interacts
 *  - drug_interacts
 *  - onc_uses
 *  - precautions
 *  - protocol_drugs
 *  - side_effects
 *  - drugs
 */

/**
 * Calls showHideDrug with showHide = 1 (it only hides drugs for now and then
 * encodes and echos the status (true if succeeded, false otherwise)
 *
 * @param name Name of drug to be hidden
 */
function echoDeleteDrug($name) {
	$response = showHideDrug($name, 'API user', 1);

	echo(json_encode($response));
}

/**
 * Starts a transaction so that all of the deletions happen, or none of them
 * do. Then set the deleted variable to true on all rows connected with the
 * drug. showHide should be 1 to hide drugs and 0 to show them again.
 *
 * @param name Name of the drug to be hidden or shown
 * @param username Who is doing the deleting
 * @param showHide 1 if hiding, 0 if showing
 * @param dbhandle A way to hand the function a dbhandle in case
 *        connectStart.php has already been included
 * @return True if update succeeded, False otherwise
 */
function showHideDrug($name, $username, $showHide, $dbhandle = null) {
	require_once __DIR__."/../dbConnect/connectStart.php";

	$dbhandle->beginTransaction();

	$status = True;

	// Call one function for each table that needs to be changed. status
	// will be false if any of the queries don't work.
	$status = $status && showHideSideEffects($name, $dbhandle, $username, $showHide);
	$status = $status && showHideDose($name, $dbhandle, $username, $showHide);
	$status = $status && showHideDrugCyp($name, $dbhandle, $username, $showHide);
	$status = $status && showHideDrugInt($name, $dbhandle, $username, $showHide);
	$status = $status && showHideOncs($name, $dbhandle, $username, $showHide);
	$status = $status && showHidePre($name, $dbhandle, $username, $showHide);
	$status = $status && showHideDrugs($name, $dbhandle, $username, $showHide);

	if ($status) {
		$dbhandle->commit();
	} else {
		$dbhandle->rollBack();
	}

	return ($status);
}

/**
 * Set deleted = true in all rows of side_effects related to this drug
 *
 * @param name Name of the drug to be hidden or shown
 * @param username Who is doing the deleting
 * @param showHide 1 if hiding, 0 if showing
 * @param dbhandle The database connection
 * @return True if update succeeded, False otherwise
 */
function showHideSideEffects($name, $dbhandle, $username, $showHide) {
	$qSideEffects = $dbhandle->prepare("UPDATE side_effects "
					  ."SET deleted = ?, who_updated = ? "
					  ."WHERE drug = ?");
	$status = $qSideEffects->execute(array($showHide, $username, $name));

	return ($status);

}

/**
 * Set deleted = true inall rows of dose_adjusts with the drug
 *
 * @param name Name of the drug to be hidden or shown
 * @param username Who is doing the deleting
 * @param showHide 1 if hiding, 0 if showing
 * @param dbhandle The database connection
 * @return True if update succeeded, False otherwise
 */
function showHideDose($name, $dbhandle, $username, $showHide) {
	$qDose = $dbhandle->prepare("UPDATE dose_adjusts "
				   ."SET deleted = ?, who_updated = ? "
				   ."WHERE drug = ?");
	$status = $qDose->execute(array($showHide, $username, $name));

	return($status);
}

/**
 * Set deleted = true in all rows of drug_cyp_interacts with the drug
 *
 * @param name Name of the drug to be hidden or shown
 * @param username Who is doing the deleting
 * @param showHide 1 if hiding, 0 if showing
 * @param dbhandle The database connection
 * @return True if update succeeded, False otherwise
 */
function showHideDrugCyp($name, $dbhandle, $username, $showHide) {
	$qDrugCyp = $dbhandle->prepare("UPDATE drug_cyp_interacts "
				      ."SET deleted = ?, who_updated = ? "
				      ."WHERE drug = ?");
	$status = $qDrugCyp->execute(array($showHide, $username, $name));

	return($status);
}

/** 
 * Set deleted = true in all rows of drug_interacts with the drug
 *
 * @param name Name of the drug to be hidden or shown
 * @param username Who is doing the deleting
 * @param showHide 1 if hiding, 0 if showing
 * @param dbhandle The database connection
 * @return True if update succeeded, False otherwise
 */
function showHideDrugInt($name, $dbhandle, $username, $showHide) {
	$qDrugInt = $dbhandle->prepare("UPDATE drug_interacts "
				      ."SET deleted = ?, who_updated = ? "
				      ."WHERE drug = ?");
	$status = $qDrugInt->execute(array($showHide, $username, $name));

	return($status);
}

/**
 * Set deleted = true in all rows of onc_uses with the drug
 *
 * @param name Name of the drug to be hidden or shown
 * @param username Who is doing the deleting
 * @param showHide 1 if hiding, 0 if showing
 * @param dbhandle The database connection
 * @return True if update succeeded, False otherwise
 */
function showHideOncs($name, $dbhandle, $username, $showHide) {
	$qOnc = $dbhandle->prepare("UPDATE onc_uses "
				      ."SET deleted = ?, who_updated = ? "
				      ."WHERE drug = ?");
	$status = $qOnc->execute(array($showHide, $username, $name));

	return($status);
}

/**
 * Set deleted = true in all rows of precautions with the drug
 *
 * @param name Name of the drug to be hidden or shown
 * @param username Who is doing the deleting
 * @param showHide 1 if hiding, 0 if showing
 * @param dbhandle The database connection
 * @return True if update succeeded, False otherwise
 */
function showHidePre($name, $dbhandle, $username, $showHide) {
	$qPre = $dbhandle->prepare("UPDATE precautions "
				      ."SET deleted = ?, who_updated = ? "
				      ."WHERE drug = ?");
	$status = $qPre->execute(array($showHide, $username, $name));

	return($status);
}

/**
 * Set deleted = true for the given drug
 *
 * @param name Name of the drug to be hidden or shown
 * @param username Who is doing the deleting
 * @param showHide 1 if hiding, 0 if showing
 * @param dbhandle The database connection
 * @return True if update succeeded, False otherwise
 */
function showHideDrugs($name, $dbhandle, $username, $showHide) {
	$qDrugs = $dbhandle->prepare("UPDATE drugs "
				      ."SET deleted = ?, who_updated = ? "
				      ."WHERE g_name = ?");
	$status = $qDrugs->execute(array($showHide, $username, $name));

	return($status);
}

