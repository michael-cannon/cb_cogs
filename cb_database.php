<?php

/**
 * Peimic's centralization of common database functions
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package cb_cogs
 * @version $Id: cb_database.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */

// Needed library files
require_once( dirname(__FILE__) . '/cb_datetime.php');
require_once( dirname(__FILE__) . '/cb_validation.php');

/**
 * Date orientated wrapper for cbSqlBetween
 *
 * @param string column name
 * @parameter mixed integer/string starting point
 * @parameter mixed integer/string finishing point
 * @return string
 */
function cbSqlBetweenDate($attribute, $start = false, $finish = false)
{
	// convert date to mysql
	$start_date					= cbUserDate2Mysql($start, true);
	$finish_date				= cbUserDate2Mysql($finish, true);

	$start						= ( cbIsFalse($start) || cbIsBlank($start) )
		? false
		: $start_date['start'];

	$finish						= ( cbIsFalse($finish) || cbIsBlank($finish) )
		? false
		: $finish_date['end'];

	return cbSqlBetween($attribute, $start, $finish);
}

/**
 * Create inclusive BETWEEN, <=, >=, or some range based upon $start and
 * $finish input.
 *
 * @param string column name
 * @parameter mixed integer/string starting point
 * @parameter mixed integer/string finishing point
 * @parameter boolean negate result
 * @return string
 */
function cbSqlBetween($attribute, $start = false, $finish = false,
	$negate = false)
{
	$sql						= '';

	$negate						= ( !$negate )
		? ''
		: 'NOT ';

	// PostgeSQL needs '' or "" encaps, sometimes it's empty as previous, same
	// as false
	$start						= ( "''" != $start && '""' != $start )
		? $start
		: false;
	
	$finish						= ( "''" != $finish && '""' != $finish )
		? $finish
		: false;
	
	// has start and finish
	if ( $start && $finish )
	{
		// correct order?
		if ( $start > $finish )
		{
			cbSwap($start, $finish);
		}

		$sql					= 'AND ' . $negate 
			. '(' . $attribute . ' BETWEEN ' . $start . ' AND ' . $finish . ')';
	}

	// start only
	elseif ( $start )
	{
		$sql					= 'AND ' . $negate 
			. '(' . $attribute . ' >= ' . $start . ')';
	}

	// finish only
	elseif ( $finish )
	{
		$sql					= 'AND ' . $negate 
			. '(' . $attribute . ' <= ' . $finish . ')';
	}

	// nothing given ie: all
	// else{}

	return $sql;
}


/**
 * Returns string of SQL datetime format for now.
 *
 * @param boolean return as SQL datetime or MySQL timestamp
 * @return string
 */
function cbSqlNow($mysql_timestamp = false)
{
	return ( !$mysql_timestamp )
		? date('Y-m-d H:i:s')
		: date('YmdHis');
}


/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
