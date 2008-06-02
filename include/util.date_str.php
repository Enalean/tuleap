<?php
/*
 *  util.date_str.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - Date string
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
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
	if (ereg("^([+\-][0-9][0-9])([0-9][0-9])$",$tz,$regs)) {
		$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
		$date['hour_local'] = date("H",$local);
		$date['minute_local'] = date("i",$local);
		$date['tz_local'] = $tz;
	}
	return $date;
}

?>
