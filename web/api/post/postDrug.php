<?php
 /* Post Drug REST API. Based on the parameters of the object sent from the
 * website or using REST, we use the functions presented in this file. You can
 * update names, or other properties of a drug, or update everything
 * at the same time. This file has functions that makes update of
 * drug information, deletions, additions of rows from other other tables
 * that are related to the drug being updated.
 */

#require_once __DIR__."/../dbConnect/connectStart.php";

/* Tables that need to be updated for the post request if update
 * only by name of drug: Note this is done by cascading in sql
 *  - dose_adjusts
 *  - drug_cyp_interacts
 *  - drug_interacts
 *  - onc_uses
 *  - precautions
 *  - side_effects
 *  - drugs
 */

/* Function to make sure drug can be updated via REST API
 * as a web service. User needs to send JSON format data with
 * old_name of drug with it
 */
function echoPostDrug($drug) {
        $result = updateDrug($drug, "Web API");

        echo(json_encode($result));
}

/* This function is the main function to call when updating a drug
 * It takes a drug array with update information, the user who
 * is updating the drug, the old name of the drug if name is updated,
 * and the dbhandle used to execute sql queries into the database

 * drug array => ("drug" => array("name" => "test1", .....),
          "dose_adjusts => array("values" => array(array("drug" => "test1", ...),array(...)),
                         ("options" => array(array("status => "added", "pkey" => "test1"), 
                                array(....)))
                   ),
         "side_effects => array("values" => array(array("drug" => "test1", ...),array(...)),
                                         ("options" => array(array("status => "added", "pkey" => "test1"), 
                                                                array(....)))
                                   ),
                  "..." => ... )
 
 * The above drug array is a combination of dictionaries and arrays that can be 
 * used to add, update or delete information from different tables. The first
 * dictionary contains keys as table names whose rows are being changed. 
 * Multiple rows from these tables such as dose_adjusts can be changed and 
 * hereby array("values") is an array of dictionaries of rows that are changed
 * ie added deleted or edited. The array("options") is again an array of 
 * dictionaries mapping to the rows that are being changed with status
 * one of added, deleted or edited. The "pkey" keys conform to the primary
 * keys of tables whose rows are either edited or deleted. You need these
 * old pkeys to edit or delete rows from tables. The "drug" table will only
 * be updated and therefore does not have any values or options keys inside
 */

function updateDrug($drug, $user = "unknown", $name = "", $dbhandle=null) {

    require_once __DIR__."/../dbConnect/connectStart.php";

    $dbhandle->beginTransaction();
    $status = True;
    // So that the WEB REST can work by giving the old name in the dictionary
    if($name == "") {
        $name = $drug["old_name"];
    }

    /* The model for $drug and other tables with names of the colums that could be changed 
    */
    $expectedColumn = array("drugs", "drug_interacts","side_effects", "dose_adjusts", "precautions", "onc_uses");
    $expectedColumnDrugs = array("g_name", "t_name", "risk", "last_revision", "classification", "pregnancy", "breastfeeding",
                    "fertility", "metabolism", "excretion", "available", "uo_dose", "contraindications",
                        "monitoring", "administration", "anti_neoplastic", "other_interacts", "qt_prolonging");
    $expectedColumnDrugInts = array("drug", "interaction", "compound");
    $expectedColumnSEffects = array("drug", "name", "severe", "frequency");
    $expectedColumnDose = array("drug", "problem", "note", "chart");
    $expectedColumnPre = array("drug", "name", "note");
    $expectedColumnOncs = array("drug", "cancer_type", "approved");
    $expectedColumnDrugCyp = array("drug", "enzyme", "drug_effect_type", "enzyme_effect_type");

    $new_name = "";

    /* For every table check if they exist in the drug dictionary */

    foreach($expectedColumn as $columnName) {
        if (isset($drug[$columnName]) && $columnName=="drugs") {

            $returnStmt = getStmtForDrugs($drug[$columnName], $expectedColumnDrugs, $name, $dbhandle);
            $updateStmt = $returnStmt[1];
            $new_name = $returnStmt[0];
            $updateVal = $returnStmt[2];
            $updateStmt = $updateStmt."who_updated = ? WHERE g_name = ?";
            array_push($updateVal, $user, $name);
            // To make sure that compounds table has new drug name 
            // and other updates to other table can occur with this new_name, since cascaded
            if ($new_name != null) {
                $table["compound"] = $new_name;
                $status = $status && updateToCompounds($table, $user, $dbhandle);
                $name = $new_name;
            }
            //Update drug using statement and values for sql injection protection
                    
            $status = $status && updateToDrugs($updateStmt, $updateVal, $dbhandle);
        }
        
        if (isset($drug[$columnName]) && $columnName=="drug_interacts") {

            $updateDrugInt = $drug[$columnName];
            $updateStmt = "";
                        
            $status = $status && updateToInterTables($updateDrugInt, $expectedColumnDrugInts, $expectedColumnDrugCyp, $name, $dbhandle, $user);
                        
        }

        if (isset($drug[$columnName]) && $columnName=="side_effects") {

            $updateSEffects = $drug[$columnName];
            $updateStmt = "";
                        
            $status = $status && updateToTable($updateSEffects, $expectedColumnSEffects, $name, $dbhandle, $user, $columnName);
                        
        }

        if (isset($drug[$columnName]) && $columnName == "dose_adjusts") {

            $updateDose = $drug[$columnName];
            $updateStmt = "";

            $status = $status && updateDoseAdjustments($updateDose, $name, $dbhandle, $user);

        }

        if (isset($drug[$columnName]) && $columnName=="precautions") {

            $updatePre = $drug[$columnName];
            $updateStmt = "";

            $status = $status && updateToTable($updatePre, $expectedColumnPre, $name, $dbhandle, $user, $columnName);
                        
        }

        if (isset($drug[$columnName]) && $columnName=="onc_uses") {

            $updateOncs = $drug[$columnName];
            $updateStmt = "";
                        
            $status = $status && updateToTable($updateOncs, $expectedColumnOncs, $name, $dbhandle, $user, $columnName);
        }
    }

    if($new_name != null) {
                 
    }
    
    // For transactions
    if ($status) {
        $stmt = $dbhandle->prepare("COMMIT");
        $stmt->execute();
    } else {
        $stmt = $dbhandle->prepare("ROLLBACK");
        $stmt->execute();
    }

    return $status;
}

