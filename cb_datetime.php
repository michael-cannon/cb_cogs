<?php

/**
 * Peimic's centralization of common date & time functions
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package cb_cogs
 * @version $Id: cb_datetime.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */

// Needed library files
require_once( dirname(__FILE__) . '/cb_array.php');
require_once( dirname(__FILE__) . '/cb_database.php');
require_once( dirname(__FILE__) . '/cb_string.php');
require_once( dirname(__FILE__) . '/cb_validation.php');


define('CB_SECOND_IN_SECONDS',	1);
define('CB_MINUTE_IN_SECONDS',	( 60 * CB_SECOND_IN_SECONDS ) );
define('CB_HOUR_IN_SECONDS',	( 60 * CB_MINUTE_IN_SECONDS ) );
define('CB_DAY_IN_SECONDS',		( 24 * CB_HOUR_IN_SECONDS ) );
define('CB_MONTH_IN_SECONDS',	( 30.4375 * CB_DAY_IN_SECONDS ) );
define('CB_YEAR_IN_SECONDS',	( 12 * CB_MONTH_IN_SECONDS ) );

// Reference: http://www.gnu.org/manual/tar-1.12/html_chapter/tar_7.html
define('CB_UNIXTIME_MIN',	21600);
define('CB_UNIXTIME_MAX',	2146024799);


/**
 * Convert's user inputted time to a format acceptable for use in mysql
 * database queries where the TIMESTAMP (YYYYMMDDHHMMSS) type is used. If
 * $user_time is a partial period by year, month, hour, etc. then the
 * returned time is initialized to that point. 
 *
 *		Example: 
 *			1972 becomes 19720101000000
 *			197209 becomes 19720901000000
 *			9/1/1972 becomes 19720901000000
 *			9-1-1972 10:41 becomes 19720109104100
 *
 * If $as_array is true, an end time is created based upon the detail of 
 * $user_time inputted similar to initialization, but as a terminal period.
 *
 *		Example: 
 *			1972 becomes 19721231235959
 *			197209 becomes 19720931235959
 *			9/1/1972 becomes 19720931235959
 *			9-1-1972 10:41 becomes 19720109104159
 *
 * Additionally, all of the internal components of the $user_time converted
 * to start and end times is returned in an array.
 *
 * @param string $user_time human readable or mysql timestamp, ex: see above
 * @param boolean $return_as_array return result as multi-dimensional array
 * 	or integer.
 * @return integer/array
 */
function cbUserDate2Mysql($user_time = '', $return_as_array = false)
{
	// internal respresentation of time should be mysql timestamp format
	// YYYYMMDDHHMMSS or at least the components of it.
	// see http://www.mysql.com/doc/en/DATETIME.html for more information

	// time keeper array
	// false indicates not sure, not used, etc.
	$info_array					= array( 
		'user_time'			=> $user_time,
		'start_array'		=> false,
		'end_array'			=> false,
		'start'				=> false,
		'start_mktime'		=> false,
		'end'				=> false,
		'end_mktime'		=> false
	);

	// reduce whitespace to single
	$user_time					= cbTrimWhitespace($user_time);

	// was $user_time set? if not set to now
	if ( cbIsBlank($user_time) )
	{
		$info_array['user_time']	= $user_time = cbSqlNow(true);
	}

	// convert usertime to start time array
	$info_array['start_array']	= cbUserDate2Array($user_time);

	// bail if fail
	if ( !$info_array['start_array'] )
	{
		return false;
	}

	// convert usertime to start time array
	$info_array['end_array'] = cbUserDate2Array($user_time, 'end');

	// bail if fail
	if ( !$info_array['end_array'] )
	{
		return false;
	}
	
	$info_array					= cbFixDates($info_array);

	// are the components even valid?
	// is input valid date and time periods?
	if ( !cbIsDateArray($info_array) )
	{
		return false;
	}

	// store time peices to array?
	$info_array['start']		= implode('', $info_array['start_array']);
	$info_array['start_mktime']	= cbMktime($info_array['start_array']);

	// convert usertime to start time array
	$info_array['end']			= implode('', $info_array['end_array']);
	$info_array['end_mktime']	= cbMktime($info_array['end_array']);

	return ( $return_as_array ) 
		? $info_array 
		: $info_array['start'];
}


/**
 * Converts human date/time to array. 
 * 
 * @param string $user_time date/time in above format
 * @param string $time_type starting or ending type of time to create
 * 	ex: start, end
 * @return array with keys: year, month, day, hour, minute, second
 */
