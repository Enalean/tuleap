<?php
/*
V4.22 15 Apr 2004  (c) 2000-2004 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence. See License.txt.
  Set tabs to 4 for best viewing.
  Latest version is available at http://php.weblogs.com/
*/
// Code contributed by "stefan bogdan" <sbogdan#rsb.ro>

define("_ADODB_ODBTP_LAYER", 2 );

class ADODB_odbtp extends ADOConnection{
	var $databaseType = "odbtp";
	var $dataProvider = "odbtp";
	var $fmtDate = "'Y-m-d'";
	var $fmtTimeStamp = "'Y-m-d, h:i:sA'";
	var $replaceQuote = "''"; // string to use to replace quotes
	var $odbc_driver = 0;
	var $hasAffectedRows = true;
	var $hasInsertID = false;
	var $hasGenID = true;
	var $hasMoveFirst = true;

	var $_genSeqSQL = "create table %s (seq_name char(30) not null unique , seq_value integer not null)";
	var $_dropSeqSQL = "delete from adodb_seq where seq_name = '%s'";
	var $_autocommit = true;
	var $_bindInputArray = false;
	var $_useUnicodeSQL = false;
	var $_canPrepareSP = false;

	function ADODB_odbtp()
	{
	}

	function ServerInfo()
	{
		return array('description' => @odbtp_get_attr( ODB_ATTR_DBMSNAME, $this->_connectionID),
		             'version' => @odbtp_get_attr( ODB_ATTR_DBMSVER, $this->_connectionID));
	}

	function ErrorMsg()
	{
		if (empty($this->_connectionID)) return @odbtp_last_error();
		return @odbtp_last_error($this->_connectionID);
	}

	function ErrorNo()
	{
		if (empty($this->_connectionID)) return @odbtp_last_error_state();
			return @odbtp_last_error_state($this->_connectionID);
	}

	function _insertid()
	{
	// SCOPE_IDENTITY()
	// Returns the last IDENTITY value inserted into an IDENTITY column in
	// the same scope. A scope is a module -- a stored procedure, trigger,
	// function, or batch. Thus, two statements are in the same scope if
	// they are in the same stored procedure, function, or batch.
			return $this->GetOne($this->identitySQL);
	}

	function _affectedrows()
	{
		if ($this->_queryID) {
			return @odbtp_affected_rows ($this->_queryID);
	   } else
		return 0;
	}

	function CreateSequence($seqname='adodbseq',$start=1)
	{
		//verify existence
		$num = $this->GetOne("select seq_value from adodb_seq");
		$seqtab='adodb_seq';
		if( $this->odbc_driver == ODB_DRIVER_FOXPRO ) {
			$path = @odbtp_get_attr( ODB_ATTR_DATABASENAME, $this->_connectionID );
			//if using vfp dbc file
			if( !strcasecmp(strrchr($path, '.'), '.dbc') )
                $path = substr($path,0,strrpos($path,'\/'));
           	$seqtab = $path . '/' . $seqtab;
        }
		if($num == false) {
			if (empty($this->_genSeqSQL)) return false;
			$ok = $this->Execute(sprintf($this->_genSeqSQL ,$seqtab));
		}
		$num = $this->GetOne("select seq_value from adodb_seq where seq_name='$seqname'");
		if ($num) {
			return false;
		}
		$start -= 1;
		return $this->Execute("insert into adodb_seq values('$seqname',$start)");
	}

	function DropSequence($seqname)
	{
		if (empty($this->_dropSeqSQL)) return false;
		return $this->Execute(sprintf($this->_dropSeqSQL,$seqname));
	}

	function GenID($seq='adodbseq',$start=1)
	{
		$seqtab='adodb_seq';
		if( $this->odbc_driver == ODB_DRIVER_FOXPRO ) {
			$path = @odbtp_get_attr( ODB_ATTR_DATABASENAME, $this->_connectionID );
			//if using vfp dbc file
			if( !strcasecmp(strrchr($path, '.'), '.dbc') )
                $path = substr($path,0,strrpos($path,'\/'));
           	$seqtab = $path . '/' . $seqtab;
        }
		$MAXLOOPS = 100;
		while (--$MAXLOOPS>=0) {
			$num = $this->GetOne("select seq_value from adodb_seq where seq_name='$seq'");
			if ($num === false) {
				//verify if abodb_seq table exist
				$ok = $this->GetOne("select seq_value from adodb_seq ");
				if(!$ok) {
					//creating the sequence table adodb_seq
					$this->Execute(sprintf($this->_genSeqSQL ,$seqtab));
				}
				$start -= 1;
				$num = '0';
				$ok = $this->Execute("insert into adodb_seq values('$seq',$start)");
				if (!$ok) return false;
			}
			$ok = $this->Execute("update adodb_seq set seq_value=seq_value+1 where seq_name='$seq'");
			if($ok) {
				$num += 1;
				$this->genID = $num;
				return $num;
			}
		}
	if ($fn = $this->raiseErrorFn) {
		$fn($this->databaseType,'GENID',-32000,"Unable to generate unique id after $MAXLOOPS attempts",$seq,$num);
	}
		return false;
	}