// Function to update only the drugs table 
function updateToDrugs($updateStmt, $updateVal, $dbhandle) {
    $qDrugs = $dbhandle->prepare($updateStmt);
    $status = $qDrugs->execute($updateVal);

    return($status);
}

// Function to update compounds table in case a new compound is
// being added via the drug or enzyme interactions

function updateToCompounds($interact, $username, $dbhandle) {
    
    $status = True;
        $compoundInsertQuery = "INSERT INTO compounds (g_name, who_updated) "
                              ."VALUES (?, ?)";
        $findCompounds = "SELECT * FROM compounds WHERE g_name = ?";

        $insertCompQ = $dbhandle->prepare($compoundInsertQuery);
        $findQ = $dbhandle->prepare($findCompounds);
    // If the compound isn't present in the compounds 
                        // table, add it
    $status = $status && $findQ->execute(array($interact["compound"]));
        $compsPresent = $findQ->fetchAll(PDO::FETCH_ASSOC);

        if (empty($compsPresent)) {
                        $status = $status && $insertCompQ->execute(
                        array($interact["compound"], $username));
        }
    return($status);
}

/* Function only made for the interactions page in drug edit for the website
 * Changes either drug_interacts table, drug_cyp_interacts or drug table
 * For the drug table, the properties of the drug in concern can be changed
 * The colums that are changed are qt_prolonging agents and/or other interactions
 * There may be multiple of these values and they are added together in a string
 * and updated to the drug in concern
 */

function updateDrugInteracts($drug, $interactions, $dbhandle, $username) {
        if (empty($interactions)) {
                return(True);
        }
        /* These are the queries that we will use to actually input the
         * interaction data.
         */
        $otherInsQuery = "UPDATE drugs SET qt_prolonging = ?, other_interacts "
                        ."= ?, who_updated = ? WHERE g_name = ?";

        $otherInterQ = $dbhandle->prepare($otherInsQuery);

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
        }

    //if($qtProlonging == "" && $otherInteracts == "") {
    //  return ($status);
    //}
        
        $qtProlonging = ($qtProlonging == "") ? "" : substr($qtProlonging, 0, -1);
        $otherInteracts = ($otherInteracts == "") ? "" : substr($otherInteracts, 0, -1);

        $status = $status && $otherInterQ->execute(array($qtProlonging,
                $otherInteracts, $username, $drug));

        return($status);

}

/* Function to update only drug_interacts or drug_cyp_interacts
 * Action could be either added, deleted or edited depending
 * on status key of the dictionary in $updateTablesHash
 * Edited works as first deleting and then adding. Rows are
 * never actually edited
 */

