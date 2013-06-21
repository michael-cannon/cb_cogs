<?php

/**
 * Peimic's centralization of common validation functions
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package cb_cogs
 * @version $Id: cb_validation.php,v 1.1.1.1 2010/04/15 09:55:56 peimic.comprock Exp $
 */


// Needed library files
require_once( dirname(__FILE__) . '/cb_datetime.php');


/**
 * Returns boolean depending upon $variable being true or not.
 *
 * @param mixed variable $variable text sequence, ex: true, 'true'
 * @return boolean
 */
function cbIsTrue($variable)
{
	if ( !is_null($variable) && !is_numeric($variable)
		&& is_bool($variable) || is_string($variable) ) 
	{
		return ( true === $variable || preg_match('/^true$/i', $variable) 
			|| preg_match('/^Y$/i', $variable)
			|| preg_match('/^Yes$/i', $variable) )
			? true 
			: false;
	}

	return false;
}
// require_once( dirname(__FILE__) . '/cb_TBD.php');

/**
 * Returns boolean depending upon $variable being false or not.
 *
 * @param mixed variable $variable text sequence, ex: false, 'false'
 * @return boolean
 */
function cbIsFalse($variable)
{
	if ( !is_null($variable) && !is_numeric($variable)
		&& is_bool($variable) || is_string($variable) ) 
	{
		return ( false === $variable || preg_match('/^false$/i', $variable )
			|| preg_match('/^N$/i', $variable)
			|| preg_match('/^No$/i', $variable) )
			? true 
			: false;
	}

	return false;
}

/**
 * Returns boolean depending upon $string being empty or blank.
 *
 * @param string $string text sequence, ex: '', 'Now is the...'
 * @return boolean
 */
function cbIsBlank($string)
{
	return ( !is_null($string) && '' == $string ) 
		? true 
		: false;
}


/**
 * Checks for invalid date and time components in $usertime_array.
 *
 * @param array $usertime with keys start_array & end_array
 * @return boolean
 */
function cbIsDateArray($usertime)
{
	// check values of *_array to be in certain ranges
	// year cbIsBetween 1900 and 2100
	if ( !cbIsBetween($usertime['start_array']['year'], 1900, 2100) 
		|| !cbIsBetween($usertime['end_array']['year'], 1900, 2100) )
	{
		return false;
	}

	// month cbIsBetween 1 and 12
	if ( !cbIsBetween($usertime['start_array']['month'], 1, 12) 
		|| !cbIsBetween($usertime['end_array']['month'], 1, 12) )
	{
		return false;
	}

	// day cbIsBetween 1 and 31
	if ( !cbIsBetween($usertime['start_array']['day'], 1, 31) 
		|| !cbIsBetween($usertime['end_array']['day'], 1, 31) )
	{
		return false;
	}


	// hours cbIsBetween 0 and 23
	if ( !cbIsBetween($usertime['start_array']['hour'], 0, 23) 
		|| !cbIsBetween($usertime['end_array']['hour'], 0, 23) )
	{
		return false;
	}

	
	// minutes, seconds cbIsBetween 0 and 59
	if ( !cbIsBetween($usertime['start_array']['minute'], 0, 59) 
		|| !cbIsBetween($usertime['end_array']['minute'], 0, 59) 
		|| !cbIsBetween($usertime['start_array']['second'], 0, 59) 
		|| !cbIsBetween($usertime['end_array']['second'], 0, 59) )
	{
		return false;
	}


	return true;
}


/**
 * Returns boolean depending upon whether $number is in [$low, $high].
 *
 * @param float $number number being looked for
 * @param float $low low end of number being looked for
 * @param float $high high end of number being looked for
 * @return boolean
 */
function cbIsBetween($number, $low, $high)
{
	if ( !is_null($number) && !is_null($low) && !is_null($high)
		&& is_numeric($number) && is_numeric($low) && is_numeric($high) )
	{
		return ( $low <= $number && $number <= $high );
	}

	return false;
}

/**
 * Returns boolean depending upon whether unix timestamp or not.
 *
 * @param integer unix timestamp
 * @boolean success or not
 */
function cbIsUnixtime($unix)
{
	return ( is_numeric($unix) 
		&& cbIsBetween($unix, CB_UNIXTIME_MIN, CB_UNIXTIME_MIN) );
}

/**
 * Returns boolean whether ABA routing number or not.
 *
 * @link http://www.brainjar.com/js/validation/default.asp
 * @link http://www.cflib.org/udf.cfm?ID=552
 *
 * @param integer ABA routing number
 * @return boolean
 */
function cbIsAba($routing)
{
	// run through each digit and calculate the total.
	$n							= 0;

	$routingLength				= strlen($routing);

	// ABA routing numbers are always 9 digits long
	if ( preg_match('/[^0-9]/', $routing) || 9 != $routingLength )
	{
		return false;
	}

	// multiply the first digit by 3, the second by 7, the third by 1, the
	// fourth by 3, the fifth by 7, the sixth by 1, etc., and add them all up.

	for ( $i = 0; $i < $routingLength; $i += 3 ) 
	{
		$n						+= $routing[$i] * 3;
		$n						+= $routing[$i + 1] * 7;
		$n						+= $routing[$i + 2];
	}

	// If this sum is an integer multiple of 10 (e.g., 10, 20, 30, 40, 50,...)
	// then the number is valid, as far as the checksum is concerned.
	// (but not zero),
	if ( 0 != $n && 0 == ( $n % 10 ) )
	{
		return true;
	}

	else
	{
		return false;
	}
}