	//example for $UserOrDSN
	//for visual fox : DRIVER={Microsoft Visual FoxPro Driver};SOURCETYPE=DBF;SOURCEDB=c:\YourDbfFileDir;EXCLUSIVE=NO;
	//for visual fox dbc: DRIVER={Microsoft Visual FoxPro Driver};SOURCETYPE=DBC;SOURCEDB=c:\YourDbcFileDir\mydb.dbc;EXCLUSIVE=NO;
	//for access : DRIVER={Microsoft Access Driver (*.mdb)};DBQ=c:\path_to_access_db\base_test.mdb;UID=root;PWD=;
	//for mssql : DRIVER={SQL Server};SERVER=myserver;UID=myuid;PWD=mypwd;DATABASE=OdbtpTest;
	//if uid & pwd can be separate
    function _connect($HostOrInterface, $UserOrDSN='', $argPassword='', $argDatabase='')
	{
		$this->_connectionID = @odbtp_connect($HostOrInterface,$UserOrDSN,$argPassword,$argDatabase);
		if ($this->_connectionID === false)
		{
			$this->_errorMsg = $this->ErrorMsg() ;
			return false;
		}
		$this->odbc_driver = @odbtp_get_attr(ODB_ATTR_DRIVER, $this->_connectionID);

		// Set driver specific attributes
		switch( $this->odbc_driver ) {
			case ODB_DRIVER_MSSQL:
				$this->fmtDate = "'Y-m-d'";
				$this->fmtTimeStamp = "'Y-m-d h:i:sA'";
				$this->sysDate = 'convert(datetime,convert(char,GetDate(),102),102)';
				$this->sysTimeStamp = 'GetDate()';
				$this->ansiOuter = true;
				$this->leftOuter = '*=';
				$this->rightOuter = '=*';
                $this->hasTop = 'top';
				$this->hasInsertID = true;
				$this->hasTransactions = true;
				$this->_bindInputArray = true;
				$this->_canSelectDb = true;
				$this->substr = "substring";
				$this->length = 'len';
				$this->upperCase = 'upper';
				$this->identitySQL = 'select @@IDENTITY';
				$this->metaDatabasesSQL = "select name from master..sysdatabases where name <> 'master'";
				break;
			case ODB_DRIVER_JET:
				$this->fmtDate = "#Y-m-d#";
				$this->fmtTimeStamp = "#Y-m-d h:i:sA#";
				$this->sysDate = "FORMAT(NOW,'yyyy-mm-dd')";
				$this->sysTimeStamp = 'NOW';
                $this->hasTop = 'top';
				$this->hasTransactions = false;
				$this->_canPrepareSP = true;  // For MS Access only.

				// Can't rebind ODB_CHAR to ODB_WCHAR if row cache enabled.
				if ($this->_useUnicodeSQL)
					odbtp_use_row_cache($this->_connectionID, FALSE, 0);
				break;
			case ODB_DRIVER_FOXPRO:
				$this->fmtDate = "{^Y-m-d}";
				$this->fmtTimeStamp = "{^Y-m-d, h:i:sA}";
				$this->sysDate = 'date()';
				$this->sysTimeStamp = 'datetime()';
				$this->ansiOuter = true;
                $this->hasTop = 'top';
			$this->hasTransactions = false;
				$this->replaceQuote = "'+chr(39)+'";
				$this->true = '.T.';
				$this->false = '.F.';
				$this->upperCase = 'upper';
				break;
			case ODB_DRIVER_ORACLE:
				$this->fmtDate = "'Y-m-d 00:00:00'";
				$this->fmtTimeStamp = "'Y-m-d h:i:sA'";
				$this->sysDate = 'TRUNC(SYSDATE)';
				$this->sysTimeStamp = 'SYSDATE';
				$this->hasTransactions = true;
				$this->_bindInputArray = true;
				$this->concat_operator = '||';
				break;
			case ODB_DRIVER_SYBASE:
				$this->fmtDate = "'Y-m-d'";
				$this->fmtTimeStamp = "'Y-m-d H:i:s'";
				$this->sysDate = 'GetDate()';
				$this->sysTimeStamp = 'GetDate()';
				$this->leftOuter = '*=';
				$this->rightOuter = '=*';
				$this->hasInsertID = true;
				$this->hasTransactions = true;
				$this->upperCase = 'upper';
				$this->identitySQL = 'select @@IDENTITY';
				break;
			default:
				if( @odbtp_get_attr(ODB_ATTR_TXNCAPABLE, $this->_connectionID) )
			$this->hasTransactions = true;
				else
					$this->hasTransactions = false;
		}
        @odbtp_set_attr(ODB_ATTR_FULLCOLINFO, TRUE, $this->_connectionID );
		if ($this->_useUnicodeSQL )
			@odbtp_set_attr(ODB_ATTR_UNICODESQL, TRUE, $this->_connectionID);
        return true;
	}
	