function updateToInterTables($updateTablesHash, $expectedColumnsInter, $expectedColumnsEnz, $name, $dbhandle, $user) {

    $updateStmt = "";
    $updateTables = $updateTablesHash["values"];
    $updateTablesOpt = $updateTablesHash["options"];
    $status = TRUE;
    $count = count($updateTables);
    $parentCol = "";
    $expectedColumns = null;

    $status = $status && updateDrugInteracts($name, $updateTables, $dbhandle, $user);

    for($i=0; $i < $count; $i++) {

        $table = $updateTables[$i];
        $options = $updateTablesOpt[$i];

    // either deletion or edits. For edits, check if primary keys are present                           
        if ($options["status"] == "deleted" || $options["status"] == "edited") {
            if ($options["status"] == "edited") {
                if (!checkIfInputValid($table, "drug_cyp_interacts", $dbhandle) && 
                    !checkIfInputValid($table, "drug_interacts", $dbhandle)) {
                        continue;
                }
            }   
            if (isset($table["enzyme_effect_type"])) {
                $updateStmt = "DELETE FROM drug_cyp_interacts "
                              ."WHERE drug = ? AND " 
                              .$expectedColumnsEnz[1]." = ? AND "
                              .$expectedColumnsEnz[2]." = ? AND "
                              .$expectedColumnsEnz[3]." = ?";
                $qTableDel = $dbhandle->prepare($updateStmt);
                $status = $status && $qTableDel->execute(array($name, $options["pkey"],                                 
                                                               $options["pkey2"], $options["pkey3"]));
            } else if (isset($table['interaction']) && $table["interaction"] != "Other Interactions" &&
                     isset($table['compound']) && $table["compound"] != "QT-prolonging agents") {

                $updateStmt = "DELETE FROM drug_interacts "
                              ."WHERE drug = ? AND "
                              .$expectedColumnsInter[1]." = ? AND "
                              .$expectedColumnsInter[2]." = ?";
                $qTableDel = $dbhandle->prepare($updateStmt);
                $status = $status && $qTableDel->execute(array($name, $options["pkey"], 
                                                         $options["pkey2"]));

                error_log($updateStmt);
                error_log($name." ".$options['pkey']." ".$options['pkey2']);
            }
        }

        if ($options["status"] == "added" || $options["status"] == "edited") {
            if (isset($table["enzyme_effect_type"])) {
                $parentCol = "drug_cyp_interacts";
                $expectedColumns = $expectedColumnsEnz;
            } else {
                $status = $status && updateToCompounds($table, $user, $dbhandle);
                $parentCol = "drug_interacts";
                $expectedColumns = $expectedColumnsInter;
            }

            if (!checkIfInputValid($table, $parentCol, $dbhandle)) {
                continue;
            }
            
            $insertVal = array();
            $insertStmt = "INSERT INTO ".$parentCol." (";
            // Loop through columns and prepare query statement and values
            foreach ($expectedColumns as $column) {
                if (isset($table[$column])) {
                    $insertStmt = $insertStmt.$column.", ";
                    array_push($insertVal,$table[$column]);
                }
            }
            $insertStmt = $insertStmt."who_updated) VALUES (";
            array_push($insertVal,$user);
            $total = count($insertVal);
            for($j=0; $j<$total-1; $j++) {
                $insertStmt = $insertStmt."?, ";
            }
            $insertStmt = $insertStmt."?)";
            $qTableIns = $dbhandle->prepare($insertStmt);
            $status = $status && $qTableIns->execute($insertVal);

        }                             
    }

    return($status);
}

/* Function that only handles drug table and its columns
 * drug is only edited and its columns are also edited
 */
function getStmtForDrugs($updateDrug, $expectedDrugsColumn, $name, $dbhandle) {

        $updateStmt = "UPDATE drugs SET ";
        $updateVal = array();
        $new_name = "";

        foreach($expectedDrugsColumn as $columnDrugs) {
            if(isset($updateDrug[$columnDrugs])) {
                // Could contain multiple strings so concatenate everything
                if($columnDrugs == "contraindications" || 
                            $columnDrugs == "monitoring") {
                    $updateStmt = $updateStmt.$columnDrugs." = ?, ";
                    $contraind = "";
                    
                    for($i=0, $count = count($updateDrug[$columnDrugs]);$i<$count;$i++) { 
                        if($i < $count - 1) {
                            $contraind = $contraind.$updateDrug[$columnDrugs][$i]."|";
                        }
                        else {
                            $contraind = $contraind.$updateDrug[$columnDrugs][$i];
                        }
                    }
                    array_push($updateVal, $contraind);
                }
                
                else {
                    if($columnDrugs == "g_name") {
                        $new_name = $updateDrug[$columnDrugs];
                    }
                    $updateStmt = $updateStmt.$columnDrugs." = ?, ";
                    array_push($updateVal, $updateDrug[$columnDrugs]);
                }
            
            }
        }
    // new name important if drug name is changed, the new name should be used to delete
        // rows in other tables
        return(array($new_name, $updateStmt, $updateVal));
}

