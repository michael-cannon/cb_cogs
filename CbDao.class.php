<?php

/**
 * Class CbDao
 *
 * Base Data Access Object (DAO) class for helping with SQL operations.
 *
 * @author Michael Cannon, michael@peimic.com
 * @package cb_cogs 
 * @version $Id: CbDao.class.php,v 1.1.1.1 2010/04/15 09:55:55 peimic.comprock Exp $
 */

class CbDao
{
	var $fullTableName;

	var $_CbDaoFields;
	var $_CbDaoPrimaryKey;
	var $_CbDaoTableName;
	var $_CbDaoTablePrefix;
	var $_db;
	var $_dbError;
	var $_error;
	var $_tableAlias;

	/**
	 * Class constructor.
	 *
	 * @param mixed $input, preset array/object
	 * @param resource Adodb database connection
	 * @return void
	 */
	function & CbDao ( $input = null, $db = null )
	{
		$this->fullTableName	= '';

		// _CbDaoFields Ex: id => int, name => string, email => string, 
		// 	weight => float
		$this->_CbDaoFields		= array();

		$this->_CbDaoPrimaryKey	= array();
		$this->_CbDaoTableName	= '';
		$this->_CbDaoTablePrefix	= '';

		$this->setDb( $db );
		$this->setTableAlias();
	}

	/**
	 * Set primary key
	 *
	 * @param mixed string attribute name or array of attribute names
	 * @return boolean
	 */
	function setPrimaryKey( $key )
	{
		$key					= ( !is_array( $key ) )
									? array( $key )
									: $key;

		// check that the proposed keys are valid
		foreach ( $key AS $ikey => $value )
		{
			if ( !$this->isValidField( $value ) )
			{
				return false;
			}

			$key[ $ikey ]		= strtolower( $value );
		}

		$this->_CbDaoPrimaryKey	= $key;
		
		return true;
	}

	/**
	 * Returns SQL DELETE contents as a string.
	 *
	 * WARNING: $key = false will return a DELETE to delete all table
	 * information.
	 *
	 * @see where( $key = true )
	 * @param mixed boolean\string $key, 
	 *		ex: true (denotes use own primary key)
	 *			false (no where clause)
	 *			34 (denotes use 'WHERE primary_key = '34')
	 * @return mixed string/boolean
	 */
	function delete( $key = true )
	{
		$delete					= 'DELETE';

		$delete					.= $this->from();

		// if $key given, create WHERE clause
		$delete					.= $this->where( $key );

		return $delete;
	}

	/**
	 * Returns SQL FROM contents as a string.
	 *
	 * @return string
	 */
	function from( $include_from = true )
	{
		$from					= ( $include_from )
									? ' FROM '
									: '';

		$from					.= $this->fullTableName;

		$alias					= $this->getTableAlias();

		if ( !empty( $alias ) )
		{
			$from				.= ' ' . $alias;
		}

		return $from;
	}

	/**
	 * Returns SQL INSERT contents as a string.
	 *
	 * @return string
	 */
	function insert()
	{
		$insert					= 'INSERT IGNORE INTO ';
		$insert					.= $this->set();

		return $insert;
	}

	/**
	 * Returns SQL REPLACE contents as a string.
	 *
	 * @return string
	 */
	function replace()
	{
		$replace				= 'REPLACE INTO ';
		$replace				.= $this->set();

		return $replace;
	}

	/**
	 * Returns SQL SELECT contents as a string.
	 *
	 * @see where( $key = true )
	 * @param mixed boolean\string $key, 
	 *		ex: true (denotes use own primary key)
	 *			false (no where clause)
	 *			34 (denotes use 'WHERE primary_key = '34')
	 * @return mixed string/boolean
	 */
	function select( $key = true )
	{
		$select					= 'SELECT ';

		$alias					= $this->getTableAlias();

		$implode				= ', ';

		if ( !empty( $alias ) )
		{
			$alias				.= '.';
			$select				.= $alias;
			$implode			.= $alias;
		}

		$select					.= implode( $implode
									, array_keys($this->_CbDaoFields)
								);
		$select					.= $this->from();

		// if $key given, create WHERE clause
		$select					.= $this->where( $key );

		return $select;
	}

