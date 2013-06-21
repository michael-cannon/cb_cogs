<?php

/**
 * Peimic's centralization of common logging functions
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package cb_cogs
 * @version $Id: cb_log.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */


// Needed library files
require_once( dirname(__FILE__) . '/cb_string.php');


/**
 * Message writer for logging to display or file.
 *
 * @param string log contents
 * @param mixed null, display or file path, file write
 * @return boolean display or write success
 */
function cbLog($msg, $container = null)
{
	// ? create default file container ?
	$success = true;

	if ( cbDoLog() )
	{
		// prepend datetime to msg
		// RFC 822 formatted date: Thu, 21 Dec 2000 16:01:07 +0200
		$date = date('r');
		$pretty_msg = cbPrintString($msg);
		$log_msg = "\t" . $date . $pretty_msg;

		// display or filewrite
		if ( is_null($container) )
		{
			echo $log_msg;
			flush();
		}

		else
		{
			$file = fopen($container, 'a+'); 

			if ( $file )
			{
				$success = ( fwrite($file, $log_msg) )
					? true
					: false; 

				fclose($file);
			}

			else
			{
				cbPrint('Unable to open file: ' . $container);
				cbPrint('Try using the following');
				cbPrint('touch ' . $container);
				cbPrint('chmod 666 ' . $container);
				$success = false;
			}
		}
	}

	return $success;
}


/**
 * Returns boolean to do logging or not.
 *
 * @return boolean
 */
function cbDoLog()
{
	return ( !defined('CB_COGS_LOG_DO') || CB_COGS_LOG_DO )
		? true
		: false;
}


/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
