<?php

/**
 * Peimic's database table to data access object class creator.
 *
 * Copyright (C) 2002-2004 Michael Cannon, michael@peimic.com
 * See full GNU Lesser General Public License in LICENSE.
 *
 * @author Michael Cannon <michael@peimic.com>
 * @package cb_cogs
 * @version $Id: cb_dao_creator.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */


// Needed library files
require_once( dirname(__FILE__) . '/cb_array.php');
require_once( dirname(__FILE__) . '/Adodb.config.php');

error_reporting( E_ALL );


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
		<title>CbDao Generator :: A Table to Class Helper</title>
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

// table
$tableList           			= cbPost('tableList');

// table prefix
$tablePrefix           			= cbPost('tablePrefix');

$phpExtension					= '.php';

// directory to put the newly created file into
$inclDir						= cbPost('inclDir', '.');
// remove ending /
$inclDir						= preg_replace('/\/$/', '', $inclDir);

$self							= basename( cbServer('PHP_SELF') );

// create db object and connect to database
$dbl							= & ADONewConnection(ADODB_DRIVER);
$dbl->debug						= ADODB_DEBUG;
$dbl->PConnect(ADODB_DB_HOST, ADODB_DB_USER, ADODB_DB_PASS, ADODB_DB_NAME);

// find table to query
if (!$tableList) 
{
	echo '
		<h1>Please choose a table from database: ' . ADODB_DB_NAME . '</h1>
		<form name="tables" action="'.$self.'" method="POST">
			Table prefix: <input type="text" name="tablePrefix" /><br />
			Save location: <input type="text" name="inclDir" /><br />
			<table summary="Choose Table">';

		echo '
			<tr><td colspan="2">
<script language="JavaScript">
<!-- Begin
// http://forums.devshed.com/archive/t-123751
function checkAll(allbox) {
	for (var i = 0; i < document.tables["tableList[]"].length; i++) 
	{
		document.tables["tableList[]"][i].checked = allbox.checked
	}
}
//  End -->
</script>

				<input type="checkbox" name="all" onClick="checkAll(this);" />
				Select All
			</td></tr>
		';
	
	// get a list of the database tables
	// create a picklist
	switch ( ADODB_DRIVER )
	{
		case 'oci8':
		case 'oci8po':
			$showTableSql		= '
				/* grab Oracle table names by some user */
				SELECT table_name
				FROM dba_tables
				WHERE owner = \'' . strtoupper(ADODB_DB_USER) . '\'
				ORDER BY table_name ASC
			';
			$tableKey			= 'TABLE_NAME';
			break;

		case 'mysql':
		default:
			$showTableSql		= 'SHOW TABLES;';
			$tableKey			= 'Tables_in_' . ADODB_DB_NAME;
			break;
	}

	$tables						= $dbl->GetAll($showTableSql);

	if ( $tables )
	{
		foreach ( $tables AS $key => $table )
		{
			echo '<tr><td><input type="checkbox" name="tableList[]" value="' 
				. $table[$tableKey]. '"></td><td>' 
				.  $table[$tableKey] . '</td></tr>';
		}
	}

	else
	{
		echo $showTableSql . ' failed<br />';
		pageFooter();
		exit();
	}

	echo '
			</table>
			<br />
			<input type="submit" name="submit" value="Submit">
			<input type="submit" name="cancel" value="Cancel">
		</form>';
}
 
