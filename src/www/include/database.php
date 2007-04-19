<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 
//
//

function db_connect() {
    global $sys_dbhost,$sys_dbuser,$sys_dbpasswd,$conn,$sys_dbname;
    $conn = mysql_connect($sys_dbhost,$sys_dbuser,$sys_dbpasswd);
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
//	global $QUERY_COUNT;
//	$QUERY_COUNT++;
//	if ($GLOBALS[IS_DEBUG]) $GLOBALS[G_DEBUGQUERY] .= $qstring . "<BR>\n";
    global $conn;
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
	return @mysql_insert_id();
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
?>
