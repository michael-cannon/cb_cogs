<?php

/**
 * Peimic's centralization of common directory and file functions
 *
 * Copyright (C) 2004 Michael Cannon <michael@peimic.com>
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon <michael@peimic.com>
 * @package cb_cogs
 * @version $Id: cb_dir_file.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */


// Needed library files
require_once( dirname(__FILE__) . '/cb_validation.php');


/**
 * Builds an array of file URI's based upon the match of $file_type within a 
 * filename.
 *
 * @param string $directory directory being looked into trailing slash not
 * needed
 * @param string $file_type regexp type of files to look for, 
 * 	ex: readme, install
 * @param boolean $dir_prepend prepend $dir to found files in array
 * @param boolean $case_sensitve match $file_type case,
 * @param mixed boolean/string $file_only return only file names, 
 * 	ex: true (files only), false (dirs only), 'ALL' (dir contents)
 * @return array
 */
function cbGetDirListing($directory = '.', $file_type = '', 
	$dir_prepend = false, $case_sensitive = false, $file_only = true)
{
	$list						= array();

	// create regexp, lookfor case sensitive $file_type
	$regexp						= '/' . $file_type . '/';

	// case insensitive
	if ( !$case_sensitive )
	{
		$regexp					.= 'i';
	}

	// open module directory
	$dir						= dir($directory);

	// cycle through directory listing
	// !== is a PHP 4.0+ comparison type and equality check
	// see http://www.php.net/manual/en/class.dir.php for more info
	while ( false !== ( $file = $dir->read() ) )
	{
		// ignore ./..
		if ( '.' != $file && '..' != $file )
		{
			// compare filenames there with the $file_type being searched for
			// when a case insensitive match is found
			if ( preg_match($regexp, $file) )
			{
				$path_file		= $dir->path . '/' . $file;

				// files only
				if ( cbIsTrue($file_only) && is_file($path_file) )
				{
						// add that filename to $list
						$list[]	= $file;
				}

				// dirs only
				elseif ( cbIsFalse($file_only) && is_dir($path_file) )
				{
						// add that directory to $list
						$list[]	= $file;
				}

				// everything
				elseif ( 'ALL' == $file_only )
				{
					// add that directory contents to $list
					$list[]		= $file;
				}
			}
		}
	}
	
	if ( $dir_prepend )
	{
		foreach ( $list AS $key => $file )
		{
			// update $file with $dir->path()
			$list[$key]			= $dir->path . '/' . $file;
		}
	}

	$out						= array(
		'directory'			=> $directory,
		'file_type'			=> $file_type,
		'dir_prepend'		=> $dir_prepend,
		'case_sensitive'	=> $case_sensitive,
		'regexp'			=> $regexp,
		'file_only'			=> $file_only,
		'list'				=> $list
	);

	$dir->close();

	return $list;
}

/**
 * mindplay(at)mindplay(dot)dk
25-Aug-2002 12:06
Please remove my previous entry - the following entry corrects some minor
errors, which would cause problems with a few download managers...

Here's a function for sending a file to the client - it may look more
complicated than necessary, but has a number of advantages over simpler file
sending functions:

- Works with large files, and uses only an 8KB buffer per transfer.

- Stops transferring if the client is disconnected (unlike many scripts, that
  continue to read and buffer the entire file, wasting valuable resources) but
does not halt the script

- Returns TRUE if transfer was completed, or FALSE if the client was
  disconnected before completing the download - you'll often need this, so you
can log downloads correctly.

- Sends a number of headers, including ones that ensure it's cached for a
  maximum of 2 hours on any browser/proxy, and "Content-Length" which most
people seem to forget.

Note that the folder from which protected files will be pulled, is set as a
constant in this function (/protected) ... Now here's the function:
 *
 * @param string filepath with filename
 * @param string file MIME type
 * @return boolean
 */
function cbSendFile($filepath, $mime = 'application/octet-stream') 
{
	$status						= false;
	$filename					= basename($filepath);

	if ( !is_file($filepath) || connection_status() != 0 )
	{
		return 	false;
	}

	header('Content-type: ' . $mime);
	header("Content-Disposition: inline; filename=\"".$filename."\"");
	header('Content-length: ' . (string) filesize($filepath) );
	header('Expires: '. gmdate('D, d M Y H:i:s', mktime(date('H')+2, date('i'),
		date('s'), date('m'), date('d'), date('Y'))).' GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');

	if ( $file = fopen($filepath, 'rb') ) 
	{
		while( !feof($file) && ( connection_status() == 0 ) ) 
		{
			print( fread($file, 1024*8) );
			flush();
		}

		$status					= ( connection_status() == 0 );
		fclose($file);
	}

	return $status;
}

?>
