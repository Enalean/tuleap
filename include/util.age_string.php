<?php
/*
 *  util.age_string.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - convert age to a readable string
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

function age_string($age)
{
	if ($age > 60*60*24*365*2)
		return (int)($age/60/60/24/365) . " years ago";
	else if ($age > 60*60*24*(365/12)*2)
		return (int)($age/60/60/24/(365/12)) . " months ago";
	else if ($age > 60*60*24*7*2)
		return (int)($age/60/60/24/7) . " weeks ago";
	else if ($age > 60*60*24*2)
		return (int)($age/60/60/24) . " days ago";
	else if ($age > 60*60*2)
		return (int)($age/60/60) . " hours ago";
	else if ($age > 60*2)
		return (int)($age/60) . " min ago";
	else if ($age > 2)
		return (int)$age . " sec ago";
	return "right now";
}

?>