/**
 * Return boolean based upon whether given input is blank or null. Alternative
 * to empty, which considers 0 and false to be empty.
 *
 * @ref http://us2.php.net/empty
 *
 * @param mixed
 * @return boolean
 */
function cbIsNullBlank( $value )
{
	$empty						= ( is_null( $value ) || '' == $value )
									? true
									: false;

	return $empty;
}

/**
 * Verify that $email meets requirements specified by regular expression. 
 * Store various parts in $check_pieces array and then checks to see that
 * the top level domain is valid, but not the username itself.
 *
 * strstr() returns all of first parameter found after second parameter.
 * substr() returns all of the string found between the first and second 
 * parameters.
 * getmxrr() verifies that domain MX record exists.
 * checkdnsrr() checks DNS's not MX'd.
 *
 * Resource:
 * 1. Gilmore, W.J. PHP Networking. April 5, 2001.  
 *    http://www.onlamp.com/lpt/a//php/2001/04/05/networking.html.
 *    wj@wjgilmore.com.
 *
 * @param string $email
 * @param boolean $check_mx verify mail exchange or DNS records
 * @return boolean true if valid e-mail address
 */
function cbIsEmail($email, $check_mx = true)
{
    $debug = false;
    // all characters except @ and whitespace
    $name = '[^@\s]+';

    // letters, numbers, hyphens separated by a period
    $sub_domain = '[-a-z0-9]+\.';

    // country codes
    $cc = '[a-z]{2}';

    // top level domains
    $tlds = "$cc|com|net|edu|org|gov|mil|int|biz|pro|info|arpa|aero|coop|name|museum";

    $email_pattern = "/^$name@($sub_domain)+($tlds)$/ix";
    
    $skip_check = array(
		'peimic.com',
		'movingiron.com',
		'intelauto.com',
		'bcs-it.com'
    );
    
    $need_confirm = array(
        'yahoo.com'
    );

    if ( preg_match($email_pattern, $email, $check_pieces) )
    {
        // check mail exchange or DNS
        if ($check_mx)
        {
            $host = substr(strstr($check_pieces[0], '@'), 1);
            if (in_array($host, $skip_check)) {
            	return true;
            }
            if (in_array($host, $need_confirm)) {
            	return false;
            }
            if ($debug) echo "<hr>".$host;
            if ($debug) echo "<hr>".$check_pieces[0];
            //Check DNS records
            if(checkdnsrr($host, "MX"))
            {
                if(!getmxrr($host, $mxhost, $mxweight))
                {
                    if ($debug) echo "Can't found records mail servers!";
                    return false;
                }
            }
            else
            {
                $mxhost[] = $host;
                $mxweight[] = 1;
            }

            $weighted_host = array();
            for($i = 0; $i < count($mxhost); $i ++)
            {
                $weighted_host[($mxweight[$i])] = $mxhost[$i];
            }
            ksort($weighted_host);

            foreach($weighted_host as $host)
            {
                if ($debug) echo "<hr>".$host;
                if(!($fp = @fsockopen($host, 25, $errno, $errstr)))
                {
                    if ($debug) echo "<hr>Can't connect to host: $host ($errstr: $errno)<br/>";
                    continue;
                }

                $stopTime = time() + 12;
                $gotResponse = FALSE;
                stream_set_blocking($fp, FALSE);

                while(true)
                {
                    $strresp = fgets($fp, 1024);
                    if(substr($strresp, 0, 3) == "220")
                    {
                        $stopTime = time() + 12;
                        $gotResponse = true;
                    }
                    elseif(($strresp == "") && ($gotResponse))
                    {
                        break;
                    }
                    elseif(time() > $stopTime)
                    {
                        break;
                    }
                }
                if(!$gotResponse)
                {
                    continue;
                }
                stream_set_blocking($fp, true);

                fputs($fp, "HELO {$_SERVER['SERVER_NAME']}\r\n");
                fgets($fp, 1024);

                fputs($fp, "MAIL FROM: <httpd@{$_SERVER['SERVER_NAME']}>\r\n");
                fgets($fp, 1024);

                fputs($fp, "RCPT TO: <$email>\r\n");
                $line = fgets($fp, 1024);

                fputs($fp, "QUIT\r\n");

                fclose($fp);
                if(substr($line, 0, 3) != "250")
                {
                    $error = $line;
                    if ($debug) echo "<br/>Error".$error;
                    return false;
                }
                else return true;
            }
            if ($debug) echo "Error: Can't connect to mail server<br/><br/>";
            return false;
        }
        return true;
    }
    return false;
}

/**
 * Checks for illegal referer call to prevent outside use of the script 
 * for spamming. The script simply checks to see that $HTTP_REFERER contains
 * text from $valid_site_domain.
 *
 * @param string/array $valid_site_domain, ex: 'example.com'
 * @param string/array $HTTP_REFERER, ex: 'http://example.com/asdf.php'
 * @return boolean
 */
function cbIsValidReferrer($valid_site_domain, $HTTP_REFERER) 
{
	if ( preg_match('/,/', $valid_site_domain) )
	{
		$valid_site_domain = str2arr($valid_site_domain);
	}

	if ( !is_array($valid_site_domain) )
	{
		$valid_site_domain = array($valid_site_domain);
	}

	foreach ( $valid_site_domain AS $key => $value )
	{
		// domain found in referer
	if ( preg_match("/$value/i", $HTTP_REFERER) )
		{
			return true;
		}
	}
   
	// domain not found in referer
	return false;
}

/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
