<?php

// @author Michael Cannon, michael@peimic.com
// @version $Id: cb_cogs.config.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $

define('CB_COGS_DIR', 				dirname( __FILE__ ) . '/' );
define('CB_COGS_DIR_THIRD_PARTY', 	CB_COGS_DIR . '../' );
define('CB_COGS_DIR_TMP', 			'/tmp/' );
define('CB_DEV_IPS', 				'71.255.125.178|127.0.0.1|192.168.150.72' );
define('CB_DEV_FORCE_ON', 			'false' );

// include cbDebug right off the bat
require_once( CB_COGS_DIR . 'cb_string.php' );

?>