	function _pconnect($HostOrInterface, $UserOrDSN='', $argPassword='', $argDatabase='')
	{
  		return $this->_connect($HostOrInterface, $UserOrDSN, $argPassword, $argDatabase);
	}
	
	function SelectDB($dbName)
	{
		if (!@odbtp_select_db($dbName, $this->_connectionID)) {
			return false;
		}
		$this->databaseName = $dbName;
		return true;
	}
	
	function &MetaTables($ttype='',$showSchema=false,$mask=false)
	{
	global $ADODB_FETCH_MODE;

		$savem = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		$arr =& $this->GetArray("||SQLTables||||$ttype");
		$ADODB_FETCH_MODE = $savem;

		$arr2 = array();
		for ($i=0; $i < sizeof($arr); $i++) {
			if ($arr[$i][3] == 'SYSTEM TABLE' )	continue;
			if ($arr[$i][2])
				$arr2[] = $showSchema ? $arr[$i][1].'.'.$arr[$i][2] : $arr[$i][2];
		}
		return $arr2;
	}
	
	function &MetaColumns($table,$upper=true)
	{
	global $ADODB_FETCH_MODE;

		$schema = false;
		$this->_findschema($table,$schema);
		if ($upper) $table = strtoupper($table);

		$savem = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		$rs = $this->Execute( "||SQLColumns||$schema|$table" );
		$ADODB_FETCH_MODE = $savem;

		if (!$rs) return false;
		
		while (!$rs->EOF) {
			//print_r($rs->fields);
			if (strtoupper($rs->fields[2]) == $table) {
				$fld = new ADOFieldObject();
				$fld->name = $rs->fields[3];
				$fld->type = $rs->fields[5];
				$fld->max_length = $rs->fields[6];
    			$fld->not_null = !empty($rs->fields[9]);
 				$fld->scale = $rs->fields[7];
 				if (!is_null($rs->fields[12])) {
 					$fld->has_default = true;
 					$fld->default_value = $rs->fields[12];
				}
				$retarr[strtoupper($fld->name)] = $fld;
			} else if (sizeof($retarr)>0)
				break;
			$rs->MoveNext();
		}
		$rs->Close(); 

		return $retarr;
	}

	function &MetaPrimaryKeys($table, $owner='')
	{
	global $ADODB_FETCH_MODE;

		$savem = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		$arr =& $this->GetArray("||SQLPrimaryKeys||$owner|$table");
		$ADODB_FETCH_MODE = $savem;

		//print_r($arr);
		$arr2 = array();
		for ($i=0; $i < sizeof($arr); $i++) {
			if ($arr[$i][3]) $arr2[] = $arr[$i][3];
		}
		return $arr2;
	}

	function &MetaForeignKeys($table, $owner='', $upper=false)
	{
	global $ADODB_FETCH_MODE;

		$savem = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
		$constraints =& $this->GetArray("||SQLForeignKeys|||||$owner|$table");
		$ADODB_FETCH_MODE = $savem;

		$arr = false;
		foreach($constraints as $constr) {
			//print_r($constr);
			$arr[$constr[11]][$constr[2]][] = $constr[7].'='.$constr[3];
		}
		if (!$arr) return false;

		$arr2 = array();

		foreach($arr as $k => $v) {
			foreach($v as $a => $b) {
				if ($upper) $a = strtoupper($a);
				$arr2[$a] = $b;
			}
		}
		return $arr2;
	}

	function BeginTrans()
	{
		if (!$this->hasTransactions) return false;
		if ($this->transOff) return true;
		$this->transCnt += 1;
		$this->_autocommit = false;
		$rs = @odbtp_set_attr(ODB_ATTR_TRANSACTIONS,ODB_TXN_READUNCOMMITTED,$this->_connectionID);
		if(!$rs) return false;
		else return true;
	}

