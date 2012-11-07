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
    if ($error && !Config::get('DEBUG_MODE')) {
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

?>