function cbUserDate2Array($user_time, $time_type = 'start')
{
	$date						= false;

	// check format of $user_time input
	// is it human readable or mysql

	if ( !is_numeric($user_time) )
	{
		$date					= cbHumanDate2Array($user_time, $time_type);
	}

	// possibly year only or in mysql format
	else
	{
		$date					= cbNumericDate2Array($user_time, $time_type);
	}

	return $date;
}


/**
 * Attempt to fix bad ending date components.
 *
 * @param array date/time properties
 * @return array
 */
function cbFixDates($date)
{
	// check and adjust for realistic start date
	// 6/31 to 7/1 to correct 6/30
	$start_date_done			= false;
	$start_month				= $date['start_array']['month'];
	$start_mktime				= cbMktime($date['start_array']);

	do
	{
		// compare day
		$date_start_month		= date('n', $start_mktime);

		if ( $start_month != $date_start_month )
		{
			$start_mktime		= strtotime('-1 day', $start_mktime);
		}

		else
		{
			$start_date_done	= true;
		}
	} while ( !$start_date_done );

	$date['start_array']['month']	= date('m', $start_mktime);
	$date['start_array']['day']		= date('d', $start_mktime);

	$end_date_done				= false;
	$end_month					= $date['end_array']['month'];
	$end_mktime					= cbMktime($date['end_array']);

	do
	{
		// compare day
		$date_end_month			= date('n', $end_mktime);

		if ( $end_month != $date_end_month )
		{
			$end_mktime			= strtotime('-1 day', $end_mktime);
		}

		else
		{
			$end_date_done		= true;
		}
	} while ( !$end_date_done );

	$date['end_array']['month']	= date('m', $end_mktime);
	$date['end_array']['day']	= date('d', $end_mktime);

	return $date;
}


/**
 * Create timestamp from an array populated with year, month, date, hour,
 * minute, second.
 *
 * @param array date/time properties
 * @return integer
 */
function cbMktime($date = null)
{
	if ( !is_null($date) && is_array($date) )
	{
		$time					= mktime(
			isset( $date['hour'] )		? $date['hour']		: null, 
			isset( $date['minute'] )	? $date['minute']	: null, 
			isset( $date['second'] )	? $date['second']	: null, 
			isset( $date['month'] )		? $date['month']	: null, 
			isset( $date['day'] )		? $date['day']		: null, 
			isset( $date['year'] )		? $date['year']		: null,
			isset( $date['is_dst'] )	? $date['is_dst']	: null
		);

		return $time;
	}

	return mktime();
}


/**
 * Attempts to convert human $user_time into mysql timestamp components for
 * entry into an array 12/32/2002.
 *
 * @param integer $user_time user's entered human time
 * @param string $time_type starting or ending type of time to create
 * 	ex: start, end
 * @return array keys: type, year, month, day, hour, minute, second
 */
function cbHumanDate2Array($user_time, $time_type = 'start')
{
	$temp_date					= false;
	$temp_time					= false;

	// ways to seperate dates and time
	$date_splitters				= array( '-', '/', ' ' );
	$time_splitters				= array( ':' );

	// what type of separators of /, -, or ' '
	$temp_date					= $new_user_time = cbStr2Arr($user_time, 
		$date_splitters);

	// make sure $temp_date just didn't become an unbroken array
	if ( $temp_date != array($user_time) )
	{
		// check last value, assuming to be year since US format, for 2- to
		// 4-digit conversion 
		$year_position				= sizeof($temp_date) - 1;
		$temp_date[$year_position]	= cbFixYear($temp_date[$year_position]);

		// break off time portion
		// '1972 10:41' --> '1972', '10:41'
		if ( strpos($temp_date[$year_position], ' ') )
		{
			$year				= explode(' ', $temp_date[$year_position]);
			
			// time is in second portion
			$user_time			= $year[1];
			
			// date is in first portion
			$temp_date[$year_position]	= $year[0];
		}

		$temp_date				= cbMoveYear($temp_date);

		// zero prepend as needed
		$temp_date				= cbZeroPrependArray($temp_date);

		// condense temp_date
		$temp_date_size			= sizeof($temp_date);
		$temp_date				= implode('', $temp_date);
	}
	
	// make date today for the upcoming $temp_time
	else
	{
		$temp_date				= date('Ymd');
	}

	// what type of separators of time :
	// need to remove date from $user_time?
	if ( isset($temp_date_size) )
	{
		// remove date component from $user_time via $new_user_time
		$new_user_time			= $new_user_time[$temp_date_size - 1];
		$new_user_time			= explode(' ', $new_user_time);
		
		unset( $new_user_time[0] );

		$user_time				= ( isset($new_user_time[1]) )
			? $new_user_time[1]
			: false;
	}

	$temp_time					= ( $user_time )
		? cbStr2Arr($user_time, $time_splitters)
		: false;

	// build new $temp_date as needed
	if ( $temp_time )
	{
		$temp_time				= cbZeroPrependArray($temp_time);

		// check length of $temp_date for month/year entry only
		if ( 8 > strlen($temp_date) )
		{
			// add todays date
			$temp_date			.= date('d');
		}

		// combine time with date
		$temp_date				.= implode('', $temp_time);
	}

	// how much information is available
	// is it year/month/date, month-date-year, ymd, hours:minutes
	// what content order, year first or last, time only?

	// ah who cares let another function handle it
	return ( $temp_date ) 
		? cbNumericDate2Array($temp_date, $time_type) 
		: $temp_date;
}


