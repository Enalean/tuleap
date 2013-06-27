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

  /** This script will set up the structure required to use the
   mediawiki plugin.
  */

require_once dirname(__FILE__) . '/../../../www/env.inc.php';
require_once $gfcommon.'include/pre.php';

$echo_links = $argc >= 1;

$master_path = forge_get_config('master_path', 'mediawiki');
$projects_path = forge_get_config('projects_path', 'mediawiki');
$src_path = forge_get_config('src_path', 'mediawiki');

# create directories
if (!is_dir($projects_path)) {
	echo "Creating $projects_path...\n";
	mkdir($projects_path, 0755, true);
}

if (!is_dir($master_path)) {
	echo "Creating $master_path...\n";
	mkdir($master_path, 0755, true);
}

function mysymlink($from, $to) {
	global $echo_links;
	if (!@symlink($from, $to)) {
		echo "Could not create symbolic link from $from to $to.\n";
	}
	if ($echo_links) {
		echo "$from $to\n";
	}
}


# install links in master
echo "Creating symlinks from $master_path to $src_path...\n";
if (!($dh = opendir($src_path))) {
	echo "Could not open mediawiki source directory $src_path!\n";
} else {
	$ignore_file = array(
		'.' => true,
		'..' => true,
		'config' => true,
		'skins' => true,
		'images' => true,
		'tests' => true,
		't' => true,
		);
	while ($file = readdir($dh)) {
		if (!isset($ignore_file[$file]) || !$ignore_file[$file]) {
			$from = "$src_path/$file";
			$to = "$master_path/$file";
			mysymlink($from, $to);
		}
	}
	closedir ($dh);
}

// link LocalSettings.php from forge_get_config('source_path')/plugins/mediawiki/etc/plugins/mediawiki/LocalSettings.php
$from = forge_get_config('source_path')."/plugins/mediawiki/www/LocalSettings.php";
$to = "$master_path/LocalSettings.php";
mysymlink($from, $to);

// create skin directory
$todir = "$master_path/skins";
if (!is_dir($todir)) {
	mkdir($todir);
}

// link FusionForge skin file
$fromdir = forge_get_config('source_path')."/plugins/mediawiki/mediawiki-skin";
$from = "$fromdir/FusionForge.php";
$to = "$todir/FusionForge.php";
mysymlink($from, $to);

// create skin subdir
$todir = "$todir/fusionforge";
if (!is_dir($todir))
	mkdir($todir);

// link fusionforge.css files
$fromdir = "$fromdir/fusionforge";
$from = "$fromdir/fusionforge.css";
$to = "$todir/fusionforge.css";
mysymlink($from, $to);

// link the rest of the files from monobook skin
$fromdir = "$src_path/skins/monobook";

$dh = opendir($fromdir);
$ignore_file = array(
	'.' => true,
	'..' => true,
	);
while ($file = readdir($dh)) {
	if (!isset($ignore_file[$file]) || !$ignore_file[$file]) {
		$from = "$fromdir/$file";
		$to = "$todir/$file";
		mysymlink($from, $to);
	}
}
closedir($dh);

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
