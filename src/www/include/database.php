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
if(!defined('CODEX_DB_NULL')) define('CODEX_DB_NULL', 0);
if(!defined('CODEX_DB_NOT_NULL')) define('CODEX_DB_NOT_NULL', 1);

function db_connect() {
    global $sys_dbhost,$sys_dbuser,$sys_dbpasswd,$conn,$sys_dbname;
    $conn_opt = '';
    if(isset($GLOBALS['sys_enablessl']) && $GLOBALS['sys_enablessl']) {
      $conn_opt = MYSQL_CLIENT_SSL;
    }
    $conn = mysql_connect($sys_dbhost,$sys_dbuser,$sys_dbpasswd, false, $conn_opt);
    unset($sys_dbpasswd);
    if (!$conn) {
        die('Database Error - Could not connect. ' . mysql_error());
    }    
    $db_selected= mysql_select_db($sys_dbname, $conn);
    if (!$db_selected) {
        die ("Database Error - Can't use database $sys_dbname: " . mysql_error());
    }
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

function db_query($qstring,$print=0) {
    global $conn;
    $GLOBALS['DEBUG_DBPHP_QUERY_COUNT']++;
    if ($print) print "<br>Query is: $qstring<br>";
    $GLOBALS['db_qhandle'] = @mysql_query($qstring, $conn);
    return $GLOBALS['db_qhandle'];
}

function db_numrows($qhandle) {
	// return only if qhandle exists, otherwise 0
	if ($qhandle) {
		return @mysql_num_rows($qhandle);
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
	if ($qhandle) {
		return @mysql_fetch_array($qhandle);
	} else {
		if ($GLOBALS['db_qhandle']) {
			return @mysql_fetch_array($GLOBALS['db_qhandle']);
		} else {
			return (array());
		}
	}
}
	
function db_insertid($qhandle) {
	global $conn;
    if (isset($conn) && $conn) {
        return @mysql_insert_id($conn);
    } else {
        return @mysql_insert_id();
    }
}

function db_error() {
	return @mysql_error();
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
	return mysql_data_seek($qhandle,$row);
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
 * If CODEX_DB_NULL is used, empty string '' as $val returns 'NULL' string.
 * This last form is useful when the corresponding field is defined as INT or
 * NULL in SQL.
 *
 * @see http://php.net/language.types.integer
 * @see DataAccess::escapeInt for tests.
 * @param  mixed $val a value to escape
 * @param  int   $null CODEX_DB_NOT_NULL or CODEX_DB_NULL
 * @return string Decimal integer encoded as a string
 */
function db_escape_int($val, $null = CODEX_DB_NOT_NULL) {
    $match = array();
    if($null === CODEX_DB_NULL && $val === '') {
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
 * @param  int   $null CODEX_DB_NOT_NULL or CODEX_DB_NULL
 * @return string Decimal integer encoded as a string
 */
function db_ei($val, $null = CODEX_DB_NOT_NULL) {
    return db_escape_int($val, $null);
}

?>