/**
 * Checks to see if $year needs to be converted to four digit. Years
 * over 69 become 1900's, under 2000's.
 *
 * @param integer $year
 * @return integer
 */
function cbFixYear($year)
{
	if ( !is_null($year) && is_numeric($year) && $year < 100 )  
	{
		// why not 69?
		$year					= ( $year > 69 ) 
			? 1900 + $year 
			: 2000 + $year;
	}

	return $year;
}


/**
 * Rearranges $date from MM/DD/YYYY to YYYY/MM/DD or MM/YYYY to YYYY/MM.
 *
 * @param array $date date array
 * @return array
 */
function cbMoveYear($date)
{
	// year is last array element
	$year_position				= sizeof($date) - 1;

	// pick year, month, day if applicable
	$month						= $date[0];
	$year						= $date[$year_position];

	if ( 2 == $year_position )
	{
		$day					= $date[1];
	}

	// rearrange $date to place year in front
	$date[0]					= $year;
	$date[1]					= $month;

	if ( isset($day) )
	{
		$date[2]				= $day;
	}

	return $date;
}

 
/**
 * Attempts to convert numeric $user_time into mysql timestamp components for
 * entry into an array.
 *
 * @param integer $user_time user's entered numeric time
 * @param string $time_type starting or ending type of time to create
 * 	ex: start, end
 * @return array keys: type, year, month, day, hour, minute, second
 */
function cbNumericDate2Array($user_time, $time_type = 'start')
{
	$date						= cbDateArray($time_type);

	// how big is the string to help determine components
	$user_time_size				= strlen($user_time);

	// determine type
	// numeric selection type have accounting preferences
	switch ( $user_time_size )
	{
		case 1:
		case 2:
			// M
			// MM
			$date['month']		= $user_time;
			break;

		case 3:
			// MYY
			$date['month']		= cbSubStr($user_time, 0, 1);
			$date['year']		= cbSubStr($user_time, 1);
			break;

		case 4:
		case 6:
			if ( ( cbSubStr($user_time, 0 , 2) + 0 ) <= 12 )
			{
				// MMYY
				// MMYYYY
				$date['month']	= cbSubStr($user_time, 0, 2);
				$date['year']	= cbSubStr($user_time, 2);
			}

			else
			{
				// YYYY
				// YYYYMM
				$date['year']	= cbSubStr($user_time, 0, 4);

				if ( 6 == $user_time_size )
				{
					$date['month']	= cbSubStr($user_time, 4);
				}
			}
			
			break;

		// note cascading case
		case 14:
			$date['second']		= cbSubStr($user_time, 12, 2);

		case 12:
			$date['minute']		= cbSubStr($user_time, 10, 2);

		case 10:
			$date['hour']		= cbSubStr($user_time, 8, 2);

		case 8:
			if ( ( cbSubStr($user_time, 0 , 4) + 0 ) > 1231 )
			{
				// YYYYMMDD
				$date['year']	= cbSubStr($user_time, 0, 4);
				$date['month']	= cbSubStr($user_time, 4, 2);
				$date['day']	= cbSubStr($user_time, 6, 2);
			}

			else
			{
				// MMDDYYYY
				$date['month']	= cbSubStr($user_time, 0, 2);
				$date['day']	= cbSubStr($user_time, 2, 2);
				$date['year']	= cbSubStr($user_time, 4, 4);
			}
				
			break;

		default:
			// cbPrint("case $user_time_size: for $user_time not found");
			$date				= false;
			break;
	}

	// clean up data for mysql
	if ( $date )
	{
		// check year, for 2- to 4-digit conversion 
		$date['year']			= cbFixYear($date['year']);

		// ensure numbers less than ten are prepended with 0
		$date					= cbZeroPrependArray($date);
	}

	return $date;
}


