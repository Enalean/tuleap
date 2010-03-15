<?php
/*
 *  util.date_str.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - Date string
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function date_str($epoch,$tz = "-0000")
{
	$date = array();
	$date['hour'] = date("H",$epoch);
	$date['minute'] = date("i",$epoch);
	$date['mday'] = date("d",$epoch);
	$date['day'] = date("D",$epoch);
	$date['month'] = date("M",$epoch);
	$date['rfc2822'] = date("r",$epoch);
	$date['mday-time'] = date("d M H:i",$epoch);
	if (preg_match("/^([+\-][0-9][0-9])([0-9][0-9])$/",$tz,$regs)) {
		$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
		$date['hour_local'] = date("H",$local);
		$date['minute_local'] = date("i",$local);
		$date['tz_local'] = $tz;
	}
	return $date;
}

?>