	/**
	 * Returns SQL SET contents as a string.
	 *
	 * @param boolean $full_set, ex: true (table_name SET x='1', ...), 
	 * 	false (x='1', ...)
	 * @return string
	 */
	function set( $full_set = true )
	{
		$fields					= array();
		$set					= '';

		$set					.= ( true === $full_set )
									? $this->fullTableName . ' SET '
									: '';
		
		// create csv string with member and value
		foreach ( $this->_CbDaoFields AS $field => $type )
		{
			$fieldMethod		= 'get' . ucfirst( $field );
			// is field value 'safe'?
			$fields[]			= "$field = "
									. $this->quoteValue($this->$fieldMethod());
		}

		$set					.= implode(', ', $fields);

		return $set;
	}

	/**
	 * Returns SQL UPDATE contents as a string.
	 *
	 * @see where( $key = true )
	 * @param mixed boolean\string $key, 
	 *		ex: true (denotes use own primary key)
	 *			false (no where clause)
	 *			34 (denotes use 'WHERE primary_key = '34')
	 * @return mixed string/boolean
	 */
	function update( $key = true )
	{
		$update					= 'UPDATE ';
		$update					.= $this->set();

		// if $key given, create WHERE clause
		$update					.= $this->where( $key );

		return $update;
	}

	/**
	 * Returns SQL WHERE clause contents as a string.
	 *
	 * @param mixed boolean\string\array $key, 
	 *		ex: true (denotes use own primary key)
	 *			false (no where clause)
	 *			34 (denotes use 'WHERE primary_key = '34')
	 * 		array(10, 3, 4) (denotes values to use with primary key fields)
	 * @return mixed string/boolean
	 */
	function where( $key = true )
	{
		$where					= '';

		// === and !== compares value and type equality
		// false denotes no where clause built
		if ( false !== $key )
		{
			$new_key			= ( !is_array( $key ) ) 
									? array( $key ) 
									: $key;

			// begin where clause
			$where				= ' WHERE 1 = 1';

			$primary_keys_size = sizeof($this->_CbDaoPrimaryKey);

			$alias				= $this->getTableAlias();

			if ( !empty( $alias ) )
			{
				$alias			.= '.';
			}

			// build conditionals
			for ( $i = 0; $i < $primary_keys_size; $i++ )
			{
				$where			.= "
					AND {$alias}{$this->_CbDaoPrimaryKey[$i]} = ";
				$member			= '_' . $this->_CbDaoPrimaryKey[$i];
				$value			= ( true === $key )
									? $this->$member
									: $new_key[$i];
				
				$where			.= $this->quoteValue( $value );
			}
		}
		
		return $where;
	}

	/**
	 * Returns custom SQL WHERE clause contents as a string.
	 *
	 * @param mixed array $key, 
	 *		ex: array('some_field' => 10, 'another_field' => 'pizza for all')
	 * @return string
	 */
	function where2( $key = false )
	{
		// begin where clause
		$where					= ' WHERE 1 = 1 ';

		$alias					= $this->getTableAlias();

		if ( !empty( $alias ) )
		{
			$alias				.= '.';
		}

		// false denotes no where clause built
		if ( $key )
		{
			if ( is_array( $key ) )
			{
				// build conditionals
				foreach ( $key AS $field => $value )
				{
					// try to ensure valid field
					if ( $this->isValidField( $field ) )
					{
						$where	.= " AND {$alias}{$field} = ";
						$where	.= $this->quoteValue( $value );
					}
				}
			}

			elseif ( is_string( $key ) )
			{
				// try to ensure valid field
				if ( $this->isValidField( $key ) )
				{
					$member		= '_' . $key;
					$where		.= " AND {$alias}{$key} = "
									. $this->quoteValue( $this->$member );
				}
			}
		}
		
		return $where;
	}

	/** 
	 * Return string of value encapsulated if not numeric
	 *
	 * @param mixed value
	 * @return string
	 */
	function quoteValue( $value )
	{
		$string					= ( !is_numeric( $value ) )
									? "'" . $value . "'"
									: $value;

		return $string;
	}

	/**
	 * Return boolean of valid field name
	 *
	 * @param string attribute name
	 * @return boolean
	 */
	function isValidField( $field )
	{
		// @ref
		// http://us3.php.net/manual/en/function.array-key-exists.php
		return array_key_exists( $field, $this->_CbDaoFields );
	}

