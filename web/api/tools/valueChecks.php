<?php
/* This file will eventually include value checks for most of the 
 * non-standard values required by the api. The following are ones
 * that hopefully will be included:
 *
 * Dates (such that they can be inserted into mysql)
 * Strings (should not include some values, such as html tags)
 * Pregnancy Strings (should include Category: _)
 *
 * Each function returns an array indicating any errors found. The array will
 * contain 2 keys: "error_code" and "error_message".
 *
 * error_code is either 0 for no error, 1 for warning (meaning the execution
 *   will continue), or 2 for error (meaning execution has stopped).
 * error_message is an array of strings, each containing a message indicating
 *   the cause of the warning or error. There may be any number of warning 
 *   messages, which will all start with "Warning: "; but there should only be
 *   one error message, marked with "Error: " and this should always be the 
 *   last message.
 */

/* checkIsDate checks $possibleDate to be sure it is a string in the format
 * "YYYY-MM-DD hh:mm:ss". Any part of this format may be omited except the 
 * year, but if a part is omited, no parts to the right of it may be included.
 * So one cannot include hh without at least YYYY-MM-DD as well.
 */
function checkIsDate($possibleDate, $errors) {
	$a = 1;
}
