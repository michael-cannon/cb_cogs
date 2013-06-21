<?php

/**
 * Peimic's basic class creator with setters, getters.
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon <michael@peimic.com>
 * @package cb_cogs
 * @version $Id: cb_class_creator.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */


// Needed library files
require_once( dirname(__FILE__) . '/cb_array.php');


/**
 * Returns HTML page top.
 *
 * @return string
 */
function pageHeader()
{
	$out = "
	<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN'>
	<html>
	<head>
		<title>Cb Class Generator :: Class Member/Method Helper</title>
	</head>
	<style type='text/css'>
		body  { font-family: 'Verdana', 'Arial', 'Sans-Serif'; text-align: left;
		background: #ffffff; }
		h1    { color: #004d9c; font-size: 12pt; font-weight: bold }
		h2    { color: #004d9c; font-size: 10pt; font-weight: bold }
		h3    { color: #004d9c; font-size: 10pt; }
		p     { color: #004d9c; font-size: 8pt; }
		table { border: 2px solid #004d9c; font-size: 8pt; text-align: center; border-collapse: 'collapse'; }
		td    { border: 2px solid; padding: 2px; color: #004d9c; font-size: 8pt; }
	</style>
	<body>
	";

	echo $out;
}

/**
 * Returns HTML page bottom.
 *
 * @return string
 */
function pageFooter()
{
	$out = '</body></html>';

	echo $out;
}

/**
 * Echo htmlentities version of $string.
 *
 * @param string $string, ex: "<html><head><title>..."
 * @return void
 */
function echoHtml($string) 
{
	echo htmlentities($string) . "\n";
}

// begin script
pageHeader();

$className						= cbPost( 'className' );
$classBase						= cbPost( 'classBase', 'AutoBase' );
$classMembers					= cbPost( 'classMembers' );
$classExtends					= cbPost( 'classExtends' );

// directory to put the newly created file into
$inclDir						= cbPost('inclDir', '.');
// remove ending /
$inclDir						= preg_replace('/\/$/', '', $inclDir);

$self							= basename( cbServer('PHP_SELF') );

// find table to query
if ( !$className )
{
	echo '
		<h1>Please Choose a Class Name</h1>
		<form name="members" action="'.$self.'" method="POST">
			Class name: <input type="text" name="className" value="'
			. $className . '" /><br />
			Class postfix: <input type="text" name="classBase" value="'
			. $classBase . '" /><br />
			Class extends: <input type="text" name="classExtends" value="'
			. $classExtends . '" /><br />
			Member names separated by new lines:<br />
			<textarea name="classMembers">' . $classMembers . '</textarea><br />
			<br />
			Save location: <input type="text" name="inclDir" /><br />
			<br />
			<input type="submit" name="submit" value="Submit">
			<input type="submit" name="cancel" value="Cancel">
		</form>';
}
 
else
{
	$classNameOrig				= ucfirst( $className );
	$className					= $classNameOrig;
	$className					= $className . $classBase;

	// create and write class code
	// create new filename
	$phpFile					= $className . '.class.php';

	echo '<h1>Here is your table class script</h1>';

	$classExtendsExtend			= ( $classExtends )
									? 'extends ' . $classExtends
									: '';
	
	$classExtendsInit			= ( $classExtends )
									? '@parent::' . $classExtends . '()'
									: '';

	// comment none-DAO for now
	$classExtendsInit			= ( preg_match( '#dao#i', $classExtends ) )
									? '' . $classExtendsInit
									: '// ' . $classExtendsInit;

	$require					= ( $classExtends )
									? "require_once( dirname( __FILE__ ) . '/$classExtends.class.php' );"
									: '';
	
	$template = array();
	$template['CB_CLASS_NAME']			= $className;
	$template['CB_CLASS_REQUIRE']		= $require;
	$template['CB_CLASS_EXTENDS']		= $classExtendsExtend;
	$template['CB_CLASS_EXTENDS_INIT']	= $classExtendsInit;
	$template['CB_CLASS_VARS']			= '';
	$template['CB_CLASS_METHODS']		= '';

	// split classMembers into an array
	// cycle through classMembers if count > 0
	// create var and methods
	// var get _ prefix
	// methods get getMemberNamed convention
	$classMembers				= preg_split( "#\r?\n#", $classMembers );

	if ( 0 < count( $classMembers ) )
	{
		natsort( $classMembers );

		// class var string
		$var						= '';

		// class methods string
		$methods					= '';

		// from table details determine fields
		foreach ( $classMembers AS $key => $field )
		{
			// don't play with blanks
			if ( '' != $field )
			{
				$iVar			= '_' . $field;
				$method			= ucfirst( $field );

				// class: var $member;
				$var 			.= "\tvar \$$iVar\t\t= null;\n";

				$methods		.= "
	function & get{$method} ()
	{
		return \$this->$iVar;
	}

	function set{$method} ( & \$value )
	{
		\$this->$iVar			= \$value;
	}
";
			}
		}
		
		$template['CB_CLASS_VARS']		= $var;
		$template['CB_CLASS_METHODS']	= $methods;
	}

	// try to open template file
	$class						= file_get_contents( dirname(__FILE__) . 
									'/cb_class_template.php');

	// cycle through $template
	foreach ( $template AS $key => $value )
	{
		// replace like key names in template with template[] value
		// save to $class var
		$class					= preg_replace("/\b$key\b/", $value, $class);
	}

	// 
	$class						= preg_replace( "#
#", '', $class);

	// write the class file
	$filepath					= $inclDir . '/' . $phpFile;
	echo 'Trying to write class file to: <b>'.$filepath.'</b><br>';
	$filehandle					= @fopen($filepath,'w+');

	if ($filehandle) 
	{
		fwrite($filehandle, $class);
		flush($filehandle);
		fclose($filehandle);
		echo 'Class file written successfully<br>';
	}

	else 
	{
		echo 'Class file was NOT written due to inssufficient privileges.<br>';
		echo 'Please copy and paste class listed below to
		<i>'.$filepath.'</i> file.';
	}

	echo '<br /><hr />';
	echo '<h2>Class file follows</h2>';
	echo '<pre>';
	echoHtml($class);
	echo '</pre><hr>';

	// child class
	if ( '' != $classBase )
	{
		// child extends parent which is above
		$classExtends			= $className;
		$className				= $classNameOrig;

		// create and write class code
		// create new filename
		$phpFile				= $className . '.class.php';

		echo '<h1>Here is your table class script</h1>';

		$classExtendsExtend		= 'extends ' . $classExtends;
		$classExtendsInit		= '@parent::' . $classExtends . '()';
		$require				= "require_once( dirname( __FILE__ ) .  '/$classExtends.class.php' );";
		
		$template = array();
		$template['CB_CLASS_NAME']			= $className;
		$template['CB_CLASS_REQUIRE']		= $require;
		$template['CB_CLASS_EXTENDS']		= $classExtendsExtend;
		$template['CB_CLASS_EXTENDS_INIT']	= $classExtendsInit;
		$template['CB_CLASS_VARS']			= '';
		$template['CB_CLASS_METHODS']		= '';

		// try to open template file
		$class					= file_get_contents( dirname(__FILE__) . 
										'/cb_class_template.php');

		// cycle through $template
		foreach ( $template AS $key => $value )
		{
			// replace like key names in template with template[] value
			// save to $class var
			$class				= preg_replace("/\b$key\b/", $value, $class);
		}

		// 
		$class					= preg_replace( "#
#", '', $class);

		// write the class file
		$filepath				= $inclDir . '/' . $phpFile;
		echo 'Trying to write class file to: <b>'.$filepath.'</b><br>';
		$filehandle				= @fopen($filepath,'w+');

		if ($filehandle) 
		{
			fwrite($filehandle, $class);
			flush($filehandle);
			fclose($filehandle);
			echo 'Class file written successfully<br>';
		}

		else 
		{
			echo 'Class file was NOT written due to inssufficient privileges.<br>';
			echo 'Please copy and paste class listed below to
			<i>'.$filepath.'</i> file.';
		}

		echo '<br /><hr />';
		echo '<h2>Class file follows</h2>';
		echo '<pre>';
		echoHtml($class);
		echo '</pre><hr>';
	}
}

// exit program
pageFooter();

/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