/* Function that handles most of the other tables' changes. 
 * added, deleted or edited based on status of a particular row
 * dynamic query generation and value generation based on table
 * and columns
 */

function updateToTable($updateTablesHash, $expectedColumns, $name, $dbhandle, $user, $parentCol) {

        $updateStmt = "";
        $updateTables = $updateTablesHash["values"];
        $updateTablesOpt = $updateTablesHash["options"];
        $status = TRUE;
        $count = count($updateTables);
        for($i=0; $i < $count; $i++) {

            $table = $updateTables[$i];
            $options = $updateTablesOpt[$i];
            // Check for validity if edited and then delete and then add
            if($options["status"] == "deleted" || $options["status"] == "edited") {
                if($options["status"] == "edited") {
                    if(!checkIfInputValid($table, $parentCol, $dbhandle)) {
                                            continue;
                                    }
                }
                
                $updateStmt = "DELETE FROM ".$parentCol
                                ." WHERE drug = ? AND "
                                .$expectedColumns[1]." = ?";
                            
                $qTableDel = $dbhandle->prepare($updateStmt);
                        $status = $status && $qTableDel->execute(array($name, $options["pkey"]));           
            }

            if($options["status"] == "added" || $options["status"] == "edited") {
                
                if(!checkIfInputValid($table, $parentCol, $dbhandle)) {
                    continue;
                }
                if(isset($table["chart"]) && !empty($table["chart"])) {
                    $table["chart"] = saveDoseAdjustFile($table, $name);
                }
                else if (!isset($table["approved"]) && $parentCol == "onc_uses") {
                                $table["approved"] = False;
                        }
                else if (!isset($table["severe"]) && $parentCol == "side_effects") {
                                        $table["severe"] = False;
                                }

                $insertVal = array();
                $insertStmt = "INSERT INTO ".$parentCol." (";
                foreach($expectedColumns as $column) {
                            if(isset($table[$column])) {
                        $insertStmt = $insertStmt.$column.", ";
                                    array_push($insertVal,$table[$column]);
                    }
                }
                $insertStmt = $insertStmt."who_updated) VALUES (";
                array_push($insertVal,$user);
                $total = count($insertVal);
                for($j=0; $j<$total-1; $j++) {
                    $insertStmt = $insertStmt."?, ";
                }
                $insertStmt = $insertStmt."?)";
                $qTableIns = $dbhandle->prepare($insertStmt);
                $status = $status && $qTableIns->execute($insertVal);

            }
            else {

            /*  if(isset($table["chart"]) && !empty($table["chart"])) {
                                        $table["chart"] = saveDoseAdjustFile($table, $name);
                                }
                $updateStmt = "UPDATE ".$parentCol." SET ";
                $updateVal = array();
                foreach($expectedColumns as $column) {
                    if(isset($table[$column])) {
                        $updateStmt = $updateStmt.$column." = ?, ";
                        array_push($updateVal, $table[$column]);
                    }
                }
                $updateStmt = $updateStmt."who_updated = ? WHERE drug = ? AND ".$expectedColumns[1]." = ?";
                array_push($updateVal, $user, $name, $options["pkey"]);

                    $qTableUpdate = $dbhandle->prepare($updateStmt);
                    
                    $status = $status && $qTableUpdate->execute($updateVal);

            */}                             
             
        }
        
        return($status);
}

/* Update Dose Adjustments
 *
 * Function Uploads new Dose Adjustments, and Edits Existing ones.
 */
