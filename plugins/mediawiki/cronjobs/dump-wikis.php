#! /usr/bin/php
<?php
/*
 * Copyright 2011, Roland Mas
 *
 * This file is part of FusionForge. FusionForge is free software;
 * you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or (at your option)
 * any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with FusionForge; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/** This script will automatically dump Mediawiki databases to an XML file.
 *
 * It is intended to be started in a cronjob.
 */

require_once dirname(__FILE__) . '/../../../www/env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/cron_utils.php';

$src_path = forge_get_config('src_path', 'mediawiki');
$master_path = forge_get_config('master_path', 'mediawiki');

// Get all projects that use the mediawiki plugin
$project_res = db_query_params ("SELECT g.unix_group_name,g.group_id from groups g, group_plugin gp, plugins p where g.group_id = gp.group_id and gp.plugin_id = p.plugin_id and p.plugin_name = $1;", array("mediawiki"));
if (!$project_res) {
	$err =  "Error: Database Query Failed: ".db_error();
	cron_debug($err);
	cron_entry(23,$err);
	exit;
}

// Loop over all projects that use the plugin
while ( $row = db_fetch_array($project_res) ) {
	$project = $row['unix_group_name'];
	$project_id = $row['group_id'];
	$dump_file = forge_get_config('data_path') . "/plugins/mediawiki/dumps/$project.xml";

	$ra = RoleAnonymous::getInstance();
	if ($ra->hasPermission('plugin_mediawiki_read',$project_id)) {
		cron_debug("Dumping $project...");
		$mwwrapper = forge_get_config('source_path')."/plugins/mediawiki/bin/mw-wrapper.php" ;
		$tmp = tempnam(forge_get_config('data_path')."/plugins/mediawiki/dumps/", "tmp");
		system ("$mwwrapper $project dumpBackup.php --current --quiet > $tmp") ;
		chmod ($tmp, 0644);
		rename ($tmp, $dump_file);
	} else {
		cron_debug("Not dumping $project (private)...");
		if (file_exists($dump_file)) {
			unlink($dump_file);
		}
	}
}


  // Local Variables:
  // mode: php
  // c-file-style: "bsd"
  // End:

?>
