<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 
//
//
$GLOBALS['DEBUG_DBPHP_QUERY_COUNT'] = 0;
if(!defined('CODENDI_DB_NULL')) define('CODENDI_DB_NULL', 0);
if(!defined('CODENDI_DB_NOT_NULL')) define('CODENDI_DB_NOT_NULL', 1);

$conn = null;
function db_connect() {
    global $conn;
    $conn = CodendiDataAccess::instance();
}

/**
 * Returns the connection object, or null if there is no connection
 *
 * @return {resource} the connection, or null if no connection
 */
function getConnection() {
    global $conn;
    if (isset($conn) && $conn) {
        return $conn;
    } else {
        return null;
    }
}

function db_query($sql,$print=0) {
    global $conn;
    if ($print) {
        print "<br>Query is: $sql<br>";
    }
    return db_query_params($sql, array());
}

function db_query_params($sql, $params) {
    global $conn;
	$dar = $conn->query($sql, $params);
    $GLOBALS['db_qhandle'] = $dar->getResult();
    if (db_numrows($GLOBALS['db_qhandle'])) {
        db_reset_result($GLOBALS['db_qhandle']);
    }
    return $GLOBALS['db_qhandle'];
}

function db_numrows($qhandle) {
    global $conn;
	// return only if qhandle exists, otherwise 0
	if ($qhandle) {
                return @$conn->numRows($qhandle);
	} else {
		return 0;
	}
}

function db_free_result($qhandle) {
	return @mysql_free_result($qhandle);
}

function db_result($qhandle,$row,$field) {
	return @mysql_result($qhandle,$row,$field);
}

function db_numfields($lhandle) {
	return @mysql_num_fields($lhandle);
}

function db_fieldname($lhandle,$fnumber) {
           return @mysql_field_name($lhandle,$fnumber);
}

function db_affected_rows($qhandle) {
	return @mysql_affected_rows();
}
	
function db_fetch_array($qhandle = 0) {
    global $conn;
	if ($qhandle) {
		return @$conn->fetchArray($qhandle);
	} else {
		if ($GLOBALS['db_qhandle']) {
			return @$conn->fetchArray($GLOBALS['db_qhandle']);
		} else {
			return (array());
		}
	}
}
	
function db_insertid($qhandle) {
	global $conn;
    if (isset($conn) && $conn) {
        return @mysql_insert_id($conn->db);
    } else {
        return @mysql_insert_id();
    }
}

/**
 * Display real error only if we are in Debug mode
 * 
 * @return String 
 */
function db_error() {
    $error = @mysql_error();
    if ($error && !ForgeConfig::get('DEBUG_MODE')) {
        $error = 'DB error';
    }
    return $error;
}

/**
 *  db_reset_result() - Reset a result set.
 *
 *  Reset is useful for db_fetch_array sometimes you need to start over
 *
 *  @param		string	Query result set handle
 *  @param		int		Row number
 */
function db_reset_result($qhandle,$row=0) {
    global $conn;
    return $conn->dataSeek($qhandle,$row);
}

function db_escape_string($string,$qhandle=false) {
  if (function_exists('mysql_real_escape_string')) {
    if ($qhandle) {
      return mysql_real_escape_string($string,$qhandle);
    } else {
      return mysql_real_escape_string($string);
    }
  } else {
    return mysql_escape_string($string);
  }
}

/**
 * Alias for db_escape_string.
 */
function db_es($string,$qhandle=false) {
    return db_escape_string($string,$qhandle);
}

/**
 * Escape value as a valid decimal integer.
 *
 * If input is not a valid decimal integer, return '0'.
 * If CODENDI_DB_NULL is used, empty string '' as $val returns 'NULL' string.
 * This last form is useful when the corresponding field is defined as INT or
 * NULL in SQL.
 *
 * @see http://php.net/language.types.integer
 * @see DataAccess::escapeInt for tests.
 * @param  mixed $val a value to escape
 * @param  int   $null CODENDI_DB_NOT_NULL or CODENDI_DB_NULL
 * @return string Decimal integer encoded as a string
 */
