<?php

/**
 * Simple, but generally secure mail form script for cb_common
 *
 * Required field names: to, from_email, subject
 * Optional field names: cc, bcc
 *
 * @author Michael Cannon <michael@peimic.com>
 * @version $Id: cb_formmail.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */


// include some common code helpers
$cb_root						= dirname(__FILE__) . '/';
include_once($cb_root . 'cb_array.php');
include_once($cb_root . 'cb_html.php');
include_once($cb_root . 'cb_log.php');
include_once($cb_root . 'cb_string.php');
include_once($cb_root . 'cb_validation.php');
include_once($cb_root . 'htmlMimeMail/htmlMimeMail.php');


// check referrer
// bad, alert
$SERVER_NAME					= cbEnv('SERVER_NAME');
$HTTP_REFERER					= cbServer('HTTP_REFERER');

// bail if the referrer isn't on the same server as the host
if ( !cbIsValidReferrer($SERVER_NAME, $HTTP_REFERER) )
{
	$error_msg					= "Error - This script must be used from within '$SERVER_NAME' to prevent spam use.";

	cbJsAlertMessage($error_msg);
}

// check e-mail
// 	bad, alert
$to								= cbRequest('to');
$from_email						= cbRequest('from_email');
$cc								= cbRequest('cc');
$bcc							= cbRequest('bcc');
	
if ( !$to )
{
	$error_msg					= "Empty 'to:' field, please re-enter it.";

	cbJsAlertMessage($error_msg);
}

if ( !$from_email )
{
	$error_msg					= "Empty 'From E-mail:' field, please re-enter it.";

	cbJsAlertMessage($error_msg);
}

$required						= cbRequest('required');

if ( $required )
{
	$required					= explode( ',', $required );
	$error_msg					= '';

	foreach ( $required as $key => $value )
	{
		if ( ! isset( $_REQUEST[ $value ] ) || '' == $_REQUEST[ $value ] )
		{
			$error_msg			.= "Empty '$value' field, please complete it. ";
		}
	}

	if ( $error_msg )
	{
		cbJsAlertMessage($error_msg);
	}
}

// Put all addresses into a single string
$to								= str_replace(';', ',', $to);
$all_email_addresses			= $to;

$from_email						= str_replace(';', ',', $from_email);
$all_email_addresses			.= ',' . $from_email;

if ( $cc )
{
	$cc							= str_replace(';', ',', $cc);
	$all_email_addresses		.= ',' . $cc;
}

// always bcc sender
if ( $bcc ) 
{
	$bcc						= $from_email . ',' . $bcc;
}

else 
{
	$bcc						= $from_email;
}

$bcc							= str_replace(';', ',', $bcc);
$all_email_addresses			.= ',' . $bcc;

// split address list into array
$all_email_addresses			= split(',', $all_email_addresses);

foreach ($all_email_addresses AS $key => $value)
{
	// clean addresses
	cbCleanEmails($value);

	// validate each address further here
	if ( cbIsBlank($value) )
	{
		continue;
	}

	elseif ( !cbIsEmail( $value ) )
	{
		$error_msg				= "'$value' is not a valid e-mail address, please re-enter it.";

		cbJsAlertMessage($error_msg);
	}
}

// build e-mail
$subject						= cbRequest('subject');

if ( !$subject )
{
	$error_msg					= "Empty 'Subject:' field, please re-enter it.";

	cbJsAlertMessage($error_msg);
}

$message_body					= cbRequest( 'message_body', '' );
$pad_width						= 32;
$pad_string						= ' ';

// add date entry
$_REQUEST[ 'date_sent' ]		= date('n/j/Y g:i A');

$bodyIgnoreArr					= array(
									'from_email'
									, 'to'
									, 'subject'
									, 'message_body'
									, 'bcc'
									, 'thankyou'
									, 'log_email'
									, 'PHPSESSID'
									, 'logintheme'
									, 'cprelogin'
									, 'cpsession'
								);

if ( '' != $message_body )
{
	$message_body				.= "\r\n\r\n";
}

$valueCounter					= array();

foreach ( $_REQUEST AS $key => $value )
{
	// implode if array
	if ( is_array( $value ) )
	{
		$value					= implode( '; ', $value );
	}

	// grab form pieces, clean them up, format, and append
	$value						= cbCleanStr($value);

	// Carry over submitted user data, UD, to session for reuse
	$_SESSION[ 'CB_UD_' . $key ]	= $value;

	if ( in_array( $key, $bodyIgnoreArr ) )
	{
		continue;
	}

	$key						= cbMkReadableStr($key);
	$key						= str_pad($key, $pad_width, $pad_string);
	$message_body 				.= $key . ' ' . $value . "\r\n";
}

// build e-mail
$mail							= new htmlMimeMail();

// only use one from email
$from_email						= explode( ',', $from_email );
$from_email						= $from_email[ 0 ];

$mail->setFrom($from_email);
$mail->setSubject($subject);
$mail->setText($message_body);

if ( !cbIsBlank($cc) )
{
	$mail->setCc($cc);
}
	
if ( false && !cbIsBlank($bcc) )
{
	$mail->setBcc($bcc);
}
	
$logEmail						= cbRequest('log_email');

if ( $logEmail )
{
	define( 'CB_COGS_LOG_DO', 	true );

	// log the darn thing
	cbLog( $message_body, 'email-log.txt' );
}

// echo cbMail2html($to, $subject, $message_body, $from_email);
// exit();

// send it
// 	bad, alert
$result							= $mail->send( cbStr2Arr($to) );

if ( $result ) 
{
	$sent_message				= 'Your e-mail was successfully sent.';
	
	$thankyou					= cbRequest('thankyou');

	if ( $thankyou )
	{
		header( "location: $thankyou" );
		exit();
	}

	cbJsAlertMessage($sent_message);
}

else 
{
	$sent_message				= 'An unknown error occured while attempting to
		send your e-mail. Please try again in a few moments.';
	
	cbJsAlertMessage($sent_message);
} 

?>
