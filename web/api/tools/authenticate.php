<?php

/**
 * The authenticate function authenticates a user with a given username against
 * the users table in the database, and returns true if the authentication 
 * worked and false otherwise.
 */
function authenticate($email, $pass) {
	require_once __DIR__."/../dbConnect/password_user.php";

	$query = "select password from users where email = ?";

	$passQ = $dbhandle->prepare($query);
	$passQ->execute(array($email));

	$rPass = $passQ->fetch(PDO::FETCH_ASSOC);

	if(!empty($rPass)) {
		$password_hash = $rPass["password"];
		$result = password_verify($pass, $password_hash);

		//Password was different
		if (!$result) {
			echo(json_encode(["error" => "Password incorrect"]));
		}
	} else {
		//Couldn't find email in database
		echo(json_encode(["error" => "Not a valid email"]));
		$result = False;
	}
	return $result;
}