	function CommitTrans($ok=true)
	{
		if ($this->transOff) return true;
		if (!$ok) return $this->RollbackTrans();
		if ($this->transCnt) $this->transCnt -= 1;
		$this->_autocommit = true;
		if( ($ret = odbtp_commit($this->_connectionID)) )
			$ret = @odbtp_set_attr(ODB_ATTR_TRANSACTIONS, ODB_TXN_NONE, $this->_connectionID);//set transaction off
		return $ret;
	}

	function RollbackTrans()
	{
		if ($this->transOff) return true;
		if ($this->transCnt) $this->transCnt -= 1;
		$this->_autocommit = true;
		if( ($ret = odbtp_rollback($this->_connectionID)) )
			$ret = @odbtp_set_attr(ODB_ATTR_TRANSACTIONS, ODB_TXN_NONE, $this->_connectionID);//set transaction off
		return $ret;
	}

	function &SelectLimit($sql,$nrows=-1,$offset=-1, $inputarr=false,$secs2cache=0)
	{
		// TOP requires ORDER BY for Visual FoxPro
		if( $this->odbc_driver == ODB_DRIVER_FOXPRO ) {
			if (!preg_match('/ORDER[ \t\r\n]+BY/is',$sql)) $sql .= ' ORDER BY 1';
		}
		return ADOConnection::SelectLimit($sql,$nrows,$offset,$inputarr,$secs2cache);
	}

	function Prepare($sql)
	{
		if (! $this->_bindInputArray) return $sql; // no binding
		$stmt = odbtp_prepare($sql,$this->_connectionID);
		if (!$stmt) {
		//	print "Prepare Error for ($sql) ".$this->ErrorMsg()."<br>";
			return $sql;
		}
		return array($sql,$stmt,false);
	}

	function PrepareSP($sql)
	{
		if (!$this->_canPrepareSP) return $sql; // Can't prepare procedures

		$stmt = odbtp_prepare_proc($sql,$this->_connectionID);
		if (!$stmt) return false;
		return array($sql,$stmt);
	}

	/*
	Usage:
		$stmt = $db->PrepareSP('SP_RUNSOMETHING'); -- takes 2 params, @myid and @group

		# note that the parameter does not have @ in front!
		$db->Parameter($stmt,$id,'myid');
		$db->Parameter($stmt,$group,'group',false,64);
		$db->Parameter($stmt,$group,'photo',false,100000,ODB_BINARY);
		$db->Execute($stmt);

		@param $stmt Statement returned by Prepare() or PrepareSP().
		@param $var PHP variable to bind to. Can set to null (for isNull support).
		@param $name Name of stored procedure variable name to bind to.
		@param [$isOutput] Indicates direction of parameter 0/false=IN  1=OUT  2= IN/OUT. This is ignored in odbtp.
		@param [$maxLen] Holds an maximum length of the variable.
		@param [$type] The data type of $var. Legal values depend on driver.

		See odbtp_attach_param documentation at http://odbtp.sourceforge.net.
	*/
	function Parameter(&$stmt, &$var, $name, $isOutput=false, $maxLen=0, $type=0)
	{
		if ( $this->odbc_driver == ODB_DRIVER_JET ) {
			$name = '['.$name.']';
			if( !$type && $this->_useUnicodeSQL
				&& @odbtp_param_bindtype($stmt[1], $name) == ODB_CHAR )
			{
				$type = ODB_WCHAR;
			}
		}
		else {
			$name = '@'.$name;
		}
		return odbtp_attach_param($stmt[1], $name, $var, $type, $maxLen);
	}

	/*
		Insert a null into the blob field of the table first.
		Then use UpdateBlob to store the blob.

		Usage:

		$conn->Execute('INSERT INTO blobtable (id, blobcol) VALUES (1, null)');
		$conn->UpdateBlob('blobtable','blobcol',$blob,'id=1');
	*/

	function UpdateBlob($table,$column,$val,$where,$blobtype='image')
	{
		$sql = "UPDATE $table SET $column = ? WHERE $where";
		if( !($stmt = odbtp_prepare($sql, $this->_connectionID)) )
			return false;
		if( !odbtp_input( $stmt, 1, ODB_BINARY, 1000000, $blobtype ) )
			return false;
		if( !odbtp_set( $stmt, 1, $val ) )
			return false;
		return odbtp_execute( $stmt ) != false;
	}

