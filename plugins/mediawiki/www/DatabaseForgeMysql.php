<?php
/*
 * Copyright (C) 2010 Roland Mas, Olaf Lenz
 * Copyright (C) 2011 France Telecom
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class DatabaseForge extends DataBase{
	function DatabaseForge($server=false, $user=false, $password=false,
			       $dbName=false, $failFunction=false, $flags=0) {
		global $wgDBtype;

		$wgDBtype = "mysql";
		return Database::__construct($server, $user,
							  $password, $dbName, $failFunction, $flags);
	}

	function tableName($name) {
		switch ($name) {
		case 'interwiki':
			return 'public.plugin_mediawiki_interwiki';
		default:
			return Database::tableName($name);
		}
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