else
{
	// create and write class code
	foreach ( $tableList AS $key => $tableName )
	{
		// create new filename
		if ( '' != $tablePrefix )
		{
			$tableName = preg_replace("/^$tablePrefix/i", '', $tableName);
		}

		echo '<h1>Here is your table class script</h1>';
		
		// get table details

		switch ( ADODB_DRIVER )
		{
			case 'oci8':
			case 'oci8po':
				$describeSql		= "
					SELECT 
						cname AS \"Field\",
						coltype AS \"Type\",
						width, 
						SCALE, 
						PRECISION, 
						NULLS, 
						DEFAULTVAL AS \"Default\",
						NULL AS \"Key\"
					FROM col 
					WHERE tname='$tableName'
				";
				break;

			case 'mysql':
			default:
				$describeSql		= "DESCRIBE `$tableName`";
				break;
		}

		$result						= $dbl->Execute($describeSql);
		$rsFields					= $result->GetArray();

		if ( !$rsFields )
		{
			echo $describeSql .' fails<br />';
			pageFooter();
			exit();
		}

		$psuedoPrimaryKey			= strtolower( $rsFields[ 0 ][ 'Field' ] );
		$psuedoPrimaryKeySet		= false;

		@array_multisort( $rsFields );


		$template = array();
		$template['CB_DAO_TABLE_NAME']		= $tableName;
		$template['CB_DAO_TABLE_PREFIX']	= $tablePrefix;

		$tableName							= strtolower( $tableName );
		$tableName							= ucfirst( $tableName );
		$template['CB_DAO_NAME']			= $tableName . 'Dao';

		// class var string
		$var					= '';

		// class constructor string
		$constructor			= '';

		// class set/get string
		$methods				= '';

		// no fields (column headers) or primary key defined yet
		$pk						= array();
		$fields					= array();

		// from table details determine fields
		foreach ( $rsFields AS $key => $field )
		{
			$fieldName 			= strtolower( $field['Field'] );
			$iVar				= '_' . $fieldName;

			// class: var $member;
			$var				.= "\tvar \$$iVar;\n";

			if ( 'PRI' == $field['Key'] )
			{
				$pk[]			= "'$fieldName'";
			}

			// assume first attribute is key
			elseif ( !$psuedoPrimaryKeySet )
			{
				$pk[]					= "'$psuedoPrimaryKey'";
				$psuedoPrimaryKeySet	= true;
			}

			// build fields list
			$fieldType			= $field['Type'];

			// strip size and type comments if any
			$fieldType			= preg_replace('/\(.+$/', '', $fieldType);
			$fieldType			= strtolower( $fieldType );

			$fields[]			= "'$fieldName' => '$fieldType'";

			// do member assignment
			// isnull
			// isint
			// else string
			$default			= $field['Default'];

			if ( is_null($default) )
			{
				$fieldDefault	= 'null';
			}

			elseif ( is_int($default) )
			{
				$fieldDefault	= $default;
			}

			else
			{
				$fieldDefault	= preg_replace( "#'#", '', $default );
				$fieldDefault	= trim( $fieldDefault );
				$fieldDefault	= "'$fieldDefault'";
			}

			$constructor		.= 
				"\n\t\t\$this->$iVar\t\t\t= $fieldDefault;";

			$method				= ucfirst( $fieldName );

			$methods			.= "
	function & get{$method} ()
	{
		return \$this->getMember( '$fieldName' );
	}

	function set{$method} ( & \$value )
	{
		return \$this->setMember( '$fieldName', \$value, '$fieldType' );
	}
";
		}
		
		$template['CB_DAO_VARS']				= $var;
		$template['CB_DAO_VAR_CONSTRUCTORS']	= $constructor;
		$template['CB_DAO_METHODS']				= $methods;

		// primary key, denote false if none
		$pk							= ( count($pk) )
										? implode("\n\t\t\t\t\t\t\t\t\t, ", $pk)
										: false;

		$fields						= implode("\n\t\t\t\t\t\t\t\t\t, ", $fields);

		$template['CB_DAO_PRIMARY_KEY']	= $pk;
		$template['CB_DAO_FIELDS']		= $fields;

		// try to open template file
		$class						= file_get_contents( dirname(__FILE__) . 
										'/cb_dao_template.php');

		// cycle through $template
		foreach ( $template AS $key => $value )
		{
			// replace like key names in template with template[] value
			// save to $class var
			$class = preg_replace("/\b$key\b/", $value, $class);
		}

		// write the class file
		$phpFile					= "{$tableName}Dao.class{$phpExtension}";
		$filepath					= $inclDir . '/' . $phpFile;
		echo 'Trying to write class file to: <b>'.$filepath.'</b><br>';
		$filehandle					= @fopen($filepath,'w+');

		if ($filehandle) 
		{
			fwrite($filehandle, $class);
			flush($filehandle);
			fclose($filehandle);
			echo 'Table to class file written successfully<br>';
		}

		else 
		{
			echo 'Table to class file was NOT written due to inssufficient privileges.<br>';
			echo 'Please copy and paste class listed below to
			<i>'.$filepath.'</i> file.';
		}

		echo '<br /><hr />';
		echo '<h2>Table to class file follows</h2>';
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
