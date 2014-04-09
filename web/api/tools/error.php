<?php

/**
 * A class for maintaining information about the warnings and errors that
 * occur while running a script. Currently not used in any scripts
 */
class error {
	// Static variables for specific error messages
	public static $REQUEST_KEY_ERROR = 
		"Error: No valid request key given"l

	// The array of warnings that have occured
	private $error_array = array();

	// Two arrays so we can distinguish error messages from warnings
	private $error_messages = array(
		self::$REQUEST_KEY_ERROR
	);
	private $warning_messages = array();

	// Initializes to an empty error array
	public function error() {
		self::$error_array["error_code"] = 0;
		self::$error_array["error_message"] = array();
	}

	/**
	 * Adds a given warning (which nust be from warning_messages) to the
	 * error_array. Won't add a warning after an error as errors should 
	 * cause immediate ending of a script.
	 *
	 * @param warning The warning to be added
	 */
	public function addWarning($warning) {
		if (!(array_search($warning, $warning_messages) === False)) {
			if (self::$error_array["error_code"] < 2) {
				self::$error_array["error_code"] = 1;
				self::$error_array["error_message"] = $warning; 
			} else {
			 	trigger_error("Trying to add warning after"
					." error. Errors should end api "
					."execution.\nError was: "
					. end(self::$error_array["error_message"]);
			}
		} else {
			trigger_error("Not a valid warning message");
		}
	}

	/**
	 * Adds an error to error_array, which must be from errors_array. Won't
	 * add a second error to the error_array because errors should cause
	 * immediate ending of a script.
	 *
	 * @param error The error message to add
	 */
	public function addError($error) {
		if (!(array_search($error, $error_messages) === False)) {
			if (self::$error_array["error_code"] == 2) {
				trigger_error("Trying to add a second error. "
					."Errors should end api "
					."execution.\nError was: "
					. end(self::$error_array["error_message"]);
			} else {
				self::$error_array["error_code"] = 2;
				self::$error_array["error_message"] = $error;
			}
		} else {
			trigger_error("Not a valid error message");
		}
	}

	/**
	 * Returns the error array
	 *
	 * @return error_array
	 */
	public function getErrorArray() {
		return (self::$error_array);
	}

}
