#! /usr/bin/php
<?php
/*
 * Copyright (C) 2010  Olaf Lenz
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

  /** This script will automatically create the image upload
   directories for all mediawiki instances.

   It is intended to be started in a cronjob with root permissions.
   */

require_once dirname(__FILE__) . '/../../../www/env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/cron_utils.php';

if (forge_get_config('enable_uploads', 'mediawiki')) {
	$upload_dir_basename = "images";
	$projects_path = forge_get_config('projects_path', 'mediawiki');

# Owner of files - apache
	$dir_owner = forge_get_config('apache_user');
	$dir_group = forge_get_config('apache_group');

# Get all projects that use the mediawiki plugin
	$project_res = db_query_params ("SELECT g.unix_group_name from groups g, group_plugin gp, plugins p where g.group_id = gp.group_id and gp.plugin_id = p.plugin_id and p.plugin_name = $1;", array("mediawiki"));
	if (!$project_res) {
		$err =  "Error: Database Query Failed: ".db_error();
		cron_debug($err);
		cron_entry(23,$err);
		exit;
	}

# Loop over all projects that use the plugin
	while ( $row = db_fetch_array($project_res) ) {
		$project = $row['unix_group_name'];
		$project_dir = "$projects_path/$project";
		cron_debug("Checking $project...");

		// Create the image directory if necessary
		$upload_dir = "$project_dir/$upload_dir_basename";
		if (!is_dir($upload_dir)) {
			cron_debug("  Creating upload dir $upload_dir.");
			mkdir($upload_dir, 0700);
		} else {
			cron_debug("  Upload dir $upload_dir exists.");
		}
		cron_debug("  Changing owner ($dir_owner), group ($dir_group) and permission (0775) of upload dir $upload_dir.");
		chown($upload_dir, $dir_owner);
		chgrp($upload_dir, $dir_group);
		chmod($upload_dir, 0775);
	}
} else {
	cron_debug("Mediawiki uploads not enabled, quitting create-imagedirs.php!");
}
  // Local Variables:
  // mode: php
  // c-file-style: "bsd"
  // End:

?>