	/**
	 * Member setter.
	 *
	 * is_array -- Finds whether a variable is an array
	 * is_bool --  Finds out whether a variable is a boolean
	 * is_double -- Alias of is_float()
	 * is_float -- Finds whether a variable is a float
	 * is_int -- Find whether a variable is an integer
	 * is_integer -- Alias of is_int()
	 * is_long -- Alias of is_int()
	 * is_null --  Finds whether a variable is NULL
	 * is_numeric --  Finds whether a variable is a number or a numeric string
	 * is_object -- Finds whether a variable is an object
	 * is_real -- Alias of is_float()
	 * is_resource --  Finds whether a variable is a resource
	 * is_scalar --  Finds whether a variable is a scalar
	 * is_string -- Finds whether a variable is a string
	 *
	 * @param string attribute name
	 * @param mixed integer/float value
	 * @param mixed parameter one supposed type
	 * @return boolean success
	 */
	function setMember ( $key, $value, $type = null )
	{
		// check for key as class member
		if ( $this->isValidField( $key ) )
		{
			// quick allow of nulls
			if ( is_null( $value ) )
			{
				$member			= '_' . $key;
				$this->$member 	= $value;

				return true;
			}

			$type				= $this->_CbDaoFields[$key];

			$whole_number_types	= array(
				'int', 
				'integer', 
				'long',
				'tinyint',
				'smallint',
				'mediumint',
				'bigint'
			);

			$real_number_types	= array(
				'double', 
				'float', 
				'numeric', 
				'real',
				'number',
				'double precision'
			);

			$date_types			= array(
				'date',
				'datetime',
				'timestamp',
				'time',
				'year'
			);

			$text_types			= array(
				'char',
				'varchar',
				'varchar2',
				'tinyblob',
				'tinytext',
				'blob',
				'clob',
				'text',
				'mediumblob',
				'mediumtext',
				'longblob',
				'longtext'
			);

			$set_types			= array(
				'enum',
				'set'
			);

			switch ( $type )
			{
				case in_array($type, $whole_number_types):
					$value		+= 0;
					$type		= 'int';
					break;

				case in_array($type, $real_number_types):
					$value		+= 0;
					$type		= 'numeric';
					break;

				case ( in_array($type, $date_types) ):
				case ( in_array($type, $text_types) ):
				case ( in_array($type, $set_types) ):
				default:
					$type		= 'string';
					$value		= mysql_escape_string( $value );
					break;
			}

			// create is_bool type checker
			$function			= 'is_' . $type;

			if ( is_callable( $function ) && $function( $value ) )
			{
				$member			= '_' . $key;
				$this->$member 	= $value;

				return true;
			}

			else
			{
				$this->setDbError(
					"\nkey: $key, value: $value, not of type: $type.\n" );
			}
		}

		else
		{
			$this->setDbError( "\nkey: $key, not a class variable.\n" );
		}

		return false;
	}

	/**
	 * Load given array or object into members.
	 *
	 * @param mixed $input, preset array/object
	 * @return boolean success
	 */
	function loadMembers( $input = null )
	{
		$success				= array();

		// convert $input contents to variables
		// extract would work on array, but not object hence the loop
		if ( !is_null( $input ) && is_array( $input ) || is_object( $input ) )
		{
			foreach ($input AS $key => $value)
			{
				// check for valid key, then use that keys type
				$success[]		= ( $this->isValidField( $key ) )
									? $this->setMember($key, $value)
									: false;
			}
		}

		return ( !in_array(false, $success) )
			? true
			: false;
	}

	/**
	 * getMember
	 *
	 * @param string class member name
	 * @return mixed member value, null on invalid key
	 */
	function & getMember( $key )
	{
		if ( $this->isValidField( $key ) )
		{
			// convert to private member
			$member				= '_' . $key;
			$value				= $this->$member;

			if ( !is_null( $value ) )
			{
				$value			= stripslashes( $value );
			}

			return $value;
		}

		return null;
	}

	/**
	 * Alias for error()
	 *
	 * @return string
	 */
	function & error()
	{
		return $this->getError();
	}

	/**
	 * Return string of CbDao error message
	 *
	 * @return string
	 */
	function & getError()
	{
		return $this->_error;
	}

	/**
	 * Set error.
	 *
	 * @param string error message
	 * @return void
	 */
	function setError( & $value )
	{
		$this->_error			= $value;
	}

	/**
	 * Get dbError
	 *
	 * @return string
	 */
	function & getDbError()
	{
		return $this->_dbError();
	}

	/**
	 * Set error.
	 *
	 * @param string error message
	 * @return void
	 */
	function setDbError( & $value )
	{
		$this->_dbError			= $value;
	}

	/**
	 * Allows database connection to set after class instantiation.
	 *
	 * @param resource Addob conneciton
	 * @return boolean
	 */
	function setDb( & $db )
	{
		// try for ensuring $db is an AdoDb class of some form
		if ( !is_null( $db ) && preg_match('/^ADODB_/i', get_class( $db ) ) )
		{
			$this->_db			= $db;
			return true;
		}
			
		return false;
	}

	/**
	 * Returns database connection.
	 *
	 * @return resource Addob conneciton
	 */
	function & getDb()
	{
		return $this->_db;
	}

	/**
	 * Releases database connection.
	 *
	 * @return resource Addob conneciton
	 */
	function releaseDb()
	{
		$this->_db				= null;
	}

	/**
	 * Returns the last autonumbering ID inserted. Returns false if function not
	 * supported.
	 *
	 * @return mixed integer, false on failure
	 */
	function lastInsertId()
	{
		return $this->_db->Insert_ID();
	}

	/**
	 * Execute UPDATE.
	 *
	 * @return boolean
	 */
	function updateDo()
	{
		$sql					= $this->update();

		// create new entry
		if ( false === $this->_db->Execute( $sql ) ) 
		{
			cbLog( __FILE__ . ':' . __LINE__ . ':'.$this->_db->ErrorMsg() );
			return false;
		}
			
		return true;
	}

	/**
	 * Execute INSERT.
	 *
	 * @return boolean
	 */
	function insertDo()
	{
		$sql					= $this->insert();

		// create new entry
		if ( false === $this->_db->Execute( $sql ) ) 
		{
			cbLog( __FILE__ . ':' . __LINE__ . ':'.$this->_db->ErrorMsg() );
			return false;
		}
			
		return true;
	}

	/**
	 * Adodb Execute wrapper
	 *
	 * @param string SQL statement to execute, or possibly an array holding
	 * 	prepared statement ($sql[0] will hold sql text)
	 * @param array Holds the input data to bind to. Null elements will be set
	 * 	to null.
	 * @return mixed RecordSet or false
	 */
	function & Execute($sql, $inputarr = false) 
	{
		return $this->_db->Execute($sql, $inputarr);
	}

	/**
	 * Adodb GetRow wrapper
	 *
	 * Loads results into self
	 *
	 * @param string SQL statement to execute, or possibly an array holding
	 * 	prepared statement ($sql[0] will hold sql text)
	 * @return boolean successful load or not
	 */
	function GetRow( $sql ) 
	{
		$result					=  $this->_db->GetRow( $sql );

		if ( $result )
		{
			foreach ( $result AS $key => $value )
			{
				if ( !is_numeric( $key ) )
				{
					$this->setMember($key, $value);
				}
			}

			return true;
		}

		else
		{
			return false;
		}
	}

	/**
	 * Loads first results of a self select into self.
	 *
	 * @return boolean successful load or not
	 */
	function loadSelf() 
	{
		$sql					=  $this->select();

		return $this->GetRow( $sql );
	}

	/**
	 * Adodb GetAll wrapper
	 *
	 * @param string SQL statement to execute, or possibly an array holding
	 * 	prepared statement ($sql[0] will hold sql text)
	 * @return mixed RecordSet or false
	 */
	function & GetAll( $sql ) 
	{
		return $this->_db->GetAll( $sql );
	}

	/**
	 * Get tableAlias.
	 *
	 * @return string
	 */
	function getTableAlias ()
	{
		return $this->_tableAlias;
	}

	/**
	 * Set tableAlias.
	 *
	 * @param string table alias, table abbreviation
	 * @return boolean true success
	 */
	function setTableAlias ( $alias = null )
	{
		// ensure word format
		if ( preg_match( '#[a-zA-Z]\w*#', $alias ) )
		{
			$this->_tableAlias	= $alias;

			return true;
		}

		else
		{
			$this->_tableAlias	= '';

			return false;
		}
	}
}

/* vim modeline, http://vim.org */
/* vim:set tabstop=4 shiftwidth=4 textwidth=80: */
?>
