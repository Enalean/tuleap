<?php 
/*
 * Set tabs to 4 for best viewing.
 * 
 * Latest version is available at http://php.weblogs.com/adodb
 * 
 * This is the main include file for ADOdb.
 * Database specific drivers are stored in the adodb/drivers/adodb-*.inc.php
 *
 * The ADOdb files are formatted so that doxygen can be used to generate documentation.
 * Doxygen is a documentation generation tool and can be downloaded from http://doxygen.org/
 */

/**
	\mainpage 	
	
	 @version V4.22 15 Apr 2004 (c) 2000-2004 John Lim (jlim\@natsoft.com.my). All rights reserved.

	Released under both BSD license and Lesser GPL library license. You can choose which license
	you prefer.
	
	PHP's database access functions are not standardised. This creates a need for a database 
	class library to hide the differences between the different database API's (encapsulate 
	the differences) so we can easily switch databases.

	We currently support MySQL, Oracle, Microsoft SQL Server, Sybase, Sybase SQL Anywhere, DB2,
	Informix, PostgreSQL, FrontBase, Interbase (Firebird and Borland variants), Foxpro, Access,
	ADO, SAP DB, SQLite and ODBC. We have had successful reports of connecting to Progress and
	other databases via ODBC. 
	 
	Latest Download at http://php.weblogs.com/adodb<br>
	Manual is at http://php.weblogs.com/adodb_manual
	  
 */
 
 if (!defined('_ADODB_LAYER')) {
 	define('_ADODB_LAYER',1);
	
	//==========================================================================
	// CONSTANT DEFINITIONS
	//==========================================================================

	/** 
	 * Set ADODB_DIR to the directory where this file resides...
	 * This constant was formerly called $ADODB_RootPath
	 */
	if (!defined('ADODB_DIR')) define('ADODB_DIR',dirname(__FILE__));
	
	//==========================================================================
	// GLOBAL VARIABLES
	//==========================================================================

	GLOBAL 
		$ADODB_vers, 		// database version
		$ADODB_COUNTRECS,	// count number of records returned - slows down query
		$ADODB_CACHE_DIR,	// directory to cache recordsets
		$ADODB_EXTENSION,   // ADODB extension installed
		$ADODB_COMPAT_PATCH, // If $ADODB_COUNTRECS and this is true, $rs->fields is available on EOF
	 	$ADODB_FETCH_MODE;	// DEFAULT, NUM, ASSOC or BOTH. Default follows native driver default...
	
	//==========================================================================
	// GLOBAL SETUP
	//==========================================================================
	
	$ADODB_EXTENSION = defined('ADODB_EXTENSION');
	if (!$ADODB_EXTENSION || ADODB_EXTENSION < 4.0) {
		
		define('ADODB_BAD_RS','<p>Bad $rs in %s. Connection or SQL invalid. Try using $connection->debug=true;</p>');
	
	// allow [ ] @ ` " and . in table names
		define('ADODB_TABLE_REGEX','([]0-9a-z_\"\`\.\@\[-]*)');
	
	// prefetching used by oracle
		if (!defined('ADODB_PREFETCH_ROWS')) define('ADODB_PREFETCH_ROWS',10);
	
	
	/*
	Controls ADODB_FETCH_ASSOC field-name case. Default is 2, use native case-names.
	This currently works only with mssql, odbc, oci8po and ibase derived drivers.
	
 		0 = assoc lowercase field names. $rs->fields['orderid']
		1 = assoc uppercase field names. $rs->fields['ORDERID']
		2 = use native-case field names. $rs->fields['OrderID']
	*/
	
		define('ADODB_FETCH_DEFAULT',0);
		define('ADODB_FETCH_NUM',1);
		define('ADODB_FETCH_ASSOC',2);
		define('ADODB_FETCH_BOTH',3);
		
		if (!defined('TIMESTAMP_FIRST_YEAR')) define('TIMESTAMP_FIRST_YEAR',100);
	
		if (strnatcmp(PHP_VERSION,'4.3.0')>=0) {
			define('ADODB_PHPVER',0x4300);
		} else if (strnatcmp(PHP_VERSION,'4.2.0')>=0) {
			define('ADODB_PHPVER',0x4200);
		} else if (strnatcmp(PHP_VERSION,'4.0.5')>=0) {
			define('ADODB_PHPVER',0x4050);
		} else {
			define('ADODB_PHPVER',0x4000);
		}
	}
	
	//if (!defined('ADODB_ASSOC_CASE')) define('ADODB_ASSOC_CASE',2);
	
	
	/**
	 	Accepts $src and $dest arrays, replacing string $data
	*/
	function ADODB_str_replace($src, $dest, $data)
	{
		if (ADODB_PHPVER >= 0x4050) return str_replace($src,$dest,$data);
		
		$s = reset($src);
		$d = reset($dest);
		while ($s !== false) {
			$data = str_replace($s,$d,$data);
			$s = next($src);
			$d = next($dest);
		}
		return $data;
	}
	
	function ADODB_Setup()
	{
	GLOBAL 
		$ADODB_vers, 		// database version
		$ADODB_COUNTRECS,	// count number of records returned - slows down query
		$ADODB_CACHE_DIR,	// directory to cache recordsets
	 	$ADODB_FETCH_MODE;
		
		$ADODB_FETCH_MODE = ADODB_FETCH_DEFAULT;
		
		if (!isset($ADODB_CACHE_DIR)) {
			$ADODB_CACHE_DIR = '/tmp'; //(isset($_ENV['TMP'])) ? $_ENV['TMP'] : '/tmp';
		} else {
			// do not accept url based paths, eg. http:/ or ftp:/
			if (strpos($ADODB_CACHE_DIR,'://') !== false) 
				die("Illegal path http:// or ftp://");
		}
		
			
		// Initialize random number generator for randomizing cache flushes
		srand(((double)microtime())*1000000);
		
		/**
		 * ADODB version as a string.
		 */
		$ADODB_vers = 'V4.22 15 Apr 2004 (c) 2000-2004 John Lim (jlim#natsoft.com.my). All rights reserved. Released BSD & LGPL.';
	
		/**
		 * Determines whether recordset->RecordCount() is used. 
		 * Set to false for highest performance -- RecordCount() will always return -1 then
		 * for databases that provide "virtual" recordcounts...
		 */
		if (!isset($ADODB_COUNTRECS)) $ADODB_COUNTRECS = true; 
	}
	
	
	//==========================================================================
	// CHANGE NOTHING BELOW UNLESS YOU ARE DESIGNING ADODB
	//==========================================================================
	
	ADODB_Setup();

	//==========================================================================
	// CLASS ADOFieldObject
	//==========================================================================
	/**
	 * Helper class for FetchFields -- holds info on a column
	 */
	class ADOFieldObject { 
		var $name = '';
		var $max_length=0;
		var $type="";

		// additional fields by dannym... (danny_milo@yahoo.com)
		var $not_null = false; 
		// actually, this has already been built-in in the postgres, fbsql AND mysql module? ^-^
		// so we can as well make not_null standard (leaving it at "false" does not harm anyways)

		var $has_default = false; // this one I have done only in mysql and postgres for now ... 
			// others to come (dannym)
		var $default_value; // default, if any, and supported. Check has_default first.
	}
	

	
	function ADODB_TransMonitor($dbms, $fn, $errno, $errmsg, $p1, $p2, &$thisConnection)
	{
		//print "Errorno ($fn errno=$errno m=$errmsg) ";
		$thisConnection->_transOK = false;
		if ($thisConnection->_oldRaiseFn) {
			$fn = $thisConnection->_oldRaiseFn;
			$fn($dbms, $fn, $errno, $errmsg, $p1, $p2,$thisConnection);
		}
	}
	
	//==========================================================================
	// CLASS ADOConnection
	//==========================================================================
	
	/**
	 * Connection object. For connecting to databases, and executing queries.
	 */ 
	class ADOConnection {
	//
	// PUBLIC VARS 
	//
	var $dataProvider = 'native';
	var $databaseType = '';		/// RDBMS currently in use, eg. odbc, mysql, mssql					
	var $database = '';			/// Name of database to be used.	
	var $host = ''; 			/// The hostname of the database server	
	var $user = ''; 			/// The username which is used to connect to the database server. 
	var $password = ''; 		/// Password for the username. For security, we no longer store it.
	var $debug = false; 		/// if set to true will output sql statements
	var $maxblobsize = 256000; 	/// maximum size of blobs or large text fields -- some databases die otherwise like foxpro
	var $concat_operator = '+'; /// default concat operator -- change to || for Oracle/Interbase	
	var $substr = 'substr';		/// substring operator
	var $length = 'length';		/// string length operator
	var $random = 'rand()';		/// random function
	var $upperCase = false;		/// uppercase function
	var $fmtDate = "'Y-m-d'";	/// used by DBDate() as the default date format used by the database
	var $fmtTimeStamp = "'Y-m-d, h:i:s A'"; /// used by DBTimeStamp as the default timestamp fmt.
	var $true = '1'; 			/// string that represents TRUE for a database
	var $false = '0'; 			/// string that represents FALSE for a database
	var $replaceQuote = "\\'"; 	/// string to use to replace quotes
	var $nameQuote = '"';		/// string to use to quote identifiers and names
	var $charSet=false; 		/// character set to use - only for interbase
	var $metaDatabasesSQL = '';
	var $metaTablesSQL = '';
	var $uniqueOrderBy = false; /// All order by columns have to be unique
	var $emptyDate = '&nbsp;';
	var $emptyTimeStamp = '&nbsp;';
	var $lastInsID = false;
	//--
	var $hasInsertID = false; 		/// supports autoincrement ID?
	var $hasAffectedRows = false; 	/// supports affected rows for update/delete?
	var $hasTop = false;			/// support mssql/access SELECT TOP 10 * FROM TABLE
	var $hasLimit = false;			/// support pgsql/mysql SELECT * FROM TABLE LIMIT 10
	var $readOnly = false; 			/// this is a readonly database - used by phpLens
	var $hasMoveFirst = false;  /// has ability to run MoveFirst(), scrolling backwards
	var $hasGenID = false; 		/// can generate sequences using GenID();
	var $hasTransactions = true; /// has transactions
	//--
	var $genID = 0; 			/// sequence id used by GenID();
	var $raiseErrorFn = false; 	/// error function to call
	var $isoDates = false; /// accepts dates in ISO format
	var $cacheSecs = 3600; /// cache for 1 hour
	var $sysDate = false; /// name of function that returns the current date
	var $sysTimeStamp = false; /// name of function that returns the current timestamp
	var $arrayClass = 'ADORecordSet_array'; /// name of class used to generate array recordsets, which are pre-downloaded recordsets
	
	var $noNullStrings = false; /// oracle specific stuff - if true ensures that '' is converted to ' '
	var $numCacheHits = 0; 
	var $numCacheMisses = 0;
	var $pageExecuteCountRows = true;
	var $uniqueSort = false; /// indicates that all fields in order by must be unique
	var $leftOuter = false; /// operator to use for left outer join in WHERE clause
	var $rightOuter = false; /// operator to use for right outer join in WHERE clause
	var $ansiOuter = false; /// whether ansi outer join syntax supported
	var $autoRollback = false; // autoRollback on PConnect().
	var $poorAffectedRows = false; // affectedRows not working or unreliable
	
	var $fnExecute = false;
	var $fnCacheExecute = false;
	var $blobEncodeType = false; // false=not required, 'I'=encode to integer, 'C'=encode to char
	var $rsPrefix = "ADORecordSet_";
	
	var $autoCommit = true; 	/// do not modify this yourself - actually private
	var $transOff = 0; 			/// temporarily disable transactions
	var $transCnt = 0; 			/// count of nested transactions
	
	var $fetchMode=false;
	 //
	 // PRIVATE VARS
	 //
	var $_oldRaiseFn =  false;
	var $_transOK = null;
	var $_connectionID	= false;	/// The returned link identifier whenever a successful database connection is made.	
	var $_errorMsg = false;		/// A variable which was used to keep the returned last error message.  The value will
								/// then returned by the errorMsg() function	
	var $_errorCode = false;	/// Last error code, not guaranteed to be used - only by oci8					
	var $_queryID = false;		/// This variable keeps the last created result link identifier
	
	var $_isPersistentConnection = false;	/// A boolean variable to state whether its a persistent connection or normal connection.	*/
	var $_bindInputArray = false; /// set to true if ADOConnection.Execute() permits binding of array parameters.
	var $_evalAll = false;
	var $_affected = false;
	var $_logsql = false;
	

	
	/**
	 * Constructor
	 */
	function ADOConnection()			
	{
		die('Virtual Class -- cannot instantiate');
	}
	
	/**
		Get server version info...
		
		@returns An array with 2 elements: $arr['string'] is the description string, 
			and $arr[version] is the version (also a string).
	*/
	function ServerInfo()
	{
		return array('description' => '', 'version' => '');
	}
	
	function _findvers($str)
	{
		if (preg_match('/([0-9]+\.([0-9\.])+)/',$str, $arr)) return $arr[1];
		else return '';
	}
	
	/**
	* All error messages go through this bottleneck function.
	* You can define your own handler by defining the function name in ADODB_OUTP.
	*/
	function outp($msg,$newline=true)
	{
	global $HTTP_SERVER_VARS,$ADODB_FLUSH,$ADODB_OUTP;
	
		if (defined('ADODB_OUTP')) {
			$fn = ADODB_OUTP;
			$fn($msg,$newline);
			return;
		} else if (isset($ADODB_OUTP)) {
			$fn = $ADODB_OUTP;
			$fn($msg,$newline);
			return;
		}
		
		if ($newline) $msg .= "<br>\n";
		
		if (isset($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) echo $msg;
		else echo strip_tags($msg);
		if (!empty($ADODB_FLUSH) && ob_get_length() !== false) flush(); //  dp not flush if output buffering enabled - useless - thx to Jesse Mullan 
		
	}
	
	function Time()
	{
		$rs =& $this->Execute("select $this->sysTimeStamp");
		if ($rs && !$rs->EOF) return $this->UnixTimeStamp(reset($rs->fields));
		
		return false;
	}
	
	/**
	 * Connect to database
	 *
	 * @param [argHostname]		Host to connect to
	 * @param [argUsername]		Userid to login
	 * @param [argPassword]		Associated password
	 * @param [argDatabaseName]	database
	 * @param [forceNew]		force new connection
	 *
	 * @return true or false
	 */	  
	function Connect($argHostname = "", $argUsername = "", $argPassword = "", $argDatabaseName = "", $forceNew = false) 
	{
		if ($argHostname != "") $this->host = $argHostname;
		if ($argUsername != "") $this->user = $argUsername;
		if ($argPassword != "") $this->password = $argPassword; // not stored for security reasons
		if ($argDatabaseName != "") $this->database = $argDatabaseName;		
		
		$this->_isPersistentConnection = false;	
		if ($fn = $this->raiseErrorFn) {
			if ($forceNew) {
				if ($this->_nconnect($this->host, $this->user, $this->password, $this->database)) return true;
			} else {
				 if ($this->_connect($this->host, $this->user, $this->password, $this->database)) return true;
			}
			$err = $this->ErrorMsg();
			if (empty($err)) $err = "Connection error to server '$argHostname' with user '$argUsername'";
			$fn($this->databaseType,'CONNECT',$this->ErrorNo(),$err,$this->host,$this->database,$this);
		} else {
			if ($forceNew) {
				if ($this->_nconnect($this->host, $this->user, $this->password, $this->database)) return true;
			} else {
				if ($this->_connect($this->host, $this->user, $this->password, $this->database)) return true;
			}
		}
		if ($this->debug) ADOConnection::outp( $this->host.': '.$this->ErrorMsg());
		return false;
	}	
	
	 function _nconnect($argHostname, $argUsername, $argPassword, $argDatabaseName)
	 {
	 	return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabaseName);
	 }
	
	
	/**
	 * Always force a new connection to database - currently only works with oracle
	 *
	 * @param [argHostname]		Host to connect to
	 * @param [argUsername]		Userid to login
	 * @param [argPassword]		Associated password
	 * @param [argDatabaseName]	database
	 *
	 * @return true or false
	 */	  
	function NConnect($argHostname = "", $argUsername = "", $argPassword = "", $argDatabaseName = "") 
	{
		return $this->Connect($argHostname, $argUsername, $argPassword, $argDatabaseName, true);
	}
	
	/**
	 * Establish persistent connect to database
	 *
	 * @param [argHostname]		Host to connect to
	 * @param [argUsername]		Userid to login
	 * @param [argPassword]		Associated password
	 * @param [argDatabaseName]	database
	 *
	 * @return return true or false
	 */	
	function PConnect($argHostname = "", $argUsername = "", $argPassword = "", $argDatabaseName = "")
	{
		if (defined('ADODB_NEVER_PERSIST')) 
			return $this->Connect($argHostname,$argUsername,$argPassword,$argDatabaseName);
		
		if ($argHostname != "") $this->host = $argHostname;
		if ($argUsername != "") $this->user = $argUsername;
		if ($argPassword != "") $this->password = $argPassword;
		if ($argDatabaseName != "") $this->database = $argDatabaseName;		
			
		$this->_isPersistentConnection = true;	
		
		if ($fn = $this->raiseErrorFn) {
			if ($this->_pconnect($this->host, $this->user, $this->password, $this->database)) return true;
			$err = $this->ErrorMsg();
			if (empty($err)) $err = "Connection error to server '$argHostname' with user '$argUsername'";
			$fn($this->databaseType,'PCONNECT',$this->ErrorNo(),$err,$this->host,$this->database,$this);
		} else 
			if ($this->_pconnect($this->host, $this->user, $this->password, $this->database)) return true;

		if ($this->debug) ADOConnection::outp( $this->host.': '.$this->ErrorMsg());
		return false;
	}

	// Format date column in sql string given an input format that understands Y M D
	function SQLDate($fmt, $col=false)
	{	
		if (!$col) $col = $this->sysDate;
		return $col; // child class implement
	}
	
	/**
	 * Should prepare the sql statement and return the stmt resource.
	 * For databases that do not support this, we return the $sql. To ensure
	 * compatibility with databases that do not support prepare:
	 *
	 *   $stmt = $db->Prepare("insert into table (id, name) values (?,?)");
	 *   $db->Execute($stmt,array(1,'Jill')) or die('insert failed');
	 *   $db->Execute($stmt,array(2,'Joe')) or die('insert failed');
	 *
	 * @param sql	SQL to send to database
	 *
	 * @return return FALSE, or the prepared statement, or the original sql if
	 * 			if the database does not support prepare.
	 *
	 */	
	function Prepare($sql)
	{
		return $sql;
	}

	/**
	 * Some databases, eg. mssql require a different function for preparing
	 * stored procedures. So we cannot use Prepare().
	 *
	 * Should prepare the stored procedure  and return the stmt resource.
	 * For databases that do not support this, we return the $sql. To ensure
	 * compatibility with databases that do not support prepare:
	 *
	 * @param sql	SQL to send to database
	 *
	 * @return return FALSE, or the prepared statement, or the original sql if
	 * 			if the database does not support prepare.
	 *
	 */	
	function PrepareSP($sql,$param=true)
	{
		return $this->Prepare($sql,$param);
	}
	
	/**
	* PEAR DB Compat
	*/
	function Quote($s)
	{
		return $this->qstr($s,false);
	}
	
	/**
	 Requested by "Karsten Dambekalns" <k.dambekalns@fishfarm.de>
	*/
	function QMagic($s)
	{
		return $this->qstr($s,get_magic_quotes_gpc());
	}

	function q(&$s)
	{
		$s = $this->qstr($s,false);
	}
	
	/**
	* PEAR DB Compat - do not use internally. 
	*/
	function ErrorNative()
	{
		return $this->ErrorNo();
	}

	
   /**
	* PEAR DB Compat - do not use internally. 
	*/
	function nextId($seq_name)
	{
		return $this->GenID($seq_name);
	}

	/**
	*	 Lock a row, will escalate and lock the table if row locking not supported
	*	will normally free the lock at the end of the transaction
	*
	*  @param $table	name of table to lock
	*  @param $where	where clause to use, eg: "WHERE row=12". If left empty, will escalate to table lock
	*/
	function RowLock($table,$where)
	{
		return false;
	}
	
	function CommitLock($table)
	{
		return $this->CommitTrans();
	}
	
	function RollbackLock($table)
	{
		return $this->RollbackTrans();
	}
	
	/**
	* PEAR DB Compat - do not use internally. 
	*
	* The fetch modes for NUMERIC and ASSOC for PEAR DB and ADODB are identical
	* 	for easy porting :-)
	*
	* @param mode	The fetchmode ADODB_FETCH_ASSOC or ADODB_FETCH_NUM
	* @returns		The previous fetch mode
	*/
	function SetFetchMode($mode)
	{	
		$old = $this->fetchMode;
		$this->fetchMode = $mode;
		
		if ($old === false) {
		global $ADODB_FETCH_MODE;
			return $ADODB_FETCH_MODE;
		}
		return $old;
	}
	

	/**
	* PEAR DB Compat - do not use internally. 
	*/
	function &Query($sql, $inputarr=false)
	{
		$rs = &$this->Execute($sql, $inputarr);
		if (!$rs && defined('ADODB_PEAR')) return ADODB_PEAR_Error();
		return $rs;
	}

	
	/**
	* PEAR DB Compat - do not use internally
	*/
	function &LimitQuery($sql, $offset, $count, $params=false)
	{
		$rs = &$this->SelectLimit($sql, $count, $offset, $params); 
		if (!$rs && defined('ADODB_PEAR')) return ADODB_PEAR_Error();
		return $rs;
	}

	
	/**
	* PEAR DB Compat - do not use internally
	*/
	function Disconnect()
	{
		return $this->Close();
	}
	
	/*
		 Returns placeholder for parameter, eg.
		 $DB->Param('a')
		 
		 will return ':a' for Oracle, and '?' for most other databases...
		 
		 For databases that require positioned params, eg $1, $2, $3 for postgresql,
		 	pass in Param(false) before setting the first parameter.
	*/
	function Param($name)
	{
		return '?';
	}
	
	/*
		InParameter and OutParameter are self-documenting versions of Parameter().
	*/
	function InParameter(&$stmt,&$var,$name,$maxLen=4000,$type=false)
	{
		return $this->Parameter($stmt,$var,$name,false,$maxLen,$type);
	}
	
	/*
	*/
	function OutParameter(&$stmt,&$var,$name,$maxLen=4000,$type=false)
	{
		return $this->Parameter($stmt,$var,$name,true,$maxLen,$type);
	
	}
	
	/* 
	Usage in oracle
		$stmt = $db->Prepare('select * from table where id =:myid and group=:group');
		$db->Parameter($stmt,$id,'myid');
		$db->Parameter($stmt,$group,'group',64);
		$db->Execute();
		
		@param $stmt Statement returned by Prepare() or PrepareSP().
		@param $var PHP variable to bind to
		@param $name Name of stored procedure variable name to bind to.
		@param [$isOutput] Indicates direction of parameter 0/false=IN  1=OUT  2= IN/OUT. This is ignored in oci8.
		@param [$maxLen] Holds an maximum length of the variable.
		@param [$type] The data type of $var. Legal values depend on driver.

	*/
	function Parameter(&$stmt,&$var,$name,$isOutput=false,$maxLen=4000,$type=false)
	{
		return false;
	}
	
	/**
		Improved method of initiating a transaction. Used together with CompleteTrans().
		Advantages include:
		
		a. StartTrans/CompleteTrans is nestable, unlike BeginTrans/CommitTrans/RollbackTrans.
		   Only the outermost block is treated as a transaction.<br>
		b. CompleteTrans auto-detects SQL errors, and will rollback on errors, commit otherwise.<br>
		c. All BeginTrans/CommitTrans/RollbackTrans inside a StartTrans/CompleteTrans block
		   are disabled, making it backward compatible.
	*/
	function StartTrans($errfn = 'ADODB_TransMonitor')
	{
		if ($this->transOff > 0) {
			$this->transOff += 1;
			return;
		}
		
		$this->_oldRaiseFn = $this->raiseErrorFn;
		$this->raiseErrorFn = $errfn;
		$this->_transOK = true;
		
		if ($this->debug && $this->transCnt > 0) ADOConnection::outp("Bad Transaction: StartTrans called within BeginTrans");
		$this->BeginTrans();
		$this->transOff = 1;
	}
	
	/**
		Used together with StartTrans() to end a transaction. Monitors connection
		for sql errors, and will commit or rollback as appropriate.
		
		@autoComplete if true, monitor sql errors and commit and rollback as appropriate, 
		and if set to false force rollback even if no SQL error detected.
		@returns true on commit, false on rollback.
	*/
	function CompleteTrans($autoComplete = true)
	{
		if ($this->transOff > 1) {
			$this->transOff -= 1;
			return true;
		}
		$this->raiseErrorFn = $this->_oldRaiseFn;
		
		$this->transOff = 0;
		if ($this->_transOK && $autoComplete) {
			if (!$this->CommitTrans()) {
				$this->_transOK = false;
				if ($this->debug) ADOConnection::outp("Smart Commit failed");
			} else
				if ($this->debug) ADOConnection::outp("Smart Commit occurred");
		} else {
			$this->RollbackTrans();
			if ($this->debug) ADOCOnnection::outp("Smart Rollback occurred");
		}
		
		return $this->_transOK;
	}
	
	/*
		At the end of a StartTrans/CompleteTrans block, perform a rollback.
	*/
	function FailTrans()
	{
		if ($this->debug) 
			if ($this->transOff == 0) {
				ADOConnection::outp("FailTrans outside StartTrans/CompleteTrans");
			} else {
				ADOConnection::outp("FailTrans was called");
				adodb_backtrace();
			}
		$this->_transOK = false;
	}
	
	/**
		Check if transaction has failed, only for Smart Transactions.
	*/
	function HasFailedTrans()
	{
		if ($this->transOff > 0) return $this->_transOK == false;
		return false;
	}
	
	/**
	 * Execute SQL 
	 *
	 * @param sql		SQL statement to execute, or possibly an array holding prepared statement ($sql[0] will hold sql text)
	 * @param [inputarr]	holds the input data to bind to. Null elements will be set to null.
	 * @return 		RecordSet or false
	 */
	function &Execute($sql,$inputarr=false) 
	{
		if ($this->fnExecute) {
			$fn = $this->fnExecute;
			$ret =& $fn($this,$sql,$inputarr);
			if (isset($ret)) return $ret;
		}
		if ($inputarr && is_array($inputarr)) {
			$element0 = reset($inputarr);
			# is_object check is because oci8 descriptors can be passed in
			$array_2d = is_array($element0) && !is_object(reset($element0));
			
			if (!is_array($sql) && !$this->_bindInputArray) {
				$sqlarr = explode('?',$sql);
					
				if (!$array_2d) $inputarr = array($inputarr);
				foreach($inputarr as $arr) {
					$sql = ''; $i = 0;
					foreach($arr as $v) {
						$sql .= $sqlarr[$i];
						// from Ron Baldwin <ron.baldwin@sourceprose.com>
						// Only quote string types	
						if (gettype($v) == 'string')
							$sql .= $this->qstr($v);
						else if ($v === null)
							$sql .= 'NULL';
						else
							$sql .= $v;
						$i += 1;
					}
					$sql .= $sqlarr[$i];
					
					if ($i+1 != sizeof($sqlarr))	
						ADOConnection::outp( "Input Array does not match ?: ".htmlspecialchars($sql));
		
					$ret =& $this->_Execute($sql,false);
					if (!$ret) return $ret;
				}	
			} else {
				if ($array_2d) {
					$stmt = $this->Prepare($sql);
					foreach($inputarr as $arr) {
						$ret =& $this->_Execute($stmt,$arr);
						if (!$ret) return $ret;
					}
				} else
					$ret =& $this->_Execute($sql,$inputarr);
			}
		} else {
			$ret =& $this->_Execute($sql,false);
		}

		return $ret;
	}
	
	function& _Execute($sql,$inputarr=false)
	{

		if ($this->debug) {
		global $HTTP_SERVER_VARS;
		
			$ss = '';
			if ($inputarr) {
				foreach($inputarr as $kk=>$vv) {
					if (is_string($vv) && strlen($vv)>64) $vv = substr($vv,0,64).'...';
					$ss .= "($kk=>'$vv') ";
				}
				$ss = "[ $ss ]";
			}
			$sqlTxt = str_replace(',',', ',is_array($sql) ?$sql[0] : $sql);
			
			// check if running from browser or command-line
			$inBrowser = isset($HTTP_SERVER_VARS['HTTP_USER_AGENT']);
			
			if ($inBrowser) {
				if ($this->debug === -1)
					ADOConnection::outp( "<br>\n($this->databaseType): ".htmlspecialchars($sqlTxt)." &nbsp; <code>$ss</code>\n<br>\n",false);
				else 
					ADOConnection::outp( "<hr>\n($this->databaseType): ".htmlspecialchars($sqlTxt)." &nbsp; <code>$ss</code>\n<hr>\n",false);
			} else {
				ADOConnection::outp("-----\n($this->databaseType): ".($sqlTxt)." \n-----\n",false);
			}
			$this->_queryID = $this->_query($sql,$inputarr);
			/* 
				Alexios Fakios notes that ErrorMsg() must be called before ErrorNo() for mssql
				because ErrorNo() calls Execute('SELECT @ERROR'), causing recursion
			*/
			if ($this->databaseType == 'mssql') { 
			// ErrorNo is a slow function call in mssql, and not reliable in PHP 4.0.6
				if($emsg = $this->ErrorMsg()) {
					if ($err = $this->ErrorNo()) ADOConnection::outp($err.': '.$emsg);
				}
			} else if (!$this->_queryID) {
				ADOConnection::outp($this->ErrorNo() .': '. $this->ErrorMsg());
			}	
		} else {
			//****************************
			// non-debug version of query
			//****************************
			
			$this->_queryID =@$this->_query($sql,$inputarr);
		}
		
		/************************
		// OK, query executed
		*************************/

		if ($this->_queryID === false) {
		// error handling if query fails
			if ($this->debug == 99) adodb_backtrace(true,5);	
			$fn = $this->raiseErrorFn;
			if ($fn) {
				$fn($this->databaseType,'EXECUTE',$this->ErrorNo(),$this->ErrorMsg(),$sql,$inputarr,$this);
			} 
				
			return false;
		} 
		
		
		if ($this->_queryID === true) {
		// return simplified empty recordset for inserts/updates/deletes with lower overhead
			$rs =& new ADORecordSet_empty();
			return $rs;
		}
		
		// return real recordset from select statement
		$rsclass = $this->rsPrefix.$this->databaseType;
		$rs =& new $rsclass($this->_queryID,$this->fetchMode);
		$rs->connection = &$this; // Pablo suggestion
		$rs->Init();
		if (is_array($sql)) $rs->sql = $sql[0];
		else $rs->sql = $sql;
		if ($rs->_numOfRows <= 0) {
		global $ADODB_COUNTRECS;
	
			if ($ADODB_COUNTRECS) {
				if (!$rs->EOF){ 
					$rs = &$this->_rs2rs($rs,-1,-1,!is_array($sql));
					$rs->_queryID = $this->_queryID;
				} else
					$rs->_numOfRows = 0;
			}
		}
		return $rs;
	}

	function CreateSequence($seqname='adodbseq',$startID=1)
	{
		if (empty($this->_genSeqSQL)) return false;
		return $this->Execute(sprintf($this->_genSeqSQL,$seqname,$startID));
	}

	function DropSequence($seqname)
	{
		if (empty($this->_dropSeqSQL)) return false;
		return $this->Execute(sprintf($this->_dropSeqSQL,$seqname));
	}

	/**
	 * Generates a sequence id and stores it in $this->genID;
	 * GenID is only available if $this->hasGenID = true;
	 *
	 * @param seqname		name of sequence to use
	 * @param startID		if sequence does not exist, start at this ID
	 * @return		0 if not supported, otherwise a sequence id
	 */
	function GenID($seqname='adodbseq',$startID=1)
	{
		if (!$this->hasGenID) {
			return 0; // formerly returns false pre 1.60
		}
		
		$getnext = sprintf($this->_genIDSQL,$seqname);
		
		$holdtransOK = $this->_transOK;
		$rs = @$this->Execute($getnext);
		if (!$rs) {
			$this->_transOK = $holdtransOK; //if the status was ok before reset
			$createseq = $this->Execute(sprintf($this->_genSeqSQL,$seqname,$startID));
			$rs = $this->Execute($getnext);
		}
		if ($rs && !$rs->EOF) $this->genID = reset($rs->fields);
		else $this->genID = 0; // false
	
		if ($rs) $rs->Close();

		return $this->genID;
	}	

	/**
	 * @return  the last inserted ID. Not all databases support this.
	 */ 
	function Insert_ID()
	{
		if ($this->_logsql && $this->lastInsID) return $this->lastInsID;
		if ($this->hasInsertID) return $this->_insertid();
		if ($this->debug) {
			ADOConnection::outp( '<p>Insert_ID error</p>');
			adodb_backtrace();
		}
		return false;
	}


	/**
	 * Portable Insert ID. Pablo Roca <pabloroca@mvps.org>
	 *
	 * @return  the last inserted ID. All databases support this. But aware possible
	 * problems in multiuser environments. Heavy test this before deploying.
	 */ 
	function PO_Insert_ID($table="", $id="") 
	{
	   if ($this->hasInsertID){
		   return $this->Insert_ID();
	   } else {
		   return $this->GetOne("SELECT MAX($id) FROM $table");
	   }
	}

	/**
	* @return # rows affected by UPDATE/DELETE
	*/ 
	function Affected_Rows()
	{
		if ($this->hasAffectedRows) {
			if ($this->fnExecute === 'adodb_log_sql') {
				if ($this->_logsql && $this->_affected !== false) return $this->_affected;
			}
			$val = $this->_affectedrows();
			return ($val < 0) ? false : $val;
		}
				  
		if ($this->debug) ADOConnection::outp( '<p>Affected_Rows error</p>',false);
		return false;
	}
	
	
	/**
	 * @return  the last error message
	 */
	function ErrorMsg()
	{
		return '!! '.strtoupper($this->dataProvider.' '.$this->databaseType).': '.$this->_errorMsg;
	}
	
	
	/**
	 * @return the last error number. Normally 0 means no error.
	 */
	function ErrorNo() 
	{
		return ($this->_errorMsg) ? -1 : 0;
	}
	
	function MetaError($err=false)
	{
		include_once(ADODB_DIR."/adodb-error.inc.php");
		if ($err === false) $err = $this->ErrorNo();
		return adodb_error($this->dataProvider,$this->databaseType,$err);
	}
	
	function MetaErrorMsg($errno)
	{
		include_once(ADODB_DIR."/adodb-error.inc.php");
		return adodb_errormsg($errno);
	}
	
	/**
	 * @returns an array with the primary key columns in it.
	 */
	function MetaPrimaryKeys($table, $owner=false)
	{
	// owner not used in base class - see oci8
		$p = array();
		$objs =& $this->MetaColumns($table);
		if ($objs) {
			foreach($objs as $v) {
				if (!empty($v->primary_key))
					$p[] = $v->name;
			}
		}
		if (sizeof($p)) return $p;
		if (function_exists('ADODB_VIEW_PRIMARYKEYS'))
			return ADODB_VIEW_PRIMARYKEYS($this->databaseType, $this->database, $table, $owner);
		return false;
	}
	
	/**
	 * @returns assoc array where keys are tables, and values are foreign keys
	 */
	function MetaForeignKeys($table, $owner=false, $upper=false)
	{
		return false;
	}
	/**
	 * Choose a database to connect to. Many databases do not support this.
	 *
	 * @param dbName 	is the name of the database to select
	 * @return 		true or false
	 */
	function SelectDB($dbName) 
	{return false;}
	
	
	/**
	* Will select, getting rows from $offset (1-based), for $nrows. 
	* This simulates the MySQL "select * from table limit $offset,$nrows" , and
	* the PostgreSQL "select * from table limit $nrows offset $offset". Note that
	* MySQL and PostgreSQL parameter ordering is the opposite of the other.
	* eg. 
	*  SelectLimit('select * from table',3); will return rows 1 to 3 (1-based)
	*  SelectLimit('select * from table',3,2); will return rows 3 to 5 (1-based)
	*
	* Uses SELECT TOP for Microsoft databases (when $this->hasTop is set)
	* BUG: Currently SelectLimit fails with $sql with LIMIT or TOP clause already set
	*
	* @param sql
	* @param [offset]	is the row to start calculations from (1-based)
	* @param [nrows]		is the number of rows to get
	* @param [inputarr]	array of bind variables
	* @param [secs2cache]		is a private parameter only used by jlim
	* @return		the recordset ($rs->databaseType == 'array')
 	*/
	function &SelectLimit($sql,$nrows=-1,$offset=-1, $inputarr=false,$secs2cache=0)
	{
		if ($this->hasTop && $nrows > 0) {
		// suggested by Reinhard Balling. Access requires top after distinct 
		 // Informix requires first before distinct - F Riosa
			$ismssql = (strpos($this->databaseType,'mssql') !== false);
			if ($ismssql) $isaccess = false;
			else $isaccess = (strpos($this->databaseType,'access') !== false);
			
			if ($offset <= 0) {
				
					// access includes ties in result
					if ($isaccess) {
						$sql = preg_replace(
						'/(^\s*select\s+(distinctrow|distinct)?)/i','\\1 '.$this->hasTop.' '.$nrows.' ',$sql);

						if ($secs2cache>0) {
							$ret =& $this->CacheExecute($secs2cache, $sql,$inputarr);
						} else {
							$ret =& $this->Execute($sql,$inputarr);
						}
						return $ret; // PHP5 fix
					} else if ($ismssql){
						$sql = preg_replace(
						'/(^\s*select\s+(distinctrow|distinct)?)/i','\\1 '.$this->hasTop.' '.$nrows.' ',$sql);
					} else {
						$sql = preg_replace(
						'/(^\s*select\s)/i','\\1 '.$this->hasTop.' '.$nrows.' ',$sql);
					}
			} else {
				$nn = $nrows + $offset;
				if ($isaccess || $ismssql) {
					$sql = preg_replace(
					'/(^\s*select\s+(distinctrow|distinct)?)/i','\\1 '.$this->hasTop.' '.$nn.' ',$sql);
				} else {
					$sql = preg_replace(
					'/(^\s*select\s)/i','\\1 '.$this->hasTop.' '.$nn.' ',$sql);
				}
			}
		}
		
		// if $offset>0, we want to skip rows, and $ADODB_COUNTRECS is set, we buffer  rows
		// 0 to offset-1 which will be discarded anyway. So we disable $ADODB_COUNTRECS.
		global $ADODB_COUNTRECS;
		
		$savec = $ADODB_COUNTRECS;
		$ADODB_COUNTRECS = false;
			
		if ($offset>0){
			if ($secs2cache>0) $rs = &$this->CacheExecute($secs2cache,$sql,$inputarr);
			else $rs = &$this->Execute($sql,$inputarr);
		} else {
			if ($secs2cache>0) $rs = &$this->CacheExecute($secs2cache,$sql,$inputarr);
			else $rs = &$this->Execute($sql,$inputarr);
		}
		$ADODB_COUNTRECS = $savec;
		if ($rs && !$rs->EOF) {
			$rs =& $this->_rs2rs($rs,$nrows,$offset);
		}
		//print_r($rs);
		return $rs;
	}
	
	/**
	* Create serializable recordset. Breaks rs link to connection.
	*
	* @param rs			the recordset to serialize
	*/
	function &SerializableRS(&$rs)
	{
		$rs2 =& $this->_rs2rs($rs);
		$ignore = false;
		$rs2->connection =& $ignore;
		
		return $rs2;
	}
	
	/**
	* Convert database recordset to an array recordset
	* input recordset's cursor should be at beginning, and
	* old $rs will be closed.
	*
	* @param rs			the recordset to copy
	* @param [nrows]  	number of rows to retrieve (optional)
	* @param [offset] 	offset by number of rows (optional)
	* @return 			the new recordset
	*/
	function &_rs2rs(&$rs,$nrows=-1,$offset=-1,$close=true)
	{
		if (! $rs) return false;
		
		$dbtype = $rs->databaseType;
		if (!$dbtype) {
			$rs = &$rs;  // required to prevent crashing in 4.2.1, but does not happen in 4.3.1 -- why ?
			return $rs;
		}
		if (($dbtype == 'array' || $dbtype == 'csv') && $nrows == -1 && $offset == -1) {
			$rs->MoveFirst();
			$rs = &$rs; // required to prevent crashing in 4.2.1, but does not happen in 4.3.1-- why ?
			return $rs;
		}
		$flds = array();
		for ($i=0, $max=$rs->FieldCount(); $i < $max; $i++) {
			$flds[] = $rs->FetchField($i);
		}
		$arr =& $rs->GetArrayLimit($nrows,$offset);
		//print_r($arr);
		if ($close) $rs->Close();
		
		$arrayClass = $this->arrayClass;
		
		$rs2 =& new $arrayClass();
		$rs2->connection = &$this;
		$rs2->sql = $rs->sql;
		$rs2->dataProvider = $this->dataProvider;
		$rs2->InitArrayFields($arr,$flds);
		return $rs2;
	}
	
	/*
	* Return all rows. Compat with PEAR DB
	*/
	function &GetAll($sql, $inputarr=false)
	{
		$arr =& $this->GetArray($sql,$inputarr);
		return $arr;
	}
	
	function &GetAssoc($sql, $inputarr=false,$force_array = false, $first2cols = false)
	{
		$rs =& $this->Execute($sql, $inputarr);
		if (!$rs) return false;
		
		$arr =& $rs->GetAssoc($force_array,$first2cols);
		return $arr;
	}
	
	function &CacheGetAssoc($secs2cache, $sql=false, $inputarr=false,$force_array = false, $first2cols = false)
	{
		if (!is_numeric($secs2cache)) {
			$first2cols = $force_array;
			$force_array = $inputarr;
		}
		$rs =& $this->CacheExecute($secs2cache, $sql, $inputarr);
		if (!$rs) return false;
		
		$arr =& $rs->GetAssoc($force_array,$first2cols);
		return $arr;
	}
	
	/**
	* Return first element of first row of sql statement. Recordset is disposed
	* for you.
	*
	* @param sql			SQL statement
	* @param [inputarr]		input bind array
	*/
	function GetOne($sql,$inputarr=false)
	{
	global $ADODB_COUNTRECS;
		$crecs = $ADODB_COUNTRECS;
		$ADODB_COUNTRECS = false;
		
		$ret = false;
		$rs = &$this->Execute($sql,$inputarr);
		if ($rs) {		
			if (!$rs->EOF) $ret = reset($rs->fields);
			$rs->Close();
		} 
		$ADODB_COUNTRECS = $crecs;
		return $ret;
	}
	
	function CacheGetOne($secs2cache,$sql=false,$inputarr=false)
	{
		$ret = false;
		$rs = &$this->CacheExecute($secs2cache,$sql,$inputarr);
		if ($rs) {		
			if (!$rs->EOF) $ret = reset($rs->fields);
			$rs->Close();
		} 
		
		return $ret;
	}
	
	function GetCol($sql, $inputarr = false, $trim = false)
	{
	  	$rv = false;
	  	$rs = &$this->Execute($sql, $inputarr);
	  	if ($rs) {
			$rv = array();
	   		if ($trim) {
				while (!$rs->EOF) {
					$rv[] = trim(reset($rs->fields));
					$rs->MoveNext();
		   		}
			} else {
				while (!$rs->EOF) {
					$rv[] = reset($rs->fields);
					$rs->MoveNext();
		   		}
			}
	   		$rs->Close();
	  	}
	  	return $rv;
	}
	
	function CacheGetCol($secs, $sql = false, $inputarr = false,$trim=false)
	{
	  	$rv = false;
	  	$rs = &$this->CacheExecute($secs, $sql, $inputarr);
	  	if ($rs) {
			if ($trim) {
				while (!$rs->EOF) {
					$rv[] = trim(reset($rs->fields));
					$rs->MoveNext();
		   		}
			} else {
				while (!$rs->EOF) {
					$rv[] = reset($rs->fields);
					$rs->MoveNext();
		   		}
			}
	   		$rs->Close();
	  	}
	  	return $rv;
	}
 
	/*
		Calculate the offset of a date for a particular database and generate
			appropriate SQL. Useful for calculating future/past dates and storing
			in a database.
			
		If dayFraction=1.5 means 1.5 days from now, 1.0/24 for 1 hour.
	*/
	function OffsetDate($dayFraction,$date=false)
	{		
		if (!$date) $date = $this->sysDate;
		return  '('.$date.'+'.$dayFraction.')';
	}
	
	
	/**
	*
	* @param sql			SQL statement
	* @param [inputarr]		input bind array
	*/
	function &GetArray($sql,$inputarr=false)
	{
	global $ADODB_COUNTRECS;
		
		$savec = $ADODB_COUNTRECS;
		$ADODB_COUNTRECS = false;
		$rs =& $this->Execute($sql,$inputarr);
		$ADODB_COUNTRECS = $savec;
		if (!$rs) 
			if (defined('ADODB_PEAR')) return ADODB_PEAR_Error();
			else return false;
		$arr =& $rs->GetArray();
		$rs->Close();
		return $arr;
	}
	
	function &CacheGetAll($secs2cache,$sql=false,$inputarr=false)
	{
	global $ADODB_COUNTRECS;
		
		$savec = $ADODB_COUNTRECS;
		$ADODB_COUNTRECS = false;
		$rs =& $this->CacheExecute($secs2cache,$sql,$inputarr);
		$ADODB_COUNTRECS = $savec;
		
		if (!$rs) 
			if (defined('ADODB_PEAR')) return ADODB_PEAR_Error();
			else return false;
		
		$arr =& $rs->GetArray();
		$rs->Close();
		return $arr;
	}
	
	
	
	/**
	* Return one row of sql statement. Recordset is disposed for you.
	*
	* @param sql			SQL statement
	* @param [inputarr]		input bind array
	*/
	function &GetRow($sql,$inputarr=false)
	{
	global $ADODB_COUNTRECS;
		$crecs = $ADODB_COUNTRECS;
		$ADODB_COUNTRECS = false;
		
		$rs =& $this->Execute($sql,$inputarr);
		
		$ADODB_COUNTRECS = $crecs;
		if ($rs) {
			if (!$rs->EOF) $arr = $rs->fields;
			else $arr = array();
			$rs->Close();
			return $arr;
		}
		
		return false;
	}
	
	function &CacheGetRow($secs2cache,$sql=false,$inputarr=false)
	{
		$rs =& $this->CacheExecute($secs2cache,$sql,$inputarr);
		if ($rs) {
			$arr = false;
			if (!$rs->EOF) $arr = $rs->fields;
			$rs->Close();
			return $arr;
		}
		return false;
	}
	
	/**
	* Insert or replace a single record. Note: this is not the same as MySQL's replace. 
	* ADOdb's Replace() uses update-insert semantics, not insert-delete-duplicates of MySQL.
	* Also note that no table locking is done currently, so it is possible that the
	* record be inserted twice by two programs...
	*
	* $this->Replace('products', array('prodname' =>"'Nails'","price" => 3.99), 'prodname');
	*
	* $table		table name
	* $fieldArray	associative array of data (you must quote strings yourself).
	* $keyCol		the primary key field name or if compound key, array of field names
	* autoQuote		set to true to use a hueristic to quote strings. Works with nulls and numbers
	*					but does not work with dates nor SQL functions.
	* has_autoinc	the primary key is an auto-inc field, so skip in insert.
	*
	* Currently blob replace not supported
	*
	* returns 0 = fail, 1 = update, 2 = insert 
	*/
	
	function Replace($table, $fieldArray, $keyCol, $autoQuote=false, $has_autoinc=false)
	{
		global $ADODB_INCLUDED_LIB;
		if (empty($ADODB_INCLUDED_LIB)) include_once(ADODB_DIR.'/adodb-lib.inc.php');
		
		return _adodb_replace($this, $table, $fieldArray, $keyCol, $autoQuote, $has_autoinc);
	}
	
	
	/**
	* Will select, getting rows from $offset (1-based), for $nrows. 
	* This simulates the MySQL "select * from table limit $offset,$nrows" , and
	* the PostgreSQL "select * from table limit $nrows offset $offset". Note that
	* MySQL and PostgreSQL parameter ordering is the opposite of the other.
	* eg. 
	*  CacheSelectLimit(15,'select * from table',3); will return rows 1 to 3 (1-based)
	*  CacheSelectLimit(15,'select * from table',3,2); will return rows 3 to 5 (1-based)
	*
	* BUG: Currently CacheSelectLimit fails with $sql with LIMIT or TOP clause already set
	*
	* @param [secs2cache]	seconds to cache data, set to 0 to force query. This is optional
	* @param sql
	* @param [offset]	is the row to start calculations from (1-based)
	* @param [nrows]	is the number of rows to get
	* @param [inputarr]	array of bind variables
	* @return		the recordset ($rs->databaseType == 'array')
 	*/
	function &CacheSelectLimit($secs2cache,$sql,$nrows=-1,$offset=-1,$inputarr=false)
	{	
		if (!is_numeric($secs2cache)) {
			if ($sql === false) $sql = -1;
			if ($offset == -1) $offset = false;
									  // sql,	nrows, offset,inputarr
			$rs =& $this->SelectLimit($secs2cache,$sql,$nrows,$offset,$this->cacheSecs);
		} else {
			if ($sql === false) ADOConnection::outp( "Warning: \$sql missing from CacheSelectLimit()");
			$rs =& $this->SelectLimit($sql,$nrows,$offset,$inputarr,$secs2cache);
		}
		return $rs;
	}
	
	/**
	* Flush cached recordsets that match a particular $sql statement. 
	* If $sql == false, then we purge all files in the cache.
 	*/
	function CacheFlush($sql=false,$inputarr=false)
	{
	global $ADODB_CACHE_DIR;
	
		if (strlen($ADODB_CACHE_DIR) > 1 && !$sql) {
			if (strncmp(PHP_OS,'WIN',3) === 0) {
				$cmd = 'del /s '.str_replace('/','\\',$ADODB_CACHE_DIR).'\adodb_*.cache';
			} else {
				$cmd = 'rm -rf '.$ADODB_CACHE_DIR.'/??/adodb_*.cache'; 
				// old version 'rm -f `find '.$ADODB_CACHE_DIR.' -name adodb_*.cache`';
			}
			if ($this->debug) {
				ADOConnection::outp( "CacheFlush: $cmd<br><pre>\n", system($cmd),"</pre>");
			} else {
				exec($cmd);
			}
			return;
		} 
		$f = $this->_gencachename($sql.serialize($inputarr),false);
		adodb_write_file($f,''); // is adodb_write_file needed?
		if (!@unlink($f)) {
			if ($this->debug) ADOConnection::outp( "CacheFlush: failed for $f");
		}
	}
	
	/**
	* Private function to generate filename for caching.
	* Filename is generated based on:
	*
	*  - sql statement
	*  - database type (oci8, ibase, ifx, etc)
	*  - database name
	*  - userid
	*
	* We create 256 sub-directories in the cache directory ($ADODB_CACHE_DIR). 
	* Assuming that we can have 50,000 files per directory with good performance, 
	* then we can scale to 12.8 million unique cached recordsets. Wow!
 	*/
	function _gencachename($sql,$createdir)
	{
	global $ADODB_CACHE_DIR;
		
		$m = md5($sql.$this->databaseType.$this->database.$this->user);
		$dir = $ADODB_CACHE_DIR.'/'.substr($m,0,2);
		if ($createdir && !file_exists($dir)) {
			$oldu = umask(0);
			if (!mkdir($dir,0771)) 
				if ($this->debug) ADOConnection::outp( "Unable to mkdir $dir for $sql");
			umask($oldu);
		}
		return $dir.'/adodb_'.$m.'.cache';
	}
	
	
	/**
	 * Execute SQL, caching recordsets.
	 *
	 * @param [secs2cache]	seconds to cache data, set to 0 to force query. 
	 *					  This is an optional parameter.
	 * @param sql		SQL statement to execute
	 * @param [inputarr]	holds the input data  to bind to
	 * @return 		RecordSet or false
	 */
	function &CacheExecute($secs2cache,$sql=false,$inputarr=false)
	{
		if (!is_numeric($secs2cache)) {
			$inputarr = $sql;
			$sql = $secs2cache;
			$secs2cache = $this->cacheSecs;
		}
		global $ADODB_INCLUDED_CSV;
		if (empty($ADODB_INCLUDED_CSV)) include_once(ADODB_DIR.'/adodb-csvlib.inc.php');
		
		if (is_array($sql)) $sql = $sql[0];
			
		$md5file = $this->_gencachename($sql.serialize($inputarr),true);
		$err = '';
		
		if ($secs2cache > 0){
			$rs = &csv2rs($md5file,$err,$secs2cache);
			$this->numCacheHits += 1;
		} else {
			$err='Timeout 1';
			$rs = false;
			$this->numCacheMisses += 1;
		}
		if (!$rs) {
		// no cached rs found
			if ($this->debug) {
				if (get_magic_quotes_runtime()) {
					ADOConnection::outp("Please disable magic_quotes_runtime - it corrupts cache files :(");
				}
				if ($this->debug !== -1) ADOConnection::outp( " $md5file cache failure: $err (see sql below)");
			}
			$rs = &$this->Execute($sql,$inputarr);
			if ($rs) {
				$eof = $rs->EOF;
				$rs = &$this->_rs2rs($rs); // read entire recordset into memory immediately
				$txt = _rs2serialize($rs,false,$sql); // serialize
		
				if (!adodb_write_file($md5file,$txt,$this->debug)) {
					if ($fn = $this->raiseErrorFn) {
						$fn($this->databaseType,'CacheExecute',-32000,"Cache write error",$md5file,$sql,$this);
					}
					if ($this->debug) ADOConnection::outp( " Cache write error");
				}
				if ($rs->EOF && !$eof) {
					$rs->MoveFirst();
					//$rs = &csv2rs($md5file,$err);		
					$rs->connection = &$this; // Pablo suggestion
				}  
				
			} else
				@unlink($md5file);
		} else {
			$this->_errorMsg = '';
			$this->_errorCode = 0;
			
			if ($this->fnCacheExecute) {
				$fn = $this->fnCacheExecute;
				$fn($this, $secs2cache, $sql, $inputarr);
			}
		// ok, set cached object found
			$rs->connection = &$this; // Pablo suggestion
			if ($this->debug){ 
			global $HTTP_SERVER_VARS;
					
				$inBrowser = isset($HTTP_SERVER_VARS['HTTP_USER_AGENT']);
				$ttl = $rs->timeCreated + $secs2cache - time();
				$s = is_array($sql) ? $sql[0] : $sql;
				if ($inBrowser) $s = '<i>'.htmlspecialchars($s).'</i>';
				
				ADOConnection::outp( " $md5file reloaded, ttl=$ttl [ $s ]");
			}
		}
		return $rs;
	}
	
	
	/**
	 * Generates an Update Query based on an existing recordset.
	 * $arrFields is an associative array of fields with the value
	 * that should be assigned.
	 *
	 * Note: This function should only be used on a recordset
	 *	   that is run against a single table and sql should only 
	 *		 be a simple select stmt with no groupby/orderby/limit
	 *
	 * "Jonathan Younger" <jyounger@unilab.com>
  	 */
	function GetUpdateSQL(&$rs, $arrFields,$forceUpdate=false,$magicq=false)
	{
		global $ADODB_INCLUDED_LIB;
		if (empty($ADODB_INCLUDED_LIB)) include_once(ADODB_DIR.'/adodb-lib.inc.php');
		return _adodb_getupdatesql($this,$rs,$arrFields,$forceUpdate,$magicq);
	}


	/**
	 * Generates an Insert Query based on an existing recordset.
	 * $arrFields is an associative array of fields with the value
	 * that should be assigned.
	 *
	 * Note: This function should only be used on a recordset
	 *	   that is run against a single table.
  	 */
	function GetInsertSQL(&$rs, $arrFields,$magicq=false)
	{	
		global $ADODB_INCLUDED_LIB;
		if (empty($ADODB_INCLUDED_LIB)) include_once(ADODB_DIR.'/adodb-lib.inc.php');
		return _adodb_getinsertsql($this,$rs,$arrFields,$magicq);
	}
	

	/**
	* Update a blob column, given a where clause. There are more sophisticated
	* blob handling functions that we could have implemented, but all require
	* a very complex API. Instead we have chosen something that is extremely
	* simple to understand and use. 
	*
	* Note: $blobtype supports 'BLOB' and 'CLOB', default is BLOB of course.
	*
	* Usage to update a $blobvalue which has a primary key blob_id=1 into a 
	* field blobtable.blobcolumn:
	*
	*	UpdateBlob('blobtable', 'blobcolumn', $blobvalue, 'blob_id=1');
	*
	* Insert example:
	*
	*	$conn->Execute('INSERT INTO blobtable (id, blobcol) VALUES (1, null)');
	*	$conn->UpdateBlob('blobtable','blobcol',$blob,'id=1');
	*/
	
	function UpdateBlob($table,$column,$val,$where,$blobtype='BLOB')
	{
		return $this->Execute("UPDATE $table SET $column=? WHERE $where",array($val)) != false;
	}

	/**
	* Usage:
	*	UpdateBlob('TABLE', 'COLUMN', '/path/to/file', 'ID=1');
	*	
	*	$blobtype supports 'BLOB' and 'CLOB'
	*
	*	$conn->Execute('INSERT INTO blobtable (id, blobcol) VALUES (1, null)');
	*	$conn->UpdateBlob('blobtable','blobcol',$blobpath,'id=1');
	*/
	function UpdateBlobFile($table,$column,$path,$where,$blobtype='BLOB')
	{
		$fd = fopen($path,'rb');
		if ($fd === false) return false;
		$val = fread($fd,filesize($path));
		fclose($fd);
		return $this->UpdateBlob($table,$column,$val,$where,$blobtype);
	}
	
	function BlobDecode($blob)
	{
		return $blob;
	}
	
	function BlobEncode($blob)
	{
		return $blob;
	}
	
	function SetCharSet($charset)
	{
		return false;
	}
	
	function IfNull( $field, $ifNull ) 
	{
		return " CASE WHEN $field is null THEN $ifNull ELSE $field END ";
	}
	
	function LogSQL($enable=true)
	{
		include_once(ADODB_DIR.'/adodb-perf.inc.php');
		
		if ($enable) $this->fnExecute = 'adodb_log_sql';
		else $this->fnExecute = false;
		
		$old = $this->_logsql;	
		$this->_logsql = $enable;
		if ($enable && !$old) $this->_affected = false;
		return $old;
	}
	
	function GetCharSet()
	{
		return false;
	}
	
	/**
	* Usage:
	*	UpdateClob('TABLE', 'COLUMN', $var, 'ID=1', 'CLOB');
	*
	*	$conn->Execute('INSERT INTO clobtable (id, clobcol) VALUES (1, null)');
	*	$conn->UpdateClob('clobtable','clobcol',$clob,'id=1');
	*/
	function UpdateClob($table,$column,$val,$where)
	{
		return $this->UpdateBlob($table,$column,$val,$where,'CLOB');
	}
	
	
	/**
	*  Change the SQL connection locale to a specified locale.
	*  This is used to get the date formats written depending on the client locale.
	*/
	function SetDateLocale($locale = 'En')
	{
		$this->locale = $locale;
		switch ($locale)
		{
			default:
			case 'En':
				$this->fmtDate="Y-m-d";
				$this->fmtTimeStamp = "Y-m-d H:i:s";
				break;
	
			case 'Fr':
			case 'Ro':
			case 'It':
				$this->fmtDate="d-m-Y";
				$this->fmtTimeStamp = "d-m-Y H:i:s";
				break;
				
			case 'Ge':
				$this->fmtDate="d.m.Y";
				$this->fmtTimeStamp = "d.m.Y H:i:s";
				break;
		}
	}
	
	
	/**
	 *  $meta	contains the desired type, which could be...
	 *	C for character. You will have to define the precision yourself.
	 *	X for teXt. For unlimited character lengths.
	 *	B for Binary
	 *  F for floating point, with no need to define scale and precision
	 * 	N for decimal numbers, you will have to define the (scale, precision) yourself
	 *	D for date
	 *	T for timestamp
	 * 	L for logical/Boolean
	 *	I for integer
	 *	R for autoincrement counter/integer
	 *  and if you want to use double-byte, add a 2 to the end, like C2 or X2.
	 * 
	 *
	 * @return the actual type of the data or false if no such type available
	*/
 	function ActualType($meta)
	{
		switch($meta) {
		case 'C':
		case 'X':
			return 'VARCHAR';
		case 'B':
			
		case 'D':
		case 'T':
		case 'L':
		
		case 'R':
			
		case 'I':
		case 'N':
			return false;
		}
	}

	
	/**
	 * Close Connection
	 */
	function Close() 
	{
		return $this->_close();
		
		// "Simon Lee" <simon@mediaroad.com> reports that persistent connections need 
		// to be closed too!
		//if ($this->_isPersistentConnection != true) return $this->_close();
		//else return true;	
	}
	
	/**
	 * Begin a Transaction. Must be followed by CommitTrans() or RollbackTrans().
	 *
	 * @return true if succeeded or false if database does not support transactions
	 */
	function BeginTrans() {return false;}
	
	
	/**
	 * If database does not support transactions, always return true as data always commited
	 *
	 * @param $ok  set to false to rollback transaction, true to commit
	 *
	 * @return true/false.
	 */
	function CommitTrans($ok=true) 
	{ return true;}
	
	
	/**
	 * If database does not support transactions, rollbacks always fail, so return false
	 *
	 * @return true/false.
	 */
	function RollbackTrans() 
	{ return false;}


	/**
	 * return the databases that the driver can connect to. 
	 * Some databases will return an empty array.
	 *
	 * @return an array of database names.
	 */
		function MetaDatabases() 
		{
		global $ADODB_FETCH_MODE;
		
			if ($this->metaDatabasesSQL) {
				$save = $ADODB_FETCH_MODE; 
				$ADODB_FETCH_MODE = ADODB_FETCH_NUM; 
				
				if ($this->fetchMode !== false) $savem = $this->SetFetchMode(false);
				
				$arr = $this->GetCol($this->metaDatabasesSQL);
				if (isset($savem)) $this->SetFetchMode($savem);
				$ADODB_FETCH_MODE = $save; 
			
				return $arr;
			}
			
			return false;
		}
		
	/**
	 * @param ttype can either be 'VIEW' or 'TABLE' or false. 
	 * 		If false, both views and tables are returned.
	 *		"VIEW" returns only views
	 *		"TABLE" returns only tables
	 * @param showSchema returns the schema/user with the table name, eg. USER.TABLE
	 * @param mask  is the input mask - only supported by oci8 and postgresql
	 *
	 * @return  array of tables for current database.
	 */ 
	function &MetaTables($ttype=false,$showSchema=false,$mask=false) 
	{
	global $ADODB_FETCH_MODE;
	
		if ($mask) return false;
		
		if ($this->metaTablesSQL) {
			// complicated state saving by the need for backward compat
			$save = $ADODB_FETCH_MODE; 
			$ADODB_FETCH_MODE = ADODB_FETCH_NUM; 
			
			if ($this->fetchMode !== false) $savem = $this->SetFetchMode(false);
			
			$rs = $this->Execute($this->metaTablesSQL);
			if (isset($savem)) $this->SetFetchMode($savem);
			$ADODB_FETCH_MODE = $save; 
			
			if ($rs === false) return false;
			$arr =& $rs->GetArray();
			$arr2 = array();
			
			if ($hast = ($ttype && isset($arr[0][1]))) { 
				$showt = strncmp($ttype,'T',1);
			}
			
			for ($i=0; $i < sizeof($arr); $i++) {
				if ($hast) {
					if ($showt == 0) {
						if (strncmp($arr[$i][1],'T',1) == 0) $arr2[] = trim($arr[$i][0]);
					} else {
						if (strncmp($arr[$i][1],'V',1) == 0) $arr2[] = trim($arr[$i][0]);
					}
				} else
					$arr2[] = trim($arr[$i][0]);
			}
			$rs->Close();
			return $arr2;
		}
		return false;
	}
	
	
	function _findschema(&$table,&$schema)
	{
		if (!$schema && ($at = strpos($table,'.')) !== false) {
			$schema = substr($table,0,$at);
			$table = substr($table,$at+1);
		}
	}
	
	/**
	 * List columns in a database as an array of ADOFieldObjects. 
	 * See top of file for definition of object.
	 *
	 * @param table	table name to query
	 * @param upper	uppercase table name (required by some databases)
	 * @schema is optional database schema to use - not supported by all databases.
	 *
	 * @return  array of ADOFieldObjects for current table.
	 */
	function &MetaColumns($table,$upper=true) 
	{
	global $ADODB_FETCH_MODE;
		
		if (!empty($this->metaColumnsSQL)) {
		
			$schema = false;
			$this->_findschema($table,$schema);
		
			$save = $ADODB_FETCH_MODE;
			$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
			if ($this->fetchMode !== false) $savem = $this->SetFetchMode(false);
			$rs = $this->Execute(sprintf($this->metaColumnsSQL,($upper)?strtoupper($table):$table));
			if (isset($savem)) $this->SetFetchMode($savem);
			$ADODB_FETCH_MODE = $save;
			if ($rs === false) return false;

			$retarr = array();
			while (!$rs->EOF) { //print_r($rs->fields);
				$fld =& new ADOFieldObject();
				$fld->name = $upper ? strtoupper($rs->fields[0]) : $rs->fields[0];
				$fld->type = $rs->fields[1];
				if (isset($rs->fields[3]) && $rs->fields[3]) {
					if ($rs->fields[3]>0) $fld->max_length = $rs->fields[3];
					$fld->scale = $rs->fields[4];
					if ($fld->scale>0) $fld->max_length += 1;
				} else
					$fld->max_length = $rs->fields[2];
					
				if ($ADODB_FETCH_MODE == ADODB_FETCH_NUM) $retarr[] = $fld;	
				else $retarr[$fld->name] = $fld;
				$rs->MoveNext();
			}
			$rs->Close();
			return $retarr;	
		}
		return false;
	}
	
    /**
      * List indexes on a table as an array.
      * @param table        table name to query
      * @param primary include primary keys.
	  *
      * @return array of indexes on current table.
      */
     function &MetaIndexes($table, $primary = false, $owner = false)
     {
             return FALSE;
     }

	/**
	 * List columns names in a table as an array. 
	 * @param table	table name to query
	 *
	 * @return  array of column names for current table.
	 */ 
	function &MetaColumnNames($table) 
	{
		$objarr =& $this->MetaColumns($table);
		if (!is_array($objarr)) return false;
		
		$arr = array();
		foreach($objarr as $v) {
			$arr[strtoupper($v->name)] = $v->name;
		}
		return $arr;
	}
			
	/**
	 * Different SQL databases used different methods to combine strings together.
	 * This function provides a wrapper. 
	 * 
	 * param s	variable number of string parameters
	 *
	 * Usage: $db->Concat($str1,$str2);
	 * 
	 * @return concatenated string
	 */ 	 
	function Concat()
	{	
		$arr = func_get_args();
		return implode($this->concat_operator, $arr);
	}
	
	
	/**
	 * Converts a date "d" to a string that the database can understand.
	 *
	 * @param d	a date in Unix date time format.
	 *
	 * @return  date string in database date format
	 */
	function DBDate($d)
	{
		if (empty($d) && $d !== 0) return 'null';

		if (is_string($d) && !is_numeric($d)) {
			if ($d === 'null' || strncmp($d,"'",1) === 0) return $d;
			if ($this->isoDates) return "'$d'";
			$d = ADOConnection::UnixDate($d);
		}

		return adodb_date($this->fmtDate,$d);
	}
	
	
	/**
	 * Converts a timestamp "ts" to a string that the database can understand.
	 *
	 * @param ts	a timestamp in Unix date time format.
	 *
	 * @return  timestamp string in database timestamp format
	 */
	function DBTimeStamp($ts)
	{
		if (empty($ts) && $ts !== 0) return 'null';

		# strlen(14) allows YYYYMMDDHHMMSS format
		if (!is_string($ts) || (is_numeric($ts) && strlen($ts)<14)) 
			return adodb_date($this->fmtTimeStamp,$ts);
		
		if ($ts === 'null') return $ts;
		if ($this->isoDates && strlen($ts) !== 14) return "'$ts'";
		
		$ts = ADOConnection::UnixTimeStamp($ts);
		return adodb_date($this->fmtTimeStamp,$ts);
	}
	
	/**
	 * Also in ADORecordSet.
	 * @param $v is a date string in YYYY-MM-DD format
	 *
	 * @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid date format
	 */
	function UnixDate($v)
	{
		if (!preg_match( "|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})|", 
			($v), $rr)) return false;

		if ($rr[1] <= TIMESTAMP_FIRST_YEAR) return 0;
		// h-m-s-MM-DD-YY
		return @adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
	}
	

	/**
	 * Also in ADORecordSet.
	 * @param $v is a timestamp string in YYYY-MM-DD HH-NN-SS format
	 *
	 * @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid date format
	 */
	function UnixTimeStamp($v)
	{
		if (!preg_match( 
			"|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})[ ,-]*(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|", 
			($v), $rr)) return false;
			
		if ($rr[1] <= TIMESTAMP_FIRST_YEAR && $rr[2]<= 1) return 0;
	
		// h-m-s-MM-DD-YY
		if (!isset($rr[5])) return  adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
		return  @adodb_mktime($rr[5],$rr[6],$rr[7],$rr[2],$rr[3],$rr[1]);
	}
	
	/**
	 * Also in ADORecordSet.
	 *
	 * Format database date based on user defined format.
	 *
	 * @param v  	is the character date in YYYY-MM-DD format, returned by database
	 * @param fmt 	is the format to apply to it, using date()
	 *
	 * @return a date formated as user desires
	 */
	 
	function UserDate($v,$fmt='Y-m-d')
	{
		$tt = $this->UnixDate($v);
		// $tt == -1 if pre TIMESTAMP_FIRST_YEAR
		if (($tt === false || $tt == -1) && $v != false) return $v;
		else if ($tt == 0) return $this->emptyDate;
		else if ($tt == -1) { // pre-TIMESTAMP_FIRST_YEAR
		}
		
		return adodb_date($fmt,$tt);
	
	}
	
		/**
	 *
	 * @param v  	is the character timestamp in YYYY-MM-DD hh:mm:ss format
	 * @param fmt 	is the format to apply to it, using date()
	 *
	 * @return a timestamp formated as user desires
	 */
	function UserTimeStamp($v,$fmt='Y-m-d H:i:s')
	{
		# strlen(14) allows YYYYMMDDHHMMSS format
		if (is_numeric($v) && strlen($v)<14) return adodb_date($fmt,$v);
		$tt = $this->UnixTimeStamp($v);
		// $tt == -1 if pre TIMESTAMP_FIRST_YEAR
		if (($tt === false || $tt == -1) && $v != false) return $v;
		if ($tt == 0) return $this->emptyTimeStamp;
		return adodb_date($fmt,$tt);
	}
	
	/**
	* Quotes a string, without prefixing nor appending quotes. 
	*/
	function addq($s, $magicq=false)
	{
		if (!$magicq) {
		
			if ($this->replaceQuote[0] == '\\'){
				// only since php 4.0.5
				$s = adodb_str_replace(array('\\',"\0"),array('\\\\',"\\\0"),$s);
				//$s = str_replace("\0","\\\0", str_replace('\\','\\\\',$s));
			}
			return  str_replace("'",$this->replaceQuote,$s);
		}
		
		// undo magic quotes for "
		$s = str_replace('\\"','"',$s);
		
		if ($this->replaceQuote == "\\'")  // ' already quoted, no need to change anything
			return $s;
		else {// change \' to '' for sybase/mssql
			$s = str_replace('\\\\','\\',$s);
			return str_replace("\\'",$this->replaceQuote,$s);
		}
	}
	
	/**
	 * Correctly quotes a string so that all strings are escaped. We prefix and append
	 * to the string single-quotes.
	 * An example is  $db->qstr("Don't bother",magic_quotes_runtime());
	 * 
	 * @param s			the string to quote
	 * @param [magic_quotes]	if $s is GET/POST var, set to get_magic_quotes_gpc().
	 *				This undoes the stupidity of magic quotes for GPC.
	 *
	 * @return  quoted string to be sent back to database
	 */
	function qstr($s,$magic_quotes=false)
	{	
		if (!$magic_quotes) {
		
			if ($this->replaceQuote[0] == '\\'){
				// only since php 4.0.5
				$s = adodb_str_replace(array('\\',"\0"),array('\\\\',"\\\0"),$s);
				//$s = str_replace("\0","\\\0", str_replace('\\','\\\\',$s));
			}
			return  "'".str_replace("'",$this->replaceQuote,$s)."'";
		}
		
		// undo magic quotes for "
		$s = str_replace('\\"','"',$s);
		
		if ($this->replaceQuote == "\\'")  // ' already quoted, no need to change anything
			return "'$s'";
		else {// change \' to '' for sybase/mssql
			$s = str_replace('\\\\','\\',$s);
			return "'".str_replace("\\'",$this->replaceQuote,$s)."'";
		}
	}
	
	
	/**
	* Will select the supplied $page number from a recordset, given that it is paginated in pages of 
	* $nrows rows per page. It also saves two boolean values saying if the given page is the first 
	* and/or last one of the recordset. Added by Iv�n Oliva to provide recordset pagination.
	*
	* See readme.htm#ex8 for an example of usage.
	*
	* @param sql
	* @param nrows		is the number of rows per page to get
	* @param page		is the page number to get (1-based)
	* @param [inputarr]	array of bind variables
	* @param [secs2cache]		is a private parameter only used by jlim
	* @return		the recordset ($rs->databaseType == 'array')
	*
	* NOTE: phpLens uses a different algorithm and does not use PageExecute().
	*
	*/
	function &PageExecute($sql, $nrows, $page, $inputarr=false, $secs2cache=0) 
	{
		global $ADODB_INCLUDED_LIB;
		if (empty($ADODB_INCLUDED_LIB)) include_once(ADODB_DIR.'/adodb-lib.inc.php');
		if ($this->pageExecuteCountRows) return _adodb_pageexecute_all_rows($this, $sql, $nrows, $page, $inputarr, $secs2cache);
		return _adodb_pageexecute_no_last_page($this, $sql, $nrows, $page, $inputarr, $secs2cache);

	}
	
		
	/**
	* Will select the supplied $page number from a recordset, given that it is paginated in pages of 
	* $nrows rows per page. It also saves two boolean values saying if the given page is the first 
	* and/or last one of the recordset. Added by Iv�n Oliva to provide recordset pagination.
	*
	* @param secs2cache	seconds to cache data, set to 0 to force query
	* @param sql
	* @param nrows		is the number of rows per page to get
	* @param page		is the page number to get (1-based)
	* @param [inputarr]	array of bind variables
	* @return		the recordset ($rs->databaseType == 'array')
	*/
	function &CachePageExecute($secs2cache, $sql, $nrows, $page,$inputarr=false) 
	{
		/*switch($this->dataProvider) {
		case 'postgres':
		case 'mysql': 
			break;
		default: $secs2cache = 0; break;
		}*/
		$rs =& $this->PageExecute($sql,$nrows,$page,$inputarr,$secs2cache);
		return $rs;
	}

} // end class ADOConnection
	
	
	
	//==============================================================================================	
	// CLASS ADOFetchObj
	//==============================================================================================	
		
	/**
	* Internal placeholder for record objects. Used by ADORecordSet->FetchObj().
	*/
	class ADOFetchObj {
	};
	
	//==============================================================================================	
	// CLASS ADORecordSet_empty
	//==============================================================================================	
	
	/**
	* Lightweight recordset when there are no records to be returned
	*/
	class ADORecordSet_empty
	{
		var $dataProvider = 'empty';
		var $databaseType = false;
		var $EOF = true;
		var $_numOfRows = 0;
		var $fields = false;
		var $connection = false;
		function RowCount() {return 0;}
		function RecordCount() {return 0;}
		function PO_RecordCount(){return 0;}
		function Close(){return true;}
		function FetchRow() {return false;}
		function FieldCount(){ return 0;}
	}
	
	//==============================================================================================	
	// DATE AND TIME FUNCTIONS
	//==============================================================================================	
	include_once(ADODB_DIR.'/adodb-time.inc.php');
	
	//==============================================================================================	
	// CLASS ADORecordSet
	//==============================================================================================	

	if (PHP_VERSION < 5) include_once(ADODB_DIR.'/adodb-php4.inc.php');
	else include_once(ADODB_DIR.'/adodb-iterator.inc.php');
   /**
	 * RecordSet class that represents the dataset returned by the database.
	 * To keep memory overhead low, this class holds only the current row in memory.
	 * No prefetching of data is done, so the RecordCount() can return -1 ( which
	 * means recordcount not known).
	 */
	class ADORecordSet extends ADODB_BASE_RS {
	/*
	 * public variables	
	 */
	var $dataProvider = "native";
	var $fields = false; 	/// holds the current row data
	var $blobSize = 100; 	/// any varchar/char field this size or greater is treated as a blob
							/// in other words, we use a text area for editing.
	var $canSeek = false; 	/// indicates that seek is supported
	var $sql; 				/// sql text
	var $EOF = false;		/// Indicates that the current record position is after the last record in a Recordset object. 
	
	var $emptyTimeStamp = '&nbsp;'; /// what to display when $time==0
	var $emptyDate = '&nbsp;'; /// what to display when $time==0
	var $debug = false;
	var $timeCreated=0; 	/// datetime in Unix format rs created -- for cached recordsets

	var $bind = false; 		/// used by Fields() to hold array - should be private?
	var $fetchMode;			/// default fetch mode
	var $connection = false; /// the parent connection
	/*
	 *	private variables	
	 */
	var $_numOfRows = -1;	/** number of rows, or -1 */
	var $_numOfFields = -1;	/** number of fields in recordset */
	var $_queryID = -1;		/** This variable keeps the result link identifier.	*/
	var $_currentRow = -1;	/** This variable keeps the current row in the Recordset.	*/
	var $_closed = false; 	/** has recordset been closed */
	var $_inited = false; 	/** Init() should only be called once */
	var $_obj; 				/** Used by FetchObj */
	var $_names;			/** Used by FetchObj */
	
	var $_currentPage = -1;	/** Added by Iv�n Oliva to implement recordset pagination */
	var $_atFirstPage = false;	/** Added by Iv�n Oliva to implement recordset pagination */
	var $_atLastPage = false;	/** Added by Iv�n Oliva to implement recordset pagination */
	var $_lastPageNo = -1; 
	var $_maxRecordCount = 0;
	var $datetime = false;
	
	/**
	 * Constructor
	 *
	 * @param queryID  	this is the queryID returned by ADOConnection->_query()
	 *
	 */
	function ADORecordSet($queryID) 
	{
		$this->_queryID = $queryID;
	}
	
	
	
	function Init()
	{
		if ($this->_inited) return;
		$this->_inited = true;
		if ($this->_queryID) @$this->_initrs();
		else {
			$this->_numOfRows = 0;
			$this->_numOfFields = 0;
		}
		if ($this->_numOfRows != 0 && $this->_numOfFields && $this->_currentRow == -1) {
			
			$this->_currentRow = 0;
			if ($this->EOF = ($this->_fetch() === false)) {
				$this->_numOfRows = 0; // _numOfRows could be -1
			}
		} else {
			$this->EOF = true;
		}
	}
	
	
	/**
	 * Generate a SELECT tag string from a recordset, and return the string.
	 * If the recordset has 2 cols, we treat the 1st col as the containing 
	 * the text to display to the user, and 2nd col as the return value. Default
	 * strings are compared with the FIRST column.
	 *
	 * @param name  		name of SELECT tag
	 * @param [defstr]		the value to hilite. Use an array for multiple hilites for listbox.
	 * @param [blank1stItem]	true to leave the 1st item in list empty
	 * @param [multiple]		true for listbox, false for popup
	 * @param [size]		#rows to show for listbox. not used by popup
	 * @param [selectAttr]		additional attributes to defined for SELECT tag.
	 *				useful for holding javascript onChange='...' handlers.
	 & @param [compareFields0]	when we have 2 cols in recordset, we compare the defstr with 
	 *				column 0 (1st col) if this is true. This is not documented.
	 *
	 * @return HTML
	 *
	 * changes by glen.davies@cce.ac.nz to support multiple hilited items
	 */
	function GetMenu($name,$defstr='',$blank1stItem=true,$multiple=false,
			$size=0, $selectAttr='',$compareFields0=true)
	{
		global $ADODB_INCLUDED_LIB;
		if (empty($ADODB_INCLUDED_LIB)) include_once(ADODB_DIR.'/adodb-lib.inc.php');
		return _adodb_getmenu($this, $name,$defstr,$blank1stItem,$multiple,
			$size, $selectAttr,$compareFields0);
	}
	
	/**
	 * Generate a SELECT tag string from a recordset, and return the string.
	 * If the recordset has 2 cols, we treat the 1st col as the containing 
	 * the text to display to the user, and 2nd col as the return value. Default
	 * strings are compared with the SECOND column.
	 *
	 */
	function GetMenu2($name,$defstr='',$blank1stItem=true,$multiple=false,$size=0, $selectAttr='')	
	{
		global $ADODB_INCLUDED_LIB;
		if (empty($ADODB_INCLUDED_LIB)) include_once(ADODB_DIR.'/adodb-lib.inc.php');
		return _adodb_getmenu($this,$name,$defstr,$blank1stItem,$multiple,
			$size, $selectAttr,false);
	}


	/**
	 * return recordset as a 2-dimensional array.
	 *
	 * @param [nRows]  is the number of rows to return. -1 means every row.
	 *
	 * @return an array indexed by the rows (0-based) from the recordset
	 */
	function &GetArray($nRows = -1) 
	{
	global $ADODB_EXTENSION; if ($ADODB_EXTENSION) return adodb_getall($this,$nRows);
		
		$results = array();
		$cnt = 0;
		while (!$this->EOF && $nRows != $cnt) {
			$results[] = $this->fields;
			$this->MoveNext();
			$cnt++;
		}
		return $results;
	}
	
	function &GetAll($nRows = -1)
	{
		$arr =& $this->GetArray($nRows);
		return $arr;
	}
	
	/*
	* Some databases allow multiple recordsets to be returned. This function
	* will return true if there is a next recordset, or false if no more.
	*/
	function NextRecordSet()
	{
		return false;
	}
	
	/**
	 * return recordset as a 2-dimensional array. 
	 * Helper function for ADOConnection->SelectLimit()
	 *
	 * @param offset	is the row to start calculations from (1-based)
	 * @param [nrows]	is the number of rows to return
	 *
	 * @return an array indexed by the rows (0-based) from the recordset
	 */
	function &GetArrayLimit($nrows,$offset=-1) 
	{	
		if ($offset <= 0) {
			$arr =& $this->GetArray($nrows);
			return $arr;
		} 
		
		$this->Move($offset);
		
		$results = array();
		$cnt = 0;
		while (!$this->EOF && $nrows != $cnt) {
			$results[$cnt++] = $this->fields;
			$this->MoveNext();
		}
		
		return $results;
	}
	
	
	/**
	 * Synonym for GetArray() for compatibility with ADO.
	 *
	 * @param [nRows]  is the number of rows to return. -1 means every row.
	 *
	 * @return an array indexed by the rows (0-based) from the recordset
	 */
	function &GetRows($nRows = -1) 
	{
		$arr =& $this->GetArray($nRows);
		return $arr;
	}
	
	/**
	 * return whole recordset as a 2-dimensional associative array if there are more than 2 columns. 
	 * The first column is treated as the key and is not included in the array. 
	 * If there is only 2 columns, it will return a 1 dimensional array of key-value pairs unless
	 * $force_array == true.
	 *
	 * @param [force_array] has only meaning if we have 2 data columns. If false, a 1 dimensional
	 * 	array is returned, otherwise a 2 dimensional array is returned. If this sounds confusing,
	 * 	read the source.
	 *
	 * @param [first2cols] means if there are more than 2 cols, ignore the remaining cols and 
	 * instead of returning array[col0] => array(remaining cols), return array[col0] => col1
	 *
	 * @return an associative array indexed by the first column of the array, 
	 * 	or false if the  data has less than 2 cols.
	 */
	function &GetAssoc($force_array = false, $first2cols = false) {
		$cols = $this->_numOfFields;
		if ($cols < 2) {
			return false;
		}
		$numIndex = isset($this->fields[0]);
		$results = array();
		
		if (!$first2cols && ($cols > 2 || $force_array)) {
			if ($numIndex) {
				while (!$this->EOF) {
					$results[trim($this->fields[0])] = array_slice($this->fields, 1);
					$this->MoveNext();
				}
			} else {
				while (!$this->EOF) {
					$results[trim(reset($this->fields))] = array_slice($this->fields, 1);
					$this->MoveNext();
				}
			}
		} else {
			// return scalar values
			if ($numIndex) {
				while (!$this->EOF) {
				// some bug in mssql PHP 4.02 -- doesn't handle references properly so we FORCE creating a new string
					$results[trim(($this->fields[0]))] = $this->fields[1];
					$this->MoveNext();
				}
			} else {
				while (!$this->EOF) {
				// some bug in mssql PHP 4.02 -- doesn't handle references properly so we FORCE creating a new string
					$v1 = trim(reset($this->fields));
					$v2 = ''.next($this->fields); 
					$results[$v1] = $v2;
					$this->MoveNext();
				}
			}
		}
		return $results; 
	}
	
	
	/**
	 *
	 * @param v  	is the character timestamp in YYYY-MM-DD hh:mm:ss format
	 * @param fmt 	is the format to apply to it, using date()
	 *
	 * @return a timestamp formated as user desires
	 */
	function UserTimeStamp($v,$fmt='Y-m-d H:i:s')
	{
		if (is_numeric($v) && strlen($v)<14) return adodb_date($fmt,$v);
		$tt = $this->UnixTimeStamp($v);
		// $tt == -1 if pre TIMESTAMP_FIRST_YEAR
		if (($tt === false || $tt == -1) && $v != false) return $v;
		if ($tt === 0) return $this->emptyTimeStamp;
		return adodb_date($fmt,$tt);
	}
	
	
	/**
	 * @param v  	is the character date in YYYY-MM-DD format, returned by database
	 * @param fmt 	is the format to apply to it, using date()
	 *
	 * @return a date formated as user desires
	 */
	function UserDate($v,$fmt='Y-m-d')
	{
		$tt = $this->UnixDate($v);
		// $tt == -1 if pre TIMESTAMP_FIRST_YEAR
		if (($tt === false || $tt == -1) && $v != false) return $v;
		else if ($tt == 0) return $this->emptyDate;
		else if ($tt == -1) { // pre-TIMESTAMP_FIRST_YEAR
		}
		return adodb_date($fmt,$tt);
	
	}
	
	
	/**
	 * @param $v is a date string in YYYY-MM-DD format
	 *
	 * @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid date format
	 */
	function UnixDate($v)
	{
		
		if (!preg_match( "|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})|", 
			($v), $rr)) return false;
			
		if ($rr[1] <= TIMESTAMP_FIRST_YEAR) return 0;
		// h-m-s-MM-DD-YY
		return @adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
	}
	

	/**
	 * @param $v is a timestamp string in YYYY-MM-DD HH-NN-SS format
	 *
	 * @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid date format
	 */
	function UnixTimeStamp($v)
	{
		
		if (!preg_match( 
			"|^([0-9]{4})[-/\.]?([0-9]{1,2})[-/\.]?([0-9]{1,2})[ ,-]*(([0-9]{1,2}):?([0-9]{1,2}):?([0-9\.]{1,4}))?|", 
			($v), $rr)) return false;
		if ($rr[1] <= TIMESTAMP_FIRST_YEAR && $rr[2]<= 1) return 0;
	
		// h-m-s-MM-DD-YY
		if (!isset($rr[5])) return  adodb_mktime(0,0,0,$rr[2],$rr[3],$rr[1]);
		return  @adodb_mktime($rr[5],$rr[6],$rr[7],$rr[2],$rr[3],$rr[1]);
	}
	
	
	/**
	* PEAR DB Compat - do not use internally
	*/
	function Free()
	{
		return $this->Close();
	}
	
	
	/**
	* PEAR DB compat, number of rows
	*/
	function NumRows()
	{
		return $this->_numOfRows;
	}
	
	
	/**
	* PEAR DB compat, number of cols
	*/
	function NumCols()
	{
		return $this->_numOfFields;
	}
	
	/**
	* Fetch a row, returning false if no more rows. 
	* This is PEAR DB compat mode.
	*
	* @return false or array containing the current record
	*/
	function &FetchRow()
	{
		if ($this->EOF) return false;
		$arr = $this->fields;
		$this->_currentRow++;
		if (!$this->_fetch()) $this->EOF = true;
		return $arr;
	}
	
	
	/**
	* Fetch a row, returning PEAR_Error if no more rows. 
	* This is PEAR DB compat mode.
	*
	* @return DB_OK or error object
	*/
	function FetchInto(&$arr)
	{
		if ($this->EOF) return (defined('PEAR_ERROR_RETURN')) ? new PEAR_Error('EOF',-1): false;
		$arr = $this->fields;
		$this->MoveNext();
		return 1; // DB_OK
	}
	
	
	/**
	 * Move to the first row in the recordset. Many databases do NOT support this.
	 *
	 * @return true or false
	 */
	function MoveFirst() 
	{
		if ($this->_currentRow == 0) return true;
		return $this->Move(0);			
	}			

	
	/**
	 * Move to the last row in the recordset. 
	 *
	 * @return true or false
	 */
	function MoveLast() 
	{
		if ($this->_numOfRows >= 0) return $this->Move($this->_numOfRows-1);
		if ($this->EOF) return false;
		while (!$this->EOF) {
			$f = $this->fields;
			$this->MoveNext();
		}
		$this->fields = $f;
		$this->EOF = false;
		return true;
	}
	
	
	/**
	 * Move to next record in the recordset.
	 *
	 * @return true if there still rows available, or false if there are no more rows (EOF).
	 */
	function MoveNext() 
	{
		if (!$this->EOF) {
			$this->_currentRow++;
			if ($this->_fetch()) return true;
		}
		$this->EOF = true;
		/* -- tested error handling when scrolling cursor -- seems useless.
		$conn = $this->connection;
		if ($conn && $conn->raiseErrorFn && ($errno = $conn->ErrorNo())) {
			$fn = $conn->raiseErrorFn;
			$fn($conn->databaseType,'MOVENEXT',$errno,$conn->ErrorMsg().' ('.$this->sql.')',$conn->host,$conn->database);
		}
		*/
		return false;
	}	
	
	/**
	 * Random access to a specific row in the recordset. Some databases do not support
	 * access to previous rows in the databases (no scrolling backwards).
	 *
	 * @param rowNumber is the row to move to (0-based)
	 *
	 * @return true if there still rows available, or false if there are no more rows (EOF).
	 */
	function Move($rowNumber = 0) 
	{
		$this->EOF = false;
		if ($rowNumber == $this->_currentRow) return true;
		if ($rowNumber >= $this->_numOfRows)
	   		if ($this->_numOfRows != -1) $rowNumber = $this->_numOfRows-2;
  				
		if ($this->canSeek) { 
	
			if ($this->_seek($rowNumber)) {
				$this->_currentRow = $rowNumber;
				if ($this->_fetch()) {
					return true;
				}
			} else {
				$this->EOF = true;
				return false;
			}
		} else {
			if ($rowNumber < $this->_currentRow) return false;
			global $ADODB_EXTENSION;
			if ($ADODB_EXTENSION) {
				while (!$this->EOF && $this->_currentRow < $rowNumber) {
					adodb_movenext($this);
				}
			} else {
			
				while (! $this->EOF && $this->_currentRow < $rowNumber) {
					$this->_currentRow++;
					
					if (!$this->_fetch()) $this->EOF = true;
				}
			}
			return !($this->EOF);
		}
		
		$this->fields = false;	
		$this->EOF = true;
		return false;
	}
	
		
	/**
	 * Get the value of a field in the current row by column name.
	 * Will not work if ADODB_FETCH_MODE is set to ADODB_FETCH_NUM.
	 * 
	 * @param colname  is the field to access
	 *
	 * @return the value of $colname column
	 */
	function Fields($colname)
	{
		return $this->fields[$colname];
	}
	
	function GetAssocKeys($upper=true)
	{
		$this->bind = array();
		for ($i=0; $i < $this->_numOfFields; $i++) {
			$o =& $this->FetchField($i);
			if ($upper === 2) $this->bind[$o->name] = $i;
			else $this->bind[($upper) ? strtoupper($o->name) : strtolower($o->name)] = $i;
		}
	}
	
  /**
   * Use associative array to get fields array for databases that do not support
   * associative arrays. Submitted by Paolo S. Asioli paolo.asioli@libero.it
   *
   * If you don't want uppercase cols, set $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC
   * before you execute your SQL statement, and access $rs->fields['col'] directly.
   *
   * $upper  0 = lowercase, 1 = uppercase, 2 = whatever is returned by FetchField
   */
	function &GetRowAssoc($upper=1)
	{
		$record = array();
	 //	if (!$this->fields) return $record;
		
	   	if (!$this->bind) {
			$this->GetAssocKeys($upper);
		}
		
		foreach($this->bind as $k => $v) {
			$record[$k] = $this->fields[$v];
		}

		return $record;
	}
	
	
	/**
	 * Clean up recordset
	 *
	 * @return true or false
	 */
	function Close() 
	{
		// free connection object - this seems to globally free the object
		// and not merely the reference, so don't do this...
		// $this->connection = false; 
		if (!$this->_closed) {
			$this->_closed = true;
			return $this->_close();		
		} else
			return true;
	}
	
	/**
	 * synonyms RecordCount and RowCount	
	 *
	 * @return the number of rows or -1 if this is not supported
	 */
	function RecordCount() {return $this->_numOfRows;}
	
	
	/*
	* If we are using PageExecute(), this will return the maximum possible rows
	* that can be returned when paging a recordset.
	*/
	function MaxRecordCount()
	{
		return ($this->_maxRecordCount) ? $this->_maxRecordCount : $this->RecordCount();
	}
	
	/**
	 * synonyms RecordCount and RowCount	
	 *
	 * @return the number of rows or -1 if this is not supported
	 */
	function RowCount() {return $this->_numOfRows;} 
	

	 /**
	 * Portable RecordCount. Pablo Roca <pabloroca@mvps.org>
	 *
	 * @return  the number of records from a previous SELECT. All databases support this.
	 *
	 * But aware possible problems in multiuser environments. For better speed the table
	 * must be indexed by the condition. Heavy test this before deploying.
	 */ 
	function PO_RecordCount($table="", $condition="") {
		
		$lnumrows = $this->_numOfRows;
		// the database doesn't support native recordcount, so we do a workaround
		if ($lnumrows == -1 && $this->connection) {
			IF ($table) {
				if ($condition) $condition = " WHERE " . $condition; 
				$resultrows = &$this->connection->Execute("SELECT COUNT(*) FROM $table $condition");
				if ($resultrows) $lnumrows = reset($resultrows->fields);
			}
		}
		return $lnumrows;
	}
	
	/**
	 * @return the current row in the recordset. If at EOF, will return the last row. 0-based.
	 */
	function CurrentRow() {return $this->_currentRow;}
	
	/**
	 * synonym for CurrentRow -- for ADO compat
	 *
	 * @return the current row in the recordset. If at EOF, will return the last row. 0-based.
	 */
	function AbsolutePosition() {return $this->_currentRow;}
	
	/**
	 * @return the number of columns in the recordset. Some databases will set this to 0
	 * if no records are returned, others will return the number of columns in the query.
	 */
	function FieldCount() {return $this->_numOfFields;}   


	/**
	 * Get the ADOFieldObject of a specific column.
	 *
	 * @param fieldoffset	is the column position to access(0-based).
	 *
	 * @return the ADOFieldObject for that column, or false.
	 */
	function &FetchField($fieldoffset) 
	{
		// must be defined by child class
	}	
	
	/**
	 * Get the ADOFieldObjects of all columns in an array.
	 *
	 */
	function FieldTypesArray()
	{
		$arr = array();
		for ($i=0, $max=$this->_numOfFields; $i < $max; $i++) 
			$arr[] = $this->FetchField($i);
		return $arr;
	}
	
	/**
	* Return the fields array of the current row as an object for convenience.
	* The default case is lowercase field names.
	*
	* @return the object with the properties set to the fields of the current row
	*/
	function &FetchObj()
	{
		$o =& $this->FetchObject(false);
		return $o;
	}
	
	/**
	* Return the fields array of the current row as an object for convenience.
	* The default case is uppercase.
	* 
	* @param $isupper to set the object property names to uppercase
	*
	* @return the object with the properties set to the fields of the current row
	*/
	function &FetchObject($isupper=true)
	{
		if (empty($this->_obj)) {
			$this->_obj =& new ADOFetchObj();
			$this->_names = array();
			for ($i=0; $i <$this->_numOfFields; $i++) {
				$f = $this->FetchField($i);
				$this->_names[] = $f->name;
			}
		}
		$i = 0;
		$o = &$this->_obj;
		for ($i=0; $i <$this->_numOfFields; $i++) {
			$name = $this->_names[$i];
			if ($isupper) $n = strtoupper($name);
			else $n = $name;
			
			$o->$n = $this->Fields($name);
		}
		return $o;
	}
	
	/**
	* Return the fields array of the current row as an object for convenience.
	* The default is lower-case field names.
	* 
	* @return the object with the properties set to the fields of the current row,
	* 	or false if EOF
	*
	* Fixed bug reported by tim@orotech.net
	*/
	function &FetchNextObj()
	{
		return $this->FetchNextObject(false);
	}
	
	
	/**
	* Return the fields array of the current row as an object for convenience. 
	* The default is upper case field names.
	* 
	* @param $isupper to set the object property names to uppercase
	*
	* @return the object with the properties set to the fields of the current row,
	* 	or false if EOF
	*
	* Fixed bug reported by tim@orotech.net
	*/
	function &FetchNextObject($isupper=true)
	{
		$o = false;
		if ($this->_numOfRows != 0 && !$this->EOF) {
			$o = $this->FetchObject($isupper);	
			$this->_currentRow++;
			if ($this->_fetch()) return $o;
		}
		$this->EOF = true;
		return $o;
	}
	
	/**
	 * Get the metatype of the column. This is used for formatting. This is because
	 * many databases use different names for the same type, so we transform the original
	 * type to our standardised version which uses 1 character codes:
	 *
	 * @param t  is the type passed in. Normally is ADOFieldObject->type.
	 * @param len is the maximum length of that field. This is because we treat character
	 * 	fields bigger than a certain size as a 'B' (blob).
	 * @param fieldobj is the field object returned by the database driver. Can hold
	 *	additional info (eg. primary_key for mysql).
	 * 
	 * @return the general type of the data: 
	 *	C for character < 200 chars
	 *	X for teXt (>= 200 chars)
	 *	B for Binary
	 * 	N for numeric floating point
	 *	D for date
	 *	T for timestamp
	 * 	L for logical/Boolean
	 *	I for integer
	 *	R for autoincrement counter/integer
	 * 
	 *
	*/
	function MetaType($t,$len=-1,$fieldobj=false)
	{
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}
	// changed in 2.32 to hashing instead of switch stmt for speed...
	static $typeMap = array(
		'VARCHAR' => 'C',
		'VARCHAR2' => 'C',
		'CHAR' => 'C',
		'C' => 'C',
		'STRING' => 'C',
		'NCHAR' => 'C',
		'NVARCHAR' => 'C',
		'VARYING' => 'C',
		'BPCHAR' => 'C',
		'CHARACTER' => 'C',
		'INTERVAL' => 'C',  # Postgres
		##
		'LONGCHAR' => 'X',
		'TEXT' => 'X',
		'NTEXT' => 'X',
		'M' => 'X',
		'X' => 'X',
		'CLOB' => 'X',
		'NCLOB' => 'X',
		'LVARCHAR' => 'X',
		##
		'BLOB' => 'B',
		'IMAGE' => 'B',
		'BINARY' => 'B',
		'VARBINARY' => 'B',
		'LONGBINARY' => 'B',
		'B' => 'B',
		##
		'YEAR' => 'D', // mysql
		'DATE' => 'D',
		'D' => 'D',
		##
		'TIME' => 'T',
		'TIMESTAMP' => 'T',
		'DATETIME' => 'T',
		'TIMESTAMPTZ' => 'T',
		'T' => 'T',
		##
		'BOOL' => 'L',
		'BOOLEAN' => 'L', 
		'BIT' => 'L',
		'L' => 'L',
		##
		'COUNTER' => 'R',
		'R' => 'R',
		'SERIAL' => 'R', // ifx
		'INT IDENTITY' => 'R',
		##
		'INT' => 'I',
		'INTEGER' => 'I',
		'INTEGER UNSIGNED' => 'I',
		'SHORT' => 'I',
		'TINYINT' => 'I',
		'SMALLINT' => 'I',
		'I' => 'I',
		##
		'LONG' => 'N', // interbase is numeric, oci8 is blob
		'BIGINT' => 'N', // this is bigger than PHP 32-bit integers
		'DECIMAL' => 'N',
		'DEC' => 'N',
		'REAL' => 'N',
		'DOUBLE' => 'N',
		'DOUBLE PRECISION' => 'N',
		'SMALLFLOAT' => 'N',
		'FLOAT' => 'N',
		'NUMBER' => 'N',
		'NUM' => 'N',
		'NUMERIC' => 'N',
		'MONEY' => 'N',
		
		## informix 9.2
		'SQLINT' => 'I', 
		'SQLSERIAL' => 'I', 
		'SQLSMINT' => 'I', 
		'SQLSMFLOAT' => 'N', 
		'SQLFLOAT' => 'N', 
		'SQLMONEY' => 'N', 
		'SQLDECIMAL' => 'N', 
		'SQLDATE' => 'D', 
		'SQLVCHAR' => 'C', 
		'SQLCHAR' => 'C', 
		'SQLDTIME' => 'T', 
		'SQLINTERVAL' => 'N', 
		'SQLBYTES' => 'B', 
		'SQLTEXT' => 'X' 
		);
		
		$tmap = false;
		$t = strtoupper($t);
		$tmap = @$typeMap[$t];
		switch ($tmap) {
		case 'C':
		
			// is the char field is too long, return as text field... 
			if ($this->blobSize >= 0) {
				if ($len > $this->blobSize) return 'X';
			} else if ($len > 250) {
				return 'X';
			}
			return 'C';
			
		case 'I':
			if (!empty($fieldobj->primary_key)) return 'R';
			return 'I';
		
		case false:
			return 'N';
			
		case 'B':
			 if (isset($fieldobj->binary)) 
				 return ($fieldobj->binary) ? 'B' : 'X';
			return 'B';
		
		case 'D':
			if (!empty($this->datetime)) return 'T';
			return 'D';
			
		default: 
			if ($t == 'LONG' && $this->dataProvider == 'oci8') return 'B';
			return $tmap;
		}
	}
	
	function _close() {}
	
	/**
	 * set/returns the current recordset page when paginating
	 */
	function AbsolutePage($page=-1)
	{
		if ($page != -1) $this->_currentPage = $page;
		return $this->_currentPage;
	}
	
	/**
	 * set/returns the status of the atFirstPage flag when paginating
	 */
	function AtFirstPage($status=false)
	{
		if ($status != false) $this->_atFirstPage = $status;
		return $this->_atFirstPage;
	}
	
	function LastPageNo($page = false)
	{
		if ($page != false) $this->_lastPageNo = $page;
		return $this->_lastPageNo;
	}
	
	/**
	 * set/returns the status of the atLastPage flag when paginating
	 */
	function AtLastPage($status=false)
	{
		if ($status != false) $this->_atLastPage = $status;
		return $this->_atLastPage;
	}
	
} // end class ADORecordSet
	
	//==============================================================================================	
	// CLASS ADORecordSet_array
	//==============================================================================================	
	
	/**
	 * This class encapsulates the concept of a recordset created in memory
	 * as an array. This is useful for the creation of cached recordsets.
	 * 
	 * Note that the constructor is different from the standard ADORecordSet
	 */
	
	class ADORecordSet_array extends ADORecordSet
	{
		var $databaseType = 'array';

		var $_array; 	// holds the 2-dimensional data array
		var $_types;	// the array of types of each column (C B I L M)
		var $_colnames;	// names of each column in array
		var $_skiprow1;	// skip 1st row because it holds column names
		var $_fieldarr; // holds array of field objects
		var $canSeek = true;
		var $affectedrows = false;
		var $insertid = false;
		var $sql = '';
		var $compat = false;
		/**
		 * Constructor
		 *
		 */
		function ADORecordSet_array($fakeid=1)
		{
		global $ADODB_FETCH_MODE,$ADODB_COMPAT_FETCH;
		
			// fetch() on EOF does not delete $this->fields
			$this->compat = !empty($ADODB_COMPAT_FETCH);
			$this->ADORecordSet($fakeid); // fake queryID		
			$this->fetchMode = $ADODB_FETCH_MODE;
		}
		
		
		/**
		 * Setup the array.
		 *
		 * @param array		is a 2-dimensional array holding the data.
		 *			The first row should hold the column names 
		 *			unless paramter $colnames is used.
		 * @param typearr	holds an array of types. These are the same types 
		 *			used in MetaTypes (C,B,L,I,N).
		 * @param [colnames]	array of column names. If set, then the first row of
		 *			$array should not hold the column names.
		 */
		function InitArray($array,$typearr,$colnames=false)
		{
			$this->_array = $array;
			$this->_types = $typearr;	
			if ($colnames) {
				$this->_skiprow1 = false;
				$this->_colnames = $colnames;
			} else  {
				$this->_skiprow1 = true;
				$this->_colnames = $array[0];
			}
			$this->Init();
		}
		/**
		 * Setup the Array and datatype file objects
		 *
		 * @param array		is a 2-dimensional array holding the data.
		 *			The first row should hold the column names 
		 *			unless paramter $colnames is used.
		 * @param fieldarr	holds an array of ADOFieldObject's.
		 */
		function InitArrayFields(&$array,&$fieldarr)
		{
			$this->_array =& $array;
			$this->_skiprow1= false;
			if ($fieldarr) {
				$this->_fieldobjects =& $fieldarr;
			} 
			$this->Init();
		}
		
		function &GetArray($nRows=-1)
		{
			if ($nRows == -1 && $this->_currentRow <= 0 && !$this->_skiprow1) {
				return $this->_array;
			} else {
				$arr =& ADORecordSet::GetArray($nRows);
				return $arr;
			}
		}
		
		function _initrs()
		{
			$this->_numOfRows =  sizeof($this->_array);
			if ($this->_skiprow1) $this->_numOfRows -= 1;
		
			$this->_numOfFields =(isset($this->_fieldobjects)) ?
				 sizeof($this->_fieldobjects):sizeof($this->_types);
		}
		
		/* Use associative array to get fields array */
		function Fields($colname)
		{
			if ($this->fetchMode & ADODB_FETCH_ASSOC) return $this->fields[$colname];
	
			if (!$this->bind) {
				$this->bind = array();
				for ($i=0; $i < $this->_numOfFields; $i++) {
					$o = $this->FetchField($i);
					$this->bind[strtoupper($o->name)] = $i;
				}
			}
			return $this->fields[$this->bind[strtoupper($colname)]];
		}
		
		function &FetchField($fieldOffset = -1) 
		{
			if (isset($this->_fieldobjects)) {
				return $this->_fieldobjects[$fieldOffset];
			}
			$o =  new ADOFieldObject();
			$o->name = $this->_colnames[$fieldOffset];
			$o->type =  $this->_types[$fieldOffset];
			$o->max_length = -1; // length not known
			
			return $o;
		}
			
		function _seek($row)
		{
			if (sizeof($this->_array) && 0 <= $row && $row < $this->_numOfRows) {
				$this->_currentRow = $row;
				if ($this->_skiprow1) $row += 1;
				$this->fields = $this->_array[$row];
				return true;
			}
			return false;
		}
		
		function MoveNext() 
		{
			if (!$this->EOF) {		
				$this->_currentRow++;
				
				$pos = $this->_currentRow;
				
				if ($this->_numOfRows <= $pos) {
					if (!$this->compat) $this->fields = false;
				} else {
					if ($this->_skiprow1) $pos += 1;
					$this->fields = $this->_array[$pos];
					return true;
				}		
				$this->EOF = true;
			}
			
			return false;
		}	
	
		function _fetch()
		{
			$pos = $this->_currentRow;
			
			if ($this->_numOfRows <= $pos) {
				if (!$this->compat) $this->fields = false;
				return false;
			}
			if ($this->_skiprow1) $pos += 1;
			$this->fields = $this->_array[$pos];
			return true;
		}
		
		function _close() 
		{
			return true;	
		}
	
	} // ADORecordSet_array

	//==============================================================================================	
	// HELPER FUNCTIONS
	//==============================================================================================			
	
	/**
	 * Synonym for ADOLoadCode. Private function. Do not use.
	 *
	 * @deprecated
	 */
	function ADOLoadDB($dbType) 
	{ 
		return ADOLoadCode($dbType);
	}
		
	/**
	 * Load the code for a specific database driver. Private function. Do not use.
	 */
	function ADOLoadCode($dbType) 
	{
	global $ADODB_LASTDB;
	
		if (!$dbType) return false;
		$db = strtolower($dbType);
		switch ($db) {
			case 'maxsql': $db = 'mysqlt'; break;
			case 'postgres':
			case 'pgsql': $db = 'postgres7'; break;
		}
		@include_once(ADODB_DIR."/drivers/adodb-".$db.".inc.php");
		$ADODB_LASTDB = $db;
		
		$ok = class_exists("ADODB_" . $db);
		if ($ok) return $db;
		
		$file = ADODB_DIR."/drivers/adodb-".$db.".inc.php";
		if (!file_exists($file)) ADOConnection::outp("Missing file: $file");
		else ADOConnection::outp("Syntax error in file: $file");
		return false;
	}

	/**
	 * synonym for ADONewConnection for people like me who cannot remember the correct name
	 */
	function &NewADOConnection($db='')
	{
		$tmp =& ADONewConnection($db);
		return $tmp;
	}
	
	/**
	 * Instantiate a new Connection class for a specific database driver.
	 *
	 * @param [db]  is the database Connection object to create. If undefined,
	 * 	use the last database driver that was loaded by ADOLoadCode().
	 *
	 * @return the freshly created instance of the Connection class.
	 */
	function &ADONewConnection($db='')
	{
	GLOBAL $ADODB_NEWCONNECTION, $ADODB_LASTDB;
		
		if (!defined('ADODB_ASSOC_CASE')) define('ADODB_ASSOC_CASE',2);
		$errorfn = (defined('ADODB_ERROR_HANDLER')) ? ADODB_ERROR_HANDLER : false;
		
		if (!empty($ADODB_NEWCONNECTION)) {
			$obj = $ADODB_NEWCONNECTION($db);
			if ($obj) {
				if ($errorfn)  $obj->raiseErrorFn = $errorfn;
				return $obj;
			}
		}
		
		if (!isset($ADODB_LASTDB)) $ADODB_LASTDB = '';
		if (empty($db)) $db = $ADODB_LASTDB;
		
		if ($db != $ADODB_LASTDB) $db = ADOLoadCode($db);
		
		if (!$db) {
			 if ($errorfn) {
				// raise an error
				$ignore = false;
				$errorfn('ADONewConnection', 'ADONewConnection', -998,
						 "could not load the database driver for '$db",
						 $db,false,$ignore);
			} else
				 ADOConnection::outp( "<p>ADONewConnection: Unable to load database driver '$db'</p>",false);
				
			return false;
		}
		
		$cls = 'ADODB_'.$db;
		if (!class_exists($cls)) {
			adodb_backtrace();
			return false;
		}
		
		$obj =& new $cls();
		if ($errorfn) $obj->raiseErrorFn = $errorfn;
		
		return $obj;
	}
	
	// $perf == true means called by NewPerfMonitor()
	function _adodb_getdriver($provider,$drivername,$perf=false)
	{
		if ($provider !== 'native' && $provider != 'odbc' && $provider != 'ado') 
			$drivername = $provider;
		else {
			if (substr($drivername,0,5) == 'odbc_') $drivername = substr($drivername,5);
			else if (substr($drivername,0,4) == 'ado_') $drivername = substr($drivername,4);
			else 
			switch($drivername) {
			case 'oracle': $drivername = 'oci8';break;
			//case 'sybase': $drivername = 'mssql';break;
			case 'access': 
						if ($perf) $drivername = '';
						break;
			case 'db2':	
						break;
			default:
				$drivername = 'generic';
				break;
			}
		}
		
		return $drivername;
	}
	
	function &NewPerfMonitor(&$conn)
	{
		$drivername = _adodb_getdriver($conn->dataProvider,$conn->databaseType,true);
		if (!$drivername || $drivername == 'generic') return false;
		include_once(ADODB_DIR.'/adodb-perf.inc.php');
		@include_once(ADODB_DIR."/perf/perf-$drivername.inc.php");
		$class = "Perf_$drivername";
		if (!class_exists($class)) return false;
		$perf =& new $class($conn);
		
		return $perf;
	}
	
	function &NewDataDictionary(&$conn)
	{
		$drivername = _adodb_getdriver($conn->dataProvider,$conn->databaseType);
		
		include_once(ADODB_DIR.'/adodb-lib.inc.php');
		include_once(ADODB_DIR.'/adodb-datadict.inc.php');
		$path = ADODB_DIR."/datadict/datadict-$drivername.inc.php";

		if (!file_exists($path)) {
			ADOConnection::outp("Database driver '$path' not available");
			return false;
		}
		include_once($path);
		$class = "ADODB2_$drivername";
		$dict =& new $class();
		$dict->dataProvider = $conn->dataProvider;
		$dict->connection = &$conn;
		$dict->upperName = strtoupper($drivername);
		$dict->quote = $conn->nameQuote;
		if (is_resource($conn->_connectionID))
			$dict->serverInfo = $conn->ServerInfo();
		
		return $dict;
	}


	/**
	* Save a file $filename and its $contents (normally for caching) with file locking
	*/
	function adodb_write_file($filename, $contents,$debug=false)
	{ 
	# http://www.php.net/bugs.php?id=9203 Bug that flock fails on Windows
	# So to simulate locking, we assume that rename is an atomic operation.
	# First we delete $filename, then we create a $tempfile write to it and 
	# rename to the desired $filename. If the rename works, then we successfully 
	# modified the file exclusively.
	# What a stupid need - having to simulate locking.
	# Risks:
	# 1. $tempfile name is not unique -- very very low
	# 2. unlink($filename) fails -- ok, rename will fail
	# 3. adodb reads stale file because unlink fails -- ok, $rs timeout occurs
	# 4. another process creates $filename between unlink() and rename() -- ok, rename() fails and  cache updated
		if (strncmp(PHP_OS,'WIN',3) === 0) {
			// skip the decimal place
			$mtime = substr(str_replace(' ','_',microtime()),2); 
			// getmypid() actually returns 0 on Win98 - never mind!
			$tmpname = $filename.uniqid($mtime).getmypid();
			if (!($fd = fopen($tmpname,'a'))) return false;
			$ok = ftruncate($fd,0);			
			if (!fwrite($fd,$contents)) $ok = false;
			fclose($fd);
			chmod($tmpname,0644);
			// the tricky moment
			@unlink($filename);
			if (!@rename($tmpname,$filename)) {
				unlink($tmpname);
				$ok = false;
			}
			if (!$ok) {
				if ($debug) ADOConnection::outp( " Rename $tmpname ".($ok? 'ok' : 'failed'));
			}
			return $ok;
		}
		if (!($fd = fopen($filename, 'a'))) return false;
		if (flock($fd, LOCK_EX) && ftruncate($fd, 0)) {
			$ok = fwrite( $fd, $contents );
			fclose($fd);
			chmod($filename,0644);
		}else {
			fclose($fd);
			if ($debug)ADOConnection::outp( " Failed acquiring lock for $filename<br>\n");
			$ok = false;
		}
	
		return $ok;
	}
	
	/*
		Perform a print_r, with pre tags for better formatting.
	*/
	function adodb_pr($var)
	{
		if (isset($_SERVER['HTTP_USER_AGENT'])) { 
			echo " <pre>\n";print_r($var);echo "</pre>\n";
		} else
			print_r($var);
	}
	
	/*
		Perform a stack-crawl and pretty print it.
		
		@param printOrArr  Pass in a boolean to indicate print, or an $exception->trace array (assumes that print is true then).
		@param levels Number of levels to display
	*/
	function adodb_backtrace($printOrArr=true,$levels=9999)
	{
		$s = '';
		if (PHPVERSION() < 4.3) return;
		 
		$html =  (isset($_SERVER['HTTP_USER_AGENT']));
		$fmt =  ($html) ? "</font><font color=#808080 size=-1> %% line %4d, file: <a href=\"file:/%s\">%s</a></font>" : "%% line %4d, file: %s";

		$MAXSTRLEN = 64;
	
		$s = ($html) ? '<pre align=left>' : '';
		
		if (is_array($printOrArr)) $traceArr = $printOrArr;
		else $traceArr = debug_backtrace();
		array_shift($traceArr);
		$tabs = sizeof($traceArr)-1;
		
		foreach ($traceArr as $arr) {
			$levels -= 1;
			if ($levels < 0) break;
			
			$args = array();
			for ($i=0; $i < $tabs; $i++) $s .=  ($html) ? ' &nbsp; ' : "\t";
			$tabs -= 1;
			if ($html) $s .= '<font face="Courier New,Courier">';
			if (isset($arr['class'])) $s .= $arr['class'].'.';
			if (isset($arr['args']))
			 foreach($arr['args'] as $v) {
				if (is_null($v)) $args[] = 'null';
				else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
				else if (is_object($v)) $args[] = 'Object:'.get_class($v);
				else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
				else {
					$v = (string) @$v;
					$str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
					if (strlen($v) > $MAXSTRLEN) $str .= '...';
					$args[] = $str;
				}
			}
			$s .= $arr['function'].'('.implode(', ',$args).')';
			
			
			$s .= @sprintf($fmt, $arr['line'],$arr['file'],basename($arr['file']));
				
			$s .= "\n";
		}	
		if ($html) $s .= '</pre>';
		if ($printOrArr) print $s;
		
		return $s;
	}
	
} // defined

// For emacs users
// Local Variables:
// mode: php
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>