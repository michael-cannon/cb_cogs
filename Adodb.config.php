<?php

// @author Michael Cannon, michael@peimic.com
// @version $Id: Adodb.config.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $

require_once( dirname(__FILE__) . '/cb_cogs.config.php');

// ----- ADODB INITIALIZATON -----
define('ADODB_DIR', 			CB_COGS_DIR_THIRD_PARTY . 'adodb/');
define('ADODB_DIR_CACHE',		CB_COGS_DIR_TMP . 'adodb/cache/');
define('ADODB_DRIVER',			'mysql');
define('ADODB_DB_HOST', 		'localhost');
define('ADODB_DB_USER', 		'root');
define('ADODB_DB_PASS', 		'bUpaG07pp');
define('ADODB_DB_NAME', 		'test');

// 0 = assoc lowercase field names. $rs->fields['orderid']
// 1 = assoc uppercase field names. $rs->fields['ORDERID']
// 2 = use native-case field names. $rs->fields['OrderID'] -- default
// oracle uppercases so 2 is bad, use 0
define('ADODB_ASSOC_CASE',		2); 

// cache lengths 1 minute, 1 hour
define('ADODB_CACHE_SHORT',		0);
// define('ADODB_CACHE_SHORT',		60);
define('ADODB_CACHE_LONG',		0);
// define('ADODB_CACHE_LONG',		3600);

// on true, off false
define('ADODB_DEBUG',			false);

// require_once( ADODB_DIR . 'adodb.inc.php' );
// require_once (ADODB_DIR . 'drivers/adodb-oci8po.inc.php' );

// global $ADODB_FETCH_MODE;
$ADODB_FETCH_MODE				= ADODB_FETCH_ASSOC;


/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