	function IfNull( $field, $ifNull )
	{
		switch( $this->odbc_driver ) {
			case ODB_DRIVER_MSSQL:
				return " ISNULL($field, $ifNull) ";
			case ODB_DRIVER_JET:
				return " IIF(IsNull($field), $ifNull, $field) ";
		}
		return " CASE WHEN $field is null THEN $ifNull ELSE $field END ";
	}

	function _query($sql,$inputarr=false)
	{
 		if ($inputarr) {
			if (is_array($sql)) {
				$stmtid = $sql[1];
			} else {
				$stmtid = odbtp_prepare($sql,$this->_connectionID);
				if ($stmtid == false) {
					$this->_errorMsg = $php_errormsg;
					return false;
				}
			}
			$num_params = odbtp_num_params( $stmtid );
			for( $param = 1; $param <= $num_params; $param++ ) {
				@odbtp_input( $stmtid, $param );
				@odbtp_set( $stmtid, $param, $inputarr[$param-1] );
			}
			if (! odbtp_execute($stmtid) ) {
				return false;
			}
		} else if (is_array($sql)) {
			$stmtid = $sql[1];
			if (!odbtp_execute($stmtid)) {
				return false;
			}
		} else {
			$stmtid = @odbtp_query($sql,$this->_connectionID);
   		}
		$this->_lastAffectedRows = 0;
		if ($stmtid) {
				$this->_lastAffectedRows = @odbtp_affected_rows($stmtid);
		}
        return $stmtid;
	}

	function _close()
	{
		$ret = @odbtp_close($this->_connectionID);
		$this->_connectionID = false;
		return $ret;
	}
}

class ADORecordSet_odbtp extends ADORecordSet {

	var $databaseType = 'odbtp';
	var $canSeek = true;

	function ADORecordSet_odbtp($queryID,$mode=false)
	{
		if ($mode === false) {
			global $ADODB_FETCH_MODE;
			$mode = $ADODB_FETCH_MODE;
		}
		$this->fetchMode = $mode;
		$this->ADORecordSet($queryID);
	}

	function _initrs()
	{
		$this->_numOfFields = @odbtp_num_fields($this->_queryID);
		if (!($this->_numOfRows = @odbtp_num_rows($this->_queryID)))
			$this->_numOfRows = -1;
	}

	function &FetchField($fieldOffset = 0)
	{
		$off=$fieldOffset; // offsets begin at 0
		$o= new ADOFieldObject();
		$o->name = @odbtp_field_name($this->_queryID,$off);
		$o->type = @odbtp_field_type($this->_queryID,$off);
        $o->max_length = @odbtp_field_length($this->_queryID,$off);
		if (ADODB_ASSOC_CASE == 0) $o->name = strtolower($o->name);
		else if (ADODB_ASSOC_CASE == 1) $o->name = strtoupper($o->name);
		return $o;
	}

	function _seek($row)
	{
		return @odbtp_data_seek($this->_queryID, $row);
	}

	function fields($colname)
	{
		if ($this->fetchMode & ADODB_FETCH_ASSOC) return $this->fields[$colname];

		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$name = @odbtp_field_name( $this->_queryID, $i );
				$this->bind[strtoupper($name)] = $i;
			}
		}
		 return $this->fields[$this->bind[strtoupper($colname)]];
	}

	function _fetch_odbtp($type=0)
	{
		switch ($this->fetchMode) {
			case ADODB_FETCH_NUM:
				$this->fields = @odbtp_fetch_row($this->_queryID, $type);
				break;
			case ADODB_FETCH_ASSOC:
				$this->fields = @odbtp_fetch_assoc($this->_queryID, $type);
				break;
            default:
				$this->fields = @odbtp_fetch_array($this->_queryID, $type);
		}
		return is_array($this->fields);
	}

	function _fetch()
	{
		return $this->_fetch_odbtp();
	}

	function MoveFirst()
	{
		if (!$this->_fetch_odbtp(ODB_FETCH_FIRST)) return false;
		$this->EOF = false;
	  $this->_currentRow = 0;
	  return true;
    }

    function MoveLast()
   {
		if (!$this->_fetch_odbtp(ODB_FETCH_LAST)) return false;
		$this->EOF = false;
		$this->_currentRow = $this->_numOfRows - 1;
	  return true;
    }
    
	function NextRecordSet()
	{
		if (!@odbtp_next_result($this->_queryID)) return false;
		$this->_inited = false;
		$this->bind = false;
		$this->_currentRow = -1;
		$this->Init();
		return true;
	}

	function _close()
	{
		return @odbtp_free_query($this->_queryID);
	}
}

?>