function updateDoseAdjustments($doseAdjustments, $name, $dbhandle, $user) 
{
    $status = TRUE;
    foreach ($doseAdjustments AS $adjustment) {
        if (isset($adjustment['delete']) && $adjustment['delete'] == TRUE) {
            $sql = "DELETE FROM dose_adjusts WHERE problem = ?";
            $updateStmt = $dbhandle->prepare($sql);
            $status = $status && $updateStmt->execute(array($adjustment['orig_name']));
        } else if (isset($adjustment['orig_name']) && !empty($adjustment['orig_name'])) {
            if (isset($adjustment['chart_url']) && !empty($adjustment['chart_url'])) {
                $sql = "UPDATE dose_adjusts SET problem = ?, note = ?, who_updated = ? WHERE drug = ? AND problem = ?";
                $updateStmt = $dbhandle->prepare($sql);
                $status = $status && $updateStmt->execute(array($adjustment['problem'], $adjustment['note'], $user, $name, $adjustment['orig_name']));
            } else {
                $chart_value = "";
                if (isset($adjustment['chart']) && !empty($adjustment['chart'])) {
                    // Upload New Chart
                    $chart = $adjustment["chart"];

                    $type = explode("/", $adjustment["chart_type"]);
                    $type = end($type);

                    if (substr($chart,0,5) != "api/d") {
                        $parts = explode(".", $chart);
                        $chart_value = "api/doseAdjustCharts/".$name."_"
                                  .$adjustment["problem"]."."
                                  .$type;
                        $destination = "/var/www/web/" . $chart_value;
                        move_uploaded_file($chart, $destination);
                        //unlink($adjustment['orig_chart']); // Delete Original Chart (stop name collisions)
                    }
                }
                $sql = "UPDATE dose_adjusts SET problem = ?, note = ?, chart = ?, who_updated = ? WHERE drug = ? AND problem = ?";
                $updateStmt = $dbhandle->prepare($sql);
                $status = $status && $updateStmt->execute(array($adjustment['problem'], $adjustment['note'], $chart_value, $user, $name, $adjustment['orig_name']));
            }
        } else {
            if (isset($adjustment['chart']) && !empty($adjustment['chart'])) {
                $chart = $adjustment["chart"];

                $type = explode("/", $adjustment["chart_type"]);
                $type = end($type);

                if (substr($chart,0,5) != "api/d") {
                    $parts = explode(".", $chart);
                    $chart_value = "api/doseAdjustCharts/".$name."_"
                              .$adjustment["problem"]."."
                              .$type;
                    $destination = "/var/www/web/" . $chart_value;
                    move_uploaded_file($chart, $destination);
                }
                $sql = "INSERT INTO dose_adjusts (drug, problem, note, chart, who_updated) VALUES (?,?,?,?,?)";
                $updateStmt = $dbhandle->prepare($sql);
                $status = $status && $updateStmt->execute(array($name, $adjustment['problem'], $adjustment['note'], $chart_value, $user));
            } else {
                $sql = "INSERT INTO dose_adjusts (drug, problem, note, chart, who_updated) VALUES (?,?,?,?,?)";
                $updateStmt = $dbhandle->prepare($sql);
                $status = $status && $updateStmt->execute(array($name, $adjustment['problem'], $adjustment['note'], "", $user));
            }
        }
    }
    
    return $status;
}

/* Function to save doseAdjust Image file into the server based on drug name. These files
 * are never removed but their links in the databases are.
 */

function saveDoseAdjustFile($adjustment, $drug) {
    
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

          return $adjustment["chart"];
}

/* Function to check for validity of changes/input.
 * checks whether primary keys for every table are in the input
 * for additions and deletions to take place. Also checks
 * if a row already exist in db before adding it
 */

