<?php

/**
 * Peimic's centralization of common html functions
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package cb_cogs
 * @version $Id: cb_html.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */

// Needed library files
require_once( dirname(__FILE__) . '/cb_string.php');

/**
 * Send's $data to browser for downloading.
 *
 * @param string $filename $data's name of file, ex: 'data.csv'
 * @param mixed $data $filename contents, ex: arr2cvs($data)
 * @return void
 */
function cbBrowserDownload( $filename, $data )
{
	$size						= strlen( $data );

	// header("Cache-Control: private");
	header( 'Content-Type: application/octet-stream' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Content-Length: ' . $size );

	echo $data;
}

/**
 * Returns pretty printed e-mail contents for display to screen.
 *
 * @param string $to e-mail recipient
 * @param string $subject e-mail subject
 * @param string $message e-mail message body
 * @param string $mail_header e-mail mail headers
 * @return string
 */
function cbMail2html( $to, $subject, $message = '', $mail_header = '' )
{
	$to							= cbStr2html( $to );
	$subject					= cbStr2html( $subject );
	$mail_header				= cbStr2html( $mail_header );
	$message					= cbStr2html( $message );

	$out						= "
		<h3>E-mail Sent:</h3>
		<p>To: $to</p>
		<p>$mail_header</p>
		<p>Subject: $subject</p>
		<p>$message</p>
	";

	return $out;
}

/**
 * Display's a Javascript alert box with $message as its contents and then
 * returns the user to the page they came from.
 *
 * @param string $message text to be displayed
 * @param boolean $goto_previous_page return user to previous page after
 * 	alert()
 * @return void
 */
function cbJsAlertMessage($message, $goto_previous_page = true)
{
	$previous = ( $goto_previous_page ) 
		? 'history.go(-1);' 
		: '';

	$out = "<script language='javascript' text='text/javascript'>";
	$out .= "alert(\"$message\");$previous";
	$out .= '</script>';

	echo $out;

	exit();
}

/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
