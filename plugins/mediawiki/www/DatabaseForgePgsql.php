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

require_once("$IP/includes/db/DatabasePostgres.php");
class DatabaseForge extends DatabasePostgres {
	function __construct($server=false, $user=false, $password=false,
	    $dbName=false, $failFunction=false, $flags=0) {
		global $wgDBtype;

		$wgDBtype = "postgres";
		return parent::__construct($server, $user,
		    $password, $dbName, $failFunction, $flags);
	}

	function fieldInfo($table, $field) {
		switch ($table) {
		case 'interwiki':
			$table = 'plugin_mediawiki_interwiki';
			break;
		default:
			return DatabasePostgres::fieldInfo($table, $field);
		}

		global $wgDBmwschema;

		$save_wgDBmwschema = $wgDBmwschema;
		$wgDBmwschema = 'public';
		$v = DatabasePostgres::fieldInfo($table, $field);
		$wgDBmwschema = $save_wgDBmwschema;
		return $v;
	}

	function open($server, $user, $password, $dbName) {
		$v = DatabasePostgres::open($server, $user, $password, $dbName);

		global $wgDBmwschema;
		if ($this->schemaExists($wgDBmwschema)) {
			if (method_exists ($this,"addIdentifierQuotes")) {
				$safeschema = $this->addIdentifierQuotes($wgDBmwschema);
			} else {
				$safeschema = $wgDBmwschema;
			}
			$this->doQuery("SET search_path TO $safeschema,public");
		}

		return $v;
	}

	function query($sql, $fname='', $tempIgnore=false) {
		/* ugh! */
		$chk = "ALTER TABLE interwiki ";
		$csz = strlen($chk);
		if (substr($sql, 0, $csz) == $chk) {
			$sql = "ALTER TABLE public.plugin_mediawiki_interwiki " .
			    substr($sql, $csz);
		}
		return DatabasePostgres::query($sql, $fname,$tempIgnore);
	}

	function tableName($name, $format='quoted') {
		global $wgDBmwschema;

		switch ($name) {
		case 'interwiki':
			$v = 'plugin_mediawiki_interwiki';
			break;
		default:
			return DatabasePostgres::tableName($name, $format);
		}

		if ($wgDBmwschema != 'public') {
			$v = 'public.' . $v;
		}
		return $v;
	}
}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:
