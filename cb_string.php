<?php

/**
 * Peimic's centralization of common string functions
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package cb_cogs
 * @version $Id: cb_string.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */

// Needed library files
require_once( dirname(__FILE__) . '/cb_array.php');
require_once( dirname(__FILE__) . '/cb_validation.php');

/**
 * Trim, stripslashes, and strip tags or encode entities of a string.
 *
 * @param mixed content
 * @param boolean strip tags or encode entities
 * @param mixed
 */
function cbCleanStr($item = null, $strip = true)
{
	if ( !is_null($item) && is_string($item) )
	{
		$item					= stripslashes( trim( $item ) );

		$item					= ( $strip )
			? strip_tags( $item ) 
			: htmlentities( $item );
	}

	return $item;
}


/**
 * Displayed print_r or var_dump'd contents of $msg.
 *
 * @param string $msg to be outputted, ex: 'Now is the time of'
 * @param boolean $var_dump use PHP's var_dump() instead of print_r()
 * @param boolean return type: void, false or string, true
 * @return mixed
 */
function cbPrint($msg = '', $var_dump = false, $return_as_string = false)
{
	// using web server or on command line?
	$add_html					= ( isset($_SERVER['HTTP_HOST']) || 
									isset($HTTP_SERVER_VARS['HTTP_HOST']) )
		? true
		: false;

	// get the variables using PHP's output buffering and own human readable
	// print functions
	ob_start();

	( !$var_dump ) 
		? print_r($msg) 
		: var_dump($msg);

	$msg						= ob_get_contents();
	
	ob_end_clean();

	// turn obnoxious 8 space tabs into 4 spaces
	$msg						= preg_replace("/\t/", '    ', $msg);

	$msg						= ( $add_html )
		? "\n<pre>$msg</pre>\n"
		: "\n$msg\n";

	if ( !$return_as_string )
	{
		echo $msg;
	}

	else
	{
		return $msg;
	}
}

/**
 * Displays cbPrint with a heading
 *
 * @param string heading for msg
 * @param string $msg to be outputted, ex: 'Now is the time of'
 * @param boolean $var_dump use PHP's var_dump() instead of print_r()
 * @return void
 */
function cbPrint2($heading = '', $msg = '', $var_dump = false)
{
	$string						= '';
	$string						.= '<hr />';

	if ( ! cbIsBlank( $heading ) )
	{
		$string					.= $heading . '<br />';
	}

	$string						.= cbPrint($msg, $var_dump, true);
	$string						.= '<hr />';

	echo $string;
}


/**
 * Helper alias for cbPrint to return it as string
 *
 * @param mixed contents to be printed
 * @return string
 */
function cbPrintString($contents)
{
	return cbPrint($contents, false, true);
}


/**
 * Returns mixed item depending upon input and null default
 *
 * @param mixed non-null value
 * @param mixed null value default
 * @return mixed
 */
function & cbNullDefault($input, $default = false)
{
	return ( isset($input) && !is_null($input) )
		? $input
		: $default;
}

/**
 * Alias for cbNullDefault($input, $default = false)
 *
 * @see cbNullDefault
 * @param mixed non-null value
 * @param mixed null value default
 * @return mixed
 */
function & cbCoalesce($input, $default = false)
{
	return cbNullDefault($input, $default);
}


/**
 * Returns string of a pseduo-randomized md5 hash for given string.
 *
 * @param string input to be encoded
 * @return mixed string, boolean failure
 */
function cbMd5($input)
{
	if ( is_string($input) )
	{
		$server					= cbServer('REMOTE_ADDR', rand(1, 1972) );
		$input					.= microtime() . $server;

		// remove spaces
		$input = preg_replace('/\s+/', '', $input);

		return md5($input);
	}

	return false;
}


/**
 * Swap values of passed in parameters.
 *
 * @param mixed item one
 * @param mixed item two
 * @return void
 */
function cbSwap(&$item1, &$item2)
{
	$temp = $item2;
	$item2 = $item1;
	$item1 = $temp;

	unset($temp);
}


/**
 * Converts $string into an array based upon $separator.
 *
 * @param string $string text to be broken into array
 * @param string/array $separator string to denote $string break points, ex:
 * 	array( ',', '|' ), ':'
 * @return mixed array/boolean false if failure, $string not string or numeric
 */
