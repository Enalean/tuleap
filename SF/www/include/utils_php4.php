<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2002 All Rights Reserved
// http://codex.xerox.com
//
// $Id$

/*
 This file contains handy functions that are available in PHP4  and not in
 in PHP3. It must be conditionally included if the interpreter is version 3
(see pre.php)
*/

// array_keys: return al keys of a Hash in an array
// Got this code snippet from the php.net documentation
function array_keys ($arr, $term="") {
    if (!is_array($arr)) { return; }
    $t = array();
    while (list($k,$v) = each($arr)) {
        if ($term && $v != $term) {
            continue;
        }
        $t[] = $k;
    }
    return $t;
}

// in_array: test whether the needle can be found in the array values
function in_array ($needle, $haystack, $strict=false) {
    reset($haystack);
    if ($strict) {
	while (list(,$v) = each($haystack)) {
	    if ($v == $needle) { return true;}
	}
    } else {
	while (list(,$v) = each($haystack)) {
	    if ( ($v == $needle) && (gettype($needle) == gettype($v))) { return true;}
	}
    }
    return false;
}



// localtime: returns the local time in a array or hash
function localtime( $time, $is_associative=false) {

	$tm_sec= date("s", $time);
	$tm_min= date("i", $time);
	$tm_hour= date("H", $time);
	$tm_mday= date("d", $time);
	$tm_mon= date("m", $time) - 1;
	$tm_year=date("Y", $time) - 1900;
	$tm_wday=date("w", $time);
	$tm_yday=date("z", $time);
	$tm_isdst=date("I", $time);

	if ($is_associative) {
		return array("tm_sec" => $tm_sec, "tm_min" => $tm_min, "tm_hour" => $tm_hour, "tm_mday" => $tm_mday, "tm_mon" => $tm_mon, "tm_year" => $tm_year, "tm_wday" => $tm_wday, "tm_yday" => $tm_yday, "tm_isdst" => $tm_isdst);
	} else {
		return array($tm_sec, $tm_min, $tm_hour, $tm_mday, $tm_mon, $tm_year, $tm_wday, $tm_yday, $tm_isdst);
	}

}

?>
