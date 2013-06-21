<?php

/**
 * Class CB_DAO_NAME
 *
 * Data Access Object (DAO) class for helping with SQL operations.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package TBD 
 * @version $Id: cb_dao_template.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $ 
 */


// Create a definition for CB_COGS_DIR in your calling script configuration
// Ex: define('CB_COGS_DIR', '/home/barney/www/cb_cogs/');

// Required base CbDao class containing actual SQL like named operations
require_once(CB_COGS_DIR . 'CbDao.class.php');


class CB_DAO_NAME extends CbDao
{
CB_DAO_VARS

	/**
	 * Class constructor.
	 *
	 * @param mixed $input, preset array/object
	 * @param resource Adodb database connection
	 * @return void
	 */
	function CB_DAO_NAME ( $input = null, $db = null )
	{
		$this->_CbDaoTablePrefix	= 'CB_DAO_TABLE_PREFIX';
		$this->_CbDaoTableName	= 'CB_DAO_TABLE_NAME';
		$this->fullTableName	= $this->_CbDaoTablePrefix
									. $this->_CbDaoTableName;

CB_DAO_VAR_CONSTRUCTORS

		$this->_CbDaoPrimaryKey	= array(
									CB_DAO_PRIMARY_KEY
								);

		$this->_CbDaoFields		= array(
									CB_DAO_FIELDS
								);

		$this->loadMembers( $input );
		$this->setDb( $db );
	}

CB_DAO_METHODS
}

/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