function cbStr2Arr($string, $separator = ',')
{
	// return now in case of bad input
	if ( is_null($string) || !is_string($string) && !is_numeric($string) )
	{
		return false;
	}

	// convert $separator to array for foreach
	$separator					= ( !is_array($separator) )
		? array($separator)
		: $separator;

	// cycle through $separator looking in $sting
	foreach ( $separator AS $index => $type )
	{
		// once type is found
		if ( !cbIsFalse(strpos($string, $type) ) )
		{ 
			// break $string into components 
			$new_array			= explode($type, $string); 

			// clean up results
			foreach ( $new_array AS $key => $value )
			{
				$new_array[$key]	= cbTrimWhitespace($value);

				// remove empty indices
				if ( cbIsBlank($new_array[$key]) )
				{
					unset($new_array[$key]);
				}
			}

			// return results
			return $new_array;
		}
	}

	return array($string);
}


/**
 * Trim and truncate whitespace in a string.
 *
 * @param string text content
 * @return string
 */
function cbTrimWhitespace($string)
{
	if ( !is_null($string) && is_string($string) )
	{
		return trim( preg_replace('/\s\+/', ' ', $string) );
	}

	return $string;
}


/**
 * Ensure numbers between 1 and 9 are prepended with 0.
 *
 * @param mixed $number numeric or array of numbers to check and prepend if
 * 	needed.
 * @return mixed
 */
function cbZeroPrepend($number)
{
	// is number numeric, less than 10, greater than -1
	// does number have leading zero
	// $number is 0 to 9
	if ( !is_null($number) && is_numeric($number) 
		&& cbIsBetween($number, 0, 9) && 1 == strlen($number) )
	{
		$number					= '0' . $number;
	}

	return $number;
}


/**
 * Returns similar to substr, but with $string processed by strval().
 *
 * @param string $string alphanumeric to be processed
 * @param integer $start zero-based starting position
 * @param integer $stop zero-based stoping position
entry	 * @return string
 */
function cbSubStr($string, $start, $stop = 0)
{
	if ( !is_numeric($start) )
	{
		return $string;
	}

	// if no default stopping point, stop at end of string
	if ( 0 == $stop )
	{
		$stop = strlen($string);
	}

	// substr -- Return part of a string
	// string substr ( string string, int start [', int length'])
	
	// strval -- Get string value of a variable 
	// string strval ( mixed var)
	return substr( strval($string), $start, $stop);
}

/**
 * Return string from curled URL
 *
 * @param string URL
 * @return string
 */
function cbCurlUrl($url)
{
	// create a new curl resource
	$ch							= curl_init();

	// set URL and other appropriate options
	curl_setopt($ch, CURLOPT_URL, $url);

	// don't send header
	curl_setopt($ch, CURLOPT_HEADER, 0);

	// return results
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// follow redirects
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

	// enable cookies
	curl_setopt( $ch, CURLOPT_COOKIE, 1 );

	// grab URL and pass it to the browser
	// store respone
	$response					= curl_exec($ch);

	// close curl resource, and free up system resources
	curl_close($ch);

	return $response;
}

/**
 * Returns string of a filename or string converted to a spaced extension
 * less header type string.
 *
 * @author Michael Cannon <michael@peimic.com>
 * @param string filename or arbitrary text
 * @return mixed string/boolean
 */
function cbMkReadableStr($str)
{
	if ( is_string($str) )
	{
		$clean_str = htmlspecialchars($str);

		// remove file extension
		$clean_str = preg_replace('/\.[[:alnum:]]+$/i', '', $clean_str);

		// remove funky characters
		$clean_str = preg_replace('/[^[:print:]]/', '_', $clean_str);

		// Convert camelcase to underscore
		$clean_str = preg_replace('/([[:alpha:]][a-z]+)/', "$1_", $clean_str);

		// try to cactch N.N or the like
		$clean_str = preg_replace('/([[:digit:]\.\-]+)/', "$1_", $clean_str);

		// change underscore or underscore-hyphen to become space
		$clean_str = preg_replace('/(_-|_)/', ' ', $clean_str);

		// remove extra spaces
		$clean_str = preg_replace('/ +/', ' ', $clean_str);

		// convert stand alone s to 's
		$clean_str = preg_replace('/ s /', "'s ", $clean_str);

		// remove beg/end spaces
		$clean_str = trim($clean_str);

		// capitalize
		$clean_str = ucwords($clean_str);

		// restore previous entities facing &amp; issues
		$clean_str = preg_replace( '/(&amp ;)([a-z0-9]+) ;/i'
			, '&\2;'
			, $clean_str
		);

		return $clean_str;
	}

	return false;
}

