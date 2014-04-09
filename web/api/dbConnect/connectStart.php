<?php

/**
 * Sets up a database connection in the variable dbhandle. Connection is made 
 * with a user that has full priviledges on the database
 */

$dsn = 'mysql:dbname=anticDB;host=localhost';
$user = 'root';
$password = 'root';

try {
	$dbhandle = new PDO($dsn, $user, $password);

	/* Using the prepare statements when making queries goes a long way to
	 * protecting against SQL injection attacks, however, PHP does not do
	 * "real" statement preperation by default. The first statement below
	 * tells PHP to do real statement preparation and the second tells PHP
	 * to return actual errors.
	 */
	$dbhandle->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	$dbhandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
	$error = array("error" => "Connection to database failed: "
			. $e->getMessage());
	echo(json_encode($error));
}
?>