function checkIfInputValid($tableValues, $tableName, $dbhandle) {

    if($tableName == "dose_adjusts") {
        if(!isset($tableValues["drug"]) || empty($tableValues["drug"])) {
            return false;
        }
        
        if(!isset($tableValues["problem"]) || empty($tableValues["problem"])) {
                        return false;
                }
        $checkStmt = "SELECT * FROM ".$tableName." WHERE drug = ? AND problem = ?";
        $qCheck = $dbhandle->prepare($checkStmt);
        $qCheck->execute(array($tableValues["drug"], $tableValues["problem"]));
    }

    else if($tableName == "drug_cyp_interacts") {
                if(!isset($tableValues["drug"]) || empty($tableValues["drug"])) {
                        return false;
                }
        if(!isset($tableValues["enzyme"]) || empty($tableValues["enzyme"])) {
                        return false;
                }
        if(!isset($tableValues["drug_effect_type"]) || empty($tableValues["drug_effect_type"])) {
                        return false;
                }
        if(!isset($tableValues["enzyme_effect_type"]) || empty($tableValues["enzyme_effect_type"])) {
            return false;
        }

        $checkStmt = "SELECT * FROM ".$tableName." WHERE drug = ? AND enzyme = ? AND drug_effect_type = ?";
                $qCheck = $dbhandle->prepare($checkStmt);
                $qCheck->execute(array($tableValues["drug"], $tableValues["compound"]), $tableValues["drug_effect_type"]);
                $results = $qCheck->fetchAll(PDO::FETCH_ASSOC);
                if(empty($results)) {
                        return true;
                }
        }

     else if($tableName == "drug_interacts") {
                if(!isset($tableValues["drug"]) || empty($tableValues["drug"])) {
                        return false;
                }
                if(!isset($tableValues["compound"]) || empty($tableValues["compound"])) {
                        return false;
                }
                if(!isset($tableValues["interaction"]) || empty($tableValues["interaction"])) {
                        return false;
                }

        $checkStmt = "SELECT * FROM ".$tableName." WHERE drug = ? AND compound = ? AND interaction = ?";
                $qCheck = $dbhandle->prepare($checkStmt);
                $qCheck->execute(array($tableValues["drug"], $tableValues["compound"], $tableValues["interaction"]));
                $results = $qCheck->fetchAll(PDO::FETCH_ASSOC);
                if(empty($results)) {
                        return true;
                }
        }

    
    else if($tableName == "enzyme_interacts") {
                if(!isset($tableValues["enzyme"]) || empty($tableValues["enzyme"])) {
                        return false;
                }
                if(!isset($tableValues["compound"]) || empty($tableValues["compound"])) {
                        return false;
                }
        if(!isset($tableValues["interaction"]) || empty($tableValues["interaction"])) {
                        return false;
                }

                $checkStmt = "SELECT * FROM ".$tableName." WHERE enzyme = ? AND compound = ? " 
                            ."AND interaction = ?";
                $qCheck = $dbhandle->prepare($checkStmt);
                $qCheck->execute(array($tableValues["enzyme"], $tableValues["compound"], 
                            $tableValues["interaction"]));
                $results = $qCheck->fetchAll(PDO::FETCH_ASSOC);
                if(empty($results)) {
                        return true;
                }
        }
        if($tableName == "onc_uses") {
                if(!isset($tableValues["drug"]) || empty($tableValues["drug"])) {
                        return false;
                }
                if(!isset($tableValues["cancer_type"]) || empty($tableValues["cancer_type"])) {
                        return false;
                }

                $checkStmt = "SELECT * FROM ".$tableName." WHERE drug = ? AND cancer_type = ?";
                $qCheck = $dbhandle->prepare($checkStmt);
                $qCheck->execute(array($tableValues["drug"], $tableValues["cancer_type"]));
                $results = $qCheck->fetchAll(PDO::FETCH_ASSOC);
                if(empty($results)) {
                        return true;
                }
        }
        if($tableName == "precautions" || $tableName == "side_effects") {
                if(!isset($tableValues["drug"]) || empty($tableValues["drug"])) {
                        return false;
                }
                if(!isset($tableValues["name"]) || empty($tableValues["name"])) {
                        return false;
                }

                $checkStmt = "SELECT * FROM ".$tableName." WHERE drug = ? AND name = ?";
                $qCheck = $dbhandle->prepare($checkStmt);
                $qCheck->execute(array($tableValues["drug"], $tableValues["name"]));
                $results = $qCheck->fetchAll(PDO::FETCH_ASSOC);
                if(empty($results)) {
                        return true;
                }
        }

    if($tableName == "compounds" || $tableName == "drugs") {
        if(!isset($tableValues["g_name"]) || empty($tableValues["g_name"])) {
                        return false;
                }

        $checkStmt = "SELECT * FROM ".$tableName." WHERE g_name = ?";
                $qCheck = $dbhandle->prepare($checkStmt);
                $qCheck->execute(array($tableValues["g_name"]));
            $results = $qCheck->fetchAll(PDO::FETCH_ASSOC);
                if(empty($results)) {
                        return true;
                }
    }
    
    
        if($tableName == "cyp_enzymes") {
                if(!isset($tableValues["name"]) || empty($tableValues["name"])) {
                        return false;
                }
        
        $checkStmt = "SELECT * FROM ".$tableName." WHERE name = ?";
                $qCheck = $dbhandle->prepare($checkStmt);
                $qCheck->execute(array($tableValues["name"]));        
                $results = $qCheck->fetchAll(PDO::FETCH_ASSOC);
                if(empty($results)) {
                        return true;
                }
        }
}
?>
