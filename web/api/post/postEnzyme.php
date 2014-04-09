<?php
 /* Post script to make changes in the enzyme table and also the name
 * in the drug interaction table. This file gets update statements
 * and old names and new names to be updated
 */
function echoPostEnzyme($enzyme) {
        $result = updateEnzyme($enzyme, "Web API");

        echo(json_encode($result));
}

/* This function is the main function to call when updating an enzyme
 * It takes a enzyme array with update information, the user who
 * is updating the drug, the old name of the enzyme if name is updated,
 * and the dbhandle used to execute sql queries into the database

 * enzyme array => (""name" => "test1"),
                  "inhibitors" => array("values" => array(array("drug" => "test1", ...),array(...)),
                                         ("options" => array(array("status => "added", "compound" => "test1"), 
                                                                array(....)))
                                   ),
                 "inducers" => array("values" => array(array("drug" => "test1", ...),array(...)),
                                       ("options" => array(array("status => "added", "compound" => "test1"), 
                                                                array(....)))
                                   ),
                  "..." => ... )
 
 * The above enzyme array is a combination of dictionaries and arrays that can be 
 * used to add, update or delete information from different tables. The first
 * dictionary contains keys as enzyme name which is changed in cyp_enzyme table 
 * Multiple rows from the tables enzyme_interaction can be changed and 
 * hereby array("values") is an array of dictionaries of rows that are changed
 * ie added deleted or edited. The array("options") is again an array of 
 * dictionaries mapping to the rows that are being changed with status
 * of added deleted or edited. Note the primary key has name of "compound"
 * and there is also "interaction" since only one table is being changed here
 * enzyme_interacts
 */
function updateEnzyme($enzyme, $user = "unknown", $name="", $dbhandle=null) {
	require_once __DIR__."/../dbConnect/connectStart.php";
	require_once __DIR__."/../post/postDrug.php";

	$dbhandle->beginTransaction();

	$status = True;
	//If requested via WEB REST, the old_name should be part of dictionary
	if($name == "") {
		$name = $enzyme["old_name"];
	}
	$columns = array("name", "substrate", "inducer", "inhibitor");
	//$categories = array("substrate", "inducer", "inhibitor");
	$expectedColumns = array("enzyme", "compound", "interaction", "severity");
	$enzymeName = null;

	foreach($columns as $column) {
		//either enzyme name, or inhibitor inducer or substrate
		if(isset($enzyme[$column]) && $column=="name") {
			//change the old name to new name of enzyme, so that updates can be
			// made on other tables, values already cascaded
			$enzymeName = $enzyme["name"];
			$name = $enzymeName;
			$status = $status && updateToEnzyme($name, $dbhandle, $enzyme["name"], $user); 
		}

		else if(isset($enzyme[$column]) && $column=="substrate") {
			//$updateStmt = "";
			//$enzymeInteracts = $enzyme["enzymeInteracts"];
			
			
			$status = $status && updateToEnzymeTable($enzyme[$column], $expectedColumns, $name,
                                	                                $dbhandle, $user, "enzyme_interacts");

				
		}
		
		else if(isset($enzyme[$column]) && $column=="inhibitor") {
		
			$status = $status && updateToEnzymeTable($enzyme[$column], $expectedColumns, $name,
                                 	                          $dbhandle, $user, "enzyme_interacts");
	
			
		}
		else if(isset($enzyme[$column]) && $column=="inducer") {
		
			$status = $status && updateToEnzymeTable($enzyme[$column], $expectedColumns, $name, 
								$dbhandle, $user, "enzyme_interacts"); 
		}
		
				
	}
	if($enzymeName != null) {
		/*$updateStmt = $updateStmt."enzyme = ".$enzymeName;
		$status = $status && updateToEnzymeInter($name, $dbhandle, $updateStmt);
		$status = $status && updateToDrugCyp($name, $updateStmt, $dbhandle);*/
	}
	// Call a function for each table and updates status to false if the
	// status fails
	if ($status) {
		$stmt = $dbhandle->prepare("COMMIT");
		$stmt->execute();
	} else {
		$stmt = $dbhandle->prepare("ROLLBACK");
		$stmt->execute();
	}	

	return ($status);

}

function updateToEnzymeInter($old_name, $dbhandle, $updateStmt) {
	$qEnzInter = $dbhandle->prepare("update enzyme_interacts "
				       ."set ?"
				       ."where enzyme = ?");
	$status = $qEnzInter->execute(array($updateStmt, $old_name));

	return($status);
}

// can only update the name of the old cyp_enzyme
function updateToEnzyme($old_name, $dbhandle, $new_name, $user) {
	$qEnz = $dbhandle->prepare("UPDATE cyp_enzymes "
				  ."SET name = ?, who_updated = ?"
				  ." WHERE name = ?");
	$status = $qEnz->execute(array($new_name, $user, $old_name));

	return($status);
}

/* Function to update enzyme_interacts table.
 * takes changed rows that is either deleted, added or edited
 * editing is actually deleting the old row and adding the entire new row
 * "status" gives value of either deleted added or edited
 */

function updateToEnzymeTable($updateTablesHash, $expectedColumns, $name, $dbhandle, $user, $parentCol) {

		$updateStmt = "";
		$updateTables = $updateTablesHash["values"];
		$updateTablesOpt = $updateTablesHash["options"];
		$status = TRUE;
		$count = count($updateTables);
		for($i=0; $i < $count; $i++) {

			$table = $updateTables[$i];
			$options = $updateTablesOpt[$i];
			//to edit , check for valid input then delete and then add
			if($options["status"] == "deleted" || $options["status"] == "edited") {
				if($options["status"] == "edited") {
					if(!checkIfInputValid($table, $parentCol, $dbhandle)) {
                                        	continue;
                                }
			}
				//$options["compound"] and $options["interaction"] as primary keys for the table
				$qTableDel = $dbhandle->prepare("DELETE FROM ".$parentCol
								." WHERE enzyme = ? AND "
								.$expectedColumns[1]." = ? AND "
								.$expectedColumns[2]." = ?");
                		$status = $status && $qTableDel->execute(array($name, $options["compound"], $options["interaction"]));

			}

			if($options["status"] == "added" || $options["status"] == "edited") {

				if(!checkIfInputValid($table, $parentCol, $dbhandle)) {
					continue;
				}
				
				$status = $status && updateToCompounds($table, $user, $dbhandle);
				echo $table["compound"];
				$insertVal = array();
				$insertStmt = "INSERT INTO ".$parentCol." (";
				//dynamic adding of statement and values although not necessary
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
				
				$status = $status && updateToCompounds($table, $user, $dbhandle);
				$updateStmt = "UPDATE ".$parentCol." SET ";
				$updateVal = array();
				foreach($expectedColumns as $column) {
                    if(isset($table[$column])) {
                        $updateStmt = $updateStmt.$column." = ?, ";
						array_push($updateVal, $table[$column]);
                    }
                }
				$updateStmt = $updateStmt."who_updated = ? WHERE enzyme = ? AND "
							.$expectedColumns[1]." = ? AND ".$expectedColumns[2]." = ?";
					$qTableUpdate = $dbhandle->prepare($updateStmt);
					
					array_push($updateVal, $user, $name, $options["compound"], $options["interaction"]);
					$status = $status && $qTableUpdate->execute($updateVal);
					
					//echo "\n";
					//print_r($updateVal);

			}                             
             
        }
		
		return($status);
}

?>

