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

  /** This script will destroy a mediawiki instance of a specific project.     */
if ($argc < 2 ) {
	echo "Usage " . $argv[0] . " <project>\n";
	exit (0);
}

require_once dirname(__FILE__) . '/../../../www/env.inc.php';
require_once $gfcommon.'include/pre.php';

$projects_path = forge_get_config('projects_path', 'mediawiki');

array_shift($argv);
foreach ($argv as $project) {
  echo "Removing project wiki of $project.\n";

  $project_dir = "$projects_path/$project";
  echo "  Deleting project subdir $project_dir.\n";
  if (!is_dir($project_dir)) {
    echo "$project_dir does not exist!\n";
  } else {
    system("rm -rf $project_dir");
  }

  $schema = "plugin_mediawiki_$project";
  strtr($schema, "-", "_");
  echo "  Dropping database schema $schema.\n";
  $res = db_query_params("DROP SCHEMA $schema CASCADE", array());
  if (!$res) {
    echo db_error();
  }
  $res = db_query_params('DELETE FROM plugin_mediawiki_interwiki WHERE iw_prefix=$1', array($project));
  if (!$res) {
    echo db_error();
  }
}

  // Local Variables:
  // mode: php
  // c-file-style: "bsd"
  // End:

?>