function db_escape_int($val, $null = CODENDI_DB_NOT_NULL) {
    $match = array();
    if($null === CODENDI_DB_NULL && $val === '') {
        return 'NULL';
    }
    if(preg_match('/^([+-]?[1-9][0-9]*|[+-]?0)$/', $val, $match)) {
        return $match[1];
    }
    return '0';
}

/**
 * Alias for db_escape_int
 *
 * @param mixed $val a value to escape
 * @param  int   $null CODENDI_DB_NOT_NULL or CODENDI_DB_NULL
 * @return string Decimal integer encoded as a string
 */
function db_ei($val, $null = CODENDI_DB_NOT_NULL) {
    return db_escape_int($val, $null);
}

/**
 * @deprecated
 * @see DataAccess::escapeIntImplode()
 */
function db_ei_implode($val) {
    return implode(',', array_map('db_ei', $val));
}

function db_begin(){
	echo "db_begin()\n";
}
function db_commit(){
	echo "db_commit()\n";
}
function db_rollback(){
	echo "db_rollback()\n";
}

/**
 * @deprecated
 * @return bool
 */
function db_select($database_name) {
	return @mysql_select_db($database_name);
}

/**
 *  db_query_from_file() - Query the database, from a file.
 *
 *  @param string File that contains the SQL statements.
 *  @param int How many rows do you want returned.
 *  @param int Of matching rows, return only rows starting here.
 *  @param int ability to spread load to multiple db servers.
 *  @return int result set handle.
 */
function db_query_from_file($file,$limit='-1',$offset=0,$dbserver=NULL) {
/*      
        db_connect_if_needed () ;
        $dbconn = db_switcher($dbserver) ;

        global $QUERY_COUNT;
        $QUERY_COUNT++;

        $qstring = file_get_contents($file);
        if (!$qstring) {
                error_log('db_query_from_file(): Cannot read file $file!');
                return false;
        }
        if (!$limit || !is_numeric($limit) || $limit < 0) {
                $limit=0;
        }
        if ($limit > 0) {
                if (!$offset || !is_numeric($offset) || $offset < 0) {
                        $offset=0;
                }
                $qstring=$qstring." LIMIT $limit OFFSET $offset";
        }
        $res = @pg_query($dbconn,$qstring);
        if (!$res) {
                error_log('SQL: ' . preg_replace('/\n\t+/', ' ',$qstring));
                error_log('SQL> ' . db_error($dbserver));
        }
        return $res;
*/
	// inspired from /usr/share/mediawiki115/includes/db/Database.php
        $fp = fopen( $file, 'r' );
        if ( false === $fp ) {
                error_log('db_query_from_file(): Cannot read file $file!');
        	fclose( $fp );
		return false;
        }

	$cmd = "";
	$done = false;
	$dollarquote = false;

	while ( ! feof( $fp ) ) {
		$line = trim( fgets( $fp, 1024 ) );
		$sl = strlen( $line ) - 1;

		if ( $sl < 0 ) { continue; }
		if ( '-' == $line{0} && '-' == $line{1} ) { continue; }

		## Allow dollar quoting for function declarations
		if (substr($line,0,4) == '$mw$') {
			if ($dollarquote) {
				$dollarquote = false;
				$done = true;
			}
			else {
				$dollarquote = true;
			}
		}
		else if (!$dollarquote) {
			if ( ';' == $line{$sl} && ($sl < 2 || ';' != $line{$sl - 1})) {
				$done = true;
				$line = substr( $line, 0, $sl );
			}
		}

		if ( '' != $cmd ) { $cmd .= ' '; }
		$cmd .= "$line\n";

		if ( $done ) {
			$cmd = str_replace(';;', ";", $cmd);
			// next 2 lines are for mediawiki subst
			$cmd = preg_replace(":/\*_\*/:","mw",$cmd );
                        // TOCHECK WITH CHRISTIAN: Do not change indexes for mediawiki (doesn't seems well supported)
			//$cmd = preg_replace(":/\*i\*/:","mw",$cmd );
			$res = db_query( $cmd );

        		if (!$res) {
                		error_log('SQL: ' . preg_replace('/\n\t+/', ' ',$cmd));
                		error_log('SQL> ' . db_error($dbserver));
        			return $res;
        		}

			$cmd = '';
			$done = false;
		}
	}
        fclose( $fp );
	return true;
}

?>