/**
* Returns underscored string from camelCaseString like so camel_Case_String.
*
* @author Michael Cannon <michael@peimic.com>
* @param string arbitrary text
* @return mixed string/boolean
*/
function cbMkUnderscoreStr($str)
{
	if ( is_string($str) )
	{
		$str = preg_replace('/([[:alpha:]][a-z]+)/', "$1_", $str);
		$str = preg_replace('/([[:digit:]]+)/', "$1_", $str);

		return $str;
	}

	return false;
}

/**
 * Given begin and end length to keep, X all else. Helpful for sanitizing
 * some string from 1234 to 1XX4.
 *
 * If no first/last passed, will default to X out middle 50% off.
 *
 * @param string text to overwrite
 * @param integer string start to keep
 * @param integer string end to keep
 * @param string replacement character
 * @return string
 */
function cbXstring($string, $first = false, $last = false, $replacement = null)
{
	$stringLen					= strlen($string);

	if ( !$first )
	{
		$first					= floor($stringLen / 4);
	}

	if ( !$last )
	{
		$last					= ceil($stringLen / 4);
	}

	if ( is_null($replacement) || !is_string($replacement) )
	{
		$replacement			= 'X';
	}

	$begin				= substr($string, 0, $first);
	$end				= substr($string, -$last);
	$middleLen			= $stringLen - $first - $last;
	$middle				= substr($string, $first, $middleLen);
	$middle				= preg_replace('/[[:alnum:]]/', $replacement, $middle);

	$string				= $begin . $middle . $end;

	return $string;
}

/**
 * Converts $text string to HTML suitable material for display.
 *
 * @param string $test to be converted to simple HTML display
 * @return string
 */
function cbStr2html( $text )
{
	// trim
	$text						= trim($text);

	// convert to HTML entities
	$text						= htmlentities($text);

	// convert tabs
	$text						= cbTab2space($text);

	// convert space
	$text						= cbSpace2nbsp($text);

	// convert line breaks
	$text						= nl2br($text);

	// return pseduo html
	return $text;
}

/**
 * Convert tabs in $string to number of $spaces.
 *
 * @param string $string text, ex: "Now is the time\t for more\t fun"
 * @param integer $spaces number of spaces per tab, ex: 3, 4, 0 (to remove)
 * @return string
 */
function cbTab2space( $string, $spaces = 3 )
{
	// validate $string and check for tabs
	if ( !preg_match( "/\t/", $string ) )
	{
		return $string;
	}

	// validate $spaces
	$spaces						= ( is_int( $spaces ) && $spaces > -1 )
									? $spaces
									: 3;

	$space						= '';

	// build tab-space replacement
	for ( $i = 0; $i < $spaces; $i++ )
	{
		$space					.= ' ';
	}

	$string						= ereg_replace( "\t", $space, $string );

	return $string;
}

/**
 * Convert spaces in $string to '&nbsp;'.
 *
 * @param string $string text, ex: "Now is the time for more fun"
 * @return string
 */
function cbSpace2nbsp( $string )
{
	// validate $string by checking for space
	if ( !preg_match( "/ /", $string ) )
	{
		return $string;
	}

	$string						= ereg_replace( ' ', '&nbsp;', $string );

	return $string;
}

/**
 * Create $separator typed CSV string from inputted $data.
 *
 * @param array/object $data information to be converted
 * @param string $separator CSV separator type, ex: ',', '|', "\t"
 * @return string
 */
function cbMkCsvString( $data, $separator = ',' )
{
	$csvArray					= array();

	// check data's type
	if ( ! is_object( $data ) && ! is_array( $data ) )
	{
		// convert data to an array
		$data					= array( $data );
	}

	// loop through data
	foreach ( $data as $key => $value )
	{
		// check for numeric types and prepend with ' if necessary
		if ( is_numeric( $value ) )
		{
			$value				= cbExcelPrepend( $value );
		}

		// check for text escape line breaks and strings
		else
		{
			$value				= cbCsvEscapeStrings( $value, $separator );
		}

		$csvArray[]				= $value;
	}

	// complete line
	$csv						= implode( $separator, $csvArray )
									. "\n";

	return $csv;
}

