<?php

/**
 * Peimic's centralization of common array functions
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package cb_cogs
 * @version $Id: cb_array.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */


// Needed library files
require_once( dirname(__FILE__) . '/cb_string.php');
 

/**
 * Returns mixed type depending upon $key _ENV type.
 *
 * @param string $_ENV key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbEnv($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, '_ENV', $strip);
}


/**
 * Returns mixed type depending upon $key _GET type.
 *
 * @param string _GET key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbGet($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, '_GET', $strip);
}


/**
 * Returns mixed type depending upon $key _POST type.
 *
 * @param string _POST key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbPost($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, '_POST', $strip);
}


/**
 * Returns mixed type depending upon $key _SESSION type.
 *
 * @param string _SESSION key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbSession($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, '_SESSION', $strip);
}


/**
 * Returns mixed type depending upon $key _COOKIE type.
 *
 * @param string _COOKIE key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbCookie($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, '_COOKIE', $strip);
}


/**
 * Returns mixed type depending upon $key _FILES type.
 *
 * @param string _FILES key name
 * @param mixed false return value
 * @return mixed
 */
function cbFiles($key = null, $return = false)
{
	return cbSuperglobals($key, $return, '_FILES');
}


/**
 * Returns mixed type depending upon $key _REQUEST type.
 *
 * @param string _REQUEST key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbRequest($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, '_REQUEST', $strip);
}


/**
 * Returns mixed type depending upon $key SERVER type.
 *
 * @param string SERVER key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbServer($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, '_SERVER', $strip);
}


/**
 * Returns mixed type depending upon $key GLOBALS type.
 *
 * @param string GLOBALS key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbGlobals($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, 'GLOBALS', $strip);
}


/**
 * Returns scalar value depending upon defined $key.
 *
 * @param string 'define' key name
 * @param mixed false return value
 * @param boolean strip or encode
 * @return mixed
 */
function cbDefined($key = null, $return = false, $strip = true)
{
	return cbSuperglobals($key, $return, 'defined', $strip);
}


/**
 * Returns predefined variable mixed value depending if key is given. If
 * no key given, returns boolean of superglobal's exisitance.
 *
 * @param string GLOBALS key name, null for if exists
 * @param mixed false return value
 * @param string PHP array type name
 * @param boolean strip or encode
 * @return mixed
 */
function cbSuperglobals($key = null, $return = false, $type = 'GLOBALS',
	$strip = true)
{
	// okay key, return key value
	if ( !is_null($key) )
	{
		if ( 'GLOBALS' != $type && 'defined' != $type )
		{
			$item = ( isset($GLOBALS[$type][$key]) )
				? $GLOBALS[$type][$key]
				: $return;
		}
		
		elseif ( 'defined' == $type )
		{
			$defined = get_defined_constants();

			$item = ( isset($defined[$key]) )
				? $defined[$key]
				: $return;
		}
		
		else
		{
			$item = ( isset($GLOBALS[$key]) )
				? $GLOBALS[$key]
				: $return;
		}
		
		$item = cbCleanStr($item, $strip);

		return $item;
	}

	// null key, return whether superglobal exists
	else
	{
		if ( 'GLOBALS' != $type && 'defined' != $type )
		{
			return isset($GLOBALS[$type]);
		}

		elseif ( 'defined' == $type )
		{
			return defined($key);
		}
		
		else
		{
			return isset($GLOBALS);
		}
	}
}


/**
 * Applies zero prepend to each $value of $array if needed.
 *
 * @param array/string $array array containing numeric information
 * @return mixed array/boolean false if unsuccesful
 */
function cbZeroPrependArray($array)
{
	// type check
	if ( is_null($array) || !is_array($array) )
	{
		return false;
	}

	// ensure numbers less than ten are prepended with 0
	foreach ( $array AS $key => $value )
	{
		$array[$key] = cbZeroPrepend($value);
	}

	return $array;
}

/**
 * Dispaly $array as HTML table.
 *
 * @param array $array 3D indexed-associatitive array containing data,
 * 	ex:
		[0] => Array
			(
				[number] => 1
				[name] => Bogart
				[time] => 20021115103120
			)

		[1] => Array
			(
				[number] => 2
				[name] => Bogart
				[time] => 20021115103132
			)
 * @param string $title table heading
 * @param boolean skip first result set
 * @param whether or not to display headers within the resultset every few rows
 * @return string
 */
function cbArr2Html ( $array, $title = 'Query Results', $skipFirst = false, $repeatHeaders = true )
{
	$string						= '';
	$string						.= "<h3>$title</h3>";
	$string						.= "<table\n";

	if ( $skipFirst )
	{
		@array_shift( $array );
	}

	if ( $data = array_shift( $array ) )
	{
		$attributes				= ( ! isset( $data[ 0 ] ) )
									? array_keys( $data )
									: array_values( $data );

		// create header row out of attributes
		$header					= '';
		$header					.= "<tr>\n";

		$headerCount			= count( $attributes );

		for ( $i = 0; $i < $headerCount; $i++ ) 
		{
			$value				= cbMkReadableStr( $attributes[ $i ] );

			$header				.= '<td style="text-align: center;
										font-weight: bold;">'
									. $value
									. '</td>'
									. "\n";
		}

		$header					.= "</tr>\n";

		// keep track of displayed rows
		$rowCount				= 0;

		// cycle through arrays
		do
		{
			// every 15 rows, show headers
			if ( true==$repeatHeaders && 0 == ( $rowCount % 15 ) )
			{
				$string			.= $header;
			}

			$row				= '';
			$row				.= "<tr>\n";

			foreach ( $data as $key => $value ) 
			{
				$value			= htmlentities( $value );

				$row			.= '<td>'
									. $value
									. '</td>'
									. "\n";
			}

			$row				.= "</tr>\n";

			$string				.= $row;
			$rowCount++;
		} while ( $data = array_shift( $array ) );
	}

	else
	{
		$string					.= '<tr><td><b>No Data Found</b></td></tr>';
	}
		
	$string						.= '</table>';

	return $string;
}

/**
 * Split input as needed, keeping quotated materials together.
 *
 * @ref http://us2.php.net/split
 * @author wchris on 18-Feb-2005 03:53
 *
 * @param string string to be split
 * @param string split character
 * @retrun array
 */
function cbQuoteSplit( $s, $splitter = ' ' )
{
	// First step is to split it up into the bits that are surrounded by quotes
	// and the bits that aren't. Adding the delimiter to the ends simplifies the
	// logic further down.
	$getstrings					= split( '\"', $splitter . $s . $splitter );

	// $instring toggles so we know if we are in a quoted string or not
	$delimlen					= strlen( $splitter );
	$instring					= 0;

	while (list($arg, $val) = each($getstrings))
	{
		 if ($instring == 1)
		 {
			//Add the whole string, untouched to the result array.
			$result[]			= $val;
			$instring			= 0;
		 }
		 else
		 {
			// Break up the string according to the delimiter character Each
			// string has extraneous delimiters around it (inc the ones we added
			// above), so they need to be stripped off.
			$temparray			= split( $splitter
									, substr( $val
										, $delimlen
										, strlen( $val ) - $delimlen - $delimlen
									 )
								);

			while(list($iarg, $ival) = each($temparray))
			{
				$result[] = trim($ival);
			}

			$instring = 1;
		 }
	}

	return $result;
}


/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