/**
 * Returns an associative array of the input variables. If the input
 * variables are empty, then based upon $type, they are preset to the start
 * or end point of their period. Month start is 01, end is 12. Minute start
 * is 00, end is 59. Year if empty is date('Y').
 *
 * @param string $type type of date, ex: start, end
 * @param string $year year period
 * @param string $month month period
 * @param string $day day period
 * @param string $hour hour period
 * @param string $minute minute period
 * @param string $second second period
 * @return array with input variables as keys
 */
function cbDateArray($type = 'start', $year = null, $month = null, $day = null, 
	$hour = null, $minute = null, $second = null)
{
	// create starting time
	if ( strtolower($type) != 'end' )
	{
		// pad numbers less than 10 with a zero
		$date					= array(
			'year'			=> ( !is_null($year) )		? $year		: date('Y'),
			'month'			=> ( !is_null($month) )		? $month	: '01',
			'day'			=> ( !is_null($day) )		? $day		: '01',
			'hour'			=> ( !is_null($hour) )		? $hour		: '00',
			'minute'		=> ( !is_null($minute) )	? $minute	: '00',
			'second'		=> ( !is_null($second) )	? $second	: '00'
		);
	}
	
	// create ending time's
	else
	{
		$date					= array(
			'year'			=> ( !is_null($year) )		? $year		: date('Y'),
			'month'			=> ( !is_null($month) )		? $month	: '12',
			'day'			=> ( !is_null($day) )		? $day		: '31',
			'hour'			=> ( !is_null($hour) )		? $hour		: '23',
			'minute'		=> ( !is_null($minute) )	? $minute	: '59',
			'second'		=> ( !is_null($second) )	? $second	: '59'
		);
	}

	return $date;
}


/**
 * Convert seconds input to hour minutes
 *
 * @param integer seconds
 * @return string
 */
function cbSeconds2HourMinute($seconds)
{
	$hour						= floor( $seconds / CB_HOUR_IN_SECONDS );
	$seconds					-= $hour * CB_HOUR_IN_SECONDS;
	$minute						= floor( $seconds / CB_MINUTE_IN_SECONDS );
	$minute						= cbZeroPrepend( $minute );
	$seconds					-= $minute * CB_MINUTE_IN_SECONDS;
	$seconds					= cbZeroPrepend( $seconds );

	// return "$hour:$minute:$seconds";
	return "$hour:$minute";
}

/**
 * Returns float of microtime.
 *
 * @return float
 */
function cbGetMicrotime()
{
	list( $usec, $sec )			= explode( ' ', microtime() );
	$mt							= (float) $usec + (float) $sec;

	return $mt;
}

function cbTimeStart()
{
	session_start();
	$time						= cbGetMicrotime();
	$_SESSION['cbTimeStart']	= $time;
	$_SESSION['cbTimePrev']		= $time;
	cbDebug( 'cbTimeStart', $time );	
}

function cbTimeMark( $title = false )
{
	$time						= cbGetMicrotime();
	$cbTimeMark					= 'cbTimeMark';

	$timePrev					= $_SESSION['cbTimePrev'];

	if ( isset( $_SESSION[ $cbTimeMark ] ) )
	{
		$mark					= $_SESSION[ $cbTimeMark ];
	}
	else
	{
		$mark					= 0;
	}

	$_SESSION[$cbTimeMark . $mark]	= $time;
	$_SESSION[ $cbTimeMark ]	= $mark++;
	$textPrev					= ' ('
									. number_format($time - $timePrev, 4)
									. ' secs)';
	cbDebug( $title ? $title : $cbTimeMark . $mark, $time . $textPrev );	
	$_SESSION['cbTimePrev']		= $time;
}

function cbTimeEnd()
{
	$timeEnd					= cbGetMicrotime();
	$_SESSION['cbTimeEnd']		= $timeEnd;
	$timePrev					= $_SESSION['cbTimePrev'];
	$textPrev					= ' ('
									. number_format($timeEnd - $timePrev, 4)
									. ' secs)';
	cbDebug( 'cbTimeEnd', $timeEnd . $textPrev );	
	cbDebug( 'Time elasped', ( $timeEnd - $_SESSION['cbTimeStart'] ) );	
}


/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