/**
 * Add escape characters to $string for CSV output.
 *
 * Presently checks for and escapes double quotes (") and $separator
 * characters with double quotes.
 *
 * @param string $string text to be checked and escaped if needed
 * @param string $separator CSV separator type, ex: ',', '|', "\t"
 * @return string
 */
function cbCsvEscapeStrings( $string, $separator = ',' )
{
	if ( is_string( $string ) )
	{
		// check for quotes
		$string					= ( strpos( $string, '"' ) !== FALSE ) 
									? str_replace( '"', '""', $string )
									: $string;
								
		// remove Window's line breaks
		$string					= ( strpos( $string, "\r" ) !== FALSE ) 
									? str_replace( "\r", '', $string )
									: $string;
								
		// check for line breaks
		$string					= ( strpos( $string, "\n" ) !== FALSE ) 
									? str_replace( "\n", "\\n", $string )
									: $string;
								
		// check for commas
		$string					= ( strpos( $string, $separator ) !== FALSE ) 
									? '"' . $string . '"'
									: $string;
								
		// check for blanks
		$string					= ( ! $string )
									? '""'
									: $string;
	}

	return $string;
}

/**
 * Prepend big numbers that are to be viewed in MS Excel with an apostrophe.
 *
 * @param mixed $number to be prepended, ex: 373278407401000
 * @return mixed
 */
function cbExcelPrepend( $number )
{
	if ( is_numeric( $number ) && 14 < strlen( $number ) )
	{
		$number					= "'" . $number;
	}

	return $number;
}

/**
 * Returns string containing HTML converted to a plain text string.
 *
 * @param string
 * @return string
 */
function cbHtml2Str ( $string )
{
	// remove CSS first, easier this way
	$string						= preg_replace(
									'#<style[^>]*>.+</style>#si'
									, ''
									, $string
								);
	// convert html entities to real characters
	$string						= html_entity_decode( $string );
	$string						= preg_replace( '/&bull;/', '*', $string );
	// convert quotes
	$string						= preg_replace( '/(&#8220;|&#8221;)/'
										, '"'
										, $string
									);
	// as the email is sent plain text, remove html
	$string						= strip_tags( $string );
	// double space to single
	$string						= preg_replace( '/  /', ' ', $string );
	// remove sentence beginning whitespace
	$string						= preg_replace( '/\s{2,}/'
										, "\n\n"
										, $string
									);
	$string						= trim( $string );

	return $string;
}

/**
 * Debug wrapper for cbPrint2.
 *
 * Define constant CB_DEV_IPS as "192.168.8.23|204.251.8.20" to funciton.
 *
 * @param string heading for msg
 * @param string $msg to be outputted, ex: 'Now is the time of'
 * @param boolean $var_dump use PHP's var_dump() instead of print_r()
 * @return void
 */
function cbDebug($heading = '', $msg = '', $var_dump = false)
{
	if ( cbDoDebug() )
	{
		cbPrint2( $heading, $msg, $var_dump );
	}
}

/**
 * Return boolean if debug is valid.
 *
 * @return boolean, true if debug on
 */
function cbDoDebug()
{
	return ( CB_DEV_FORCE_ON
		|| ( defined( 'CB_DEV_IPS' )
			&& preg_match( '#' . CB_DEV_IPS . '#', $_SERVER[ 'REMOTE_ADDR' ] )
		)
	);
}

/**
 * Remove extraneous stuff from email addresses. Returns an email address 
 * stripped of everything but the address itself.
 *
 * @param string &$email, ex: "first_name last_name <email@example.com>"
 * @return void
 */
function cbCleanEmails(&$email)
{
	// clean out whitespace
	$email = trim($email);
	
	// look for angle braces
	$begin = strrpos($email, "<");
	$end = strrpos($email, ">");

	if ( $begin !== false ) 
	{
		// return whatever is between the angle braces
		$email = substr( $email, ($begin + 1), ($end - $begin - 1) );
	}
}

/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
