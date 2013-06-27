#! /usr/bin/php -f
<?php
/*-
 * FusionForge/Mediawiki integration
 *
 * Copyright © 2010
 *	Roland Mas
 * Copyright © 2012
 *	Thorsten Glaser <t.glaser@tarent.de>
 * All rights reserved.
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
 *-
 * Wrapper to call MediaWiki maintenance scripts on a forge project wiki.
 */

function usage($rv=1) {
        echo "Usage: .../mw-wrapper.php [-L] <project> <script> [ arguments... ]
For instance: .../mw-wrapper.php siteadmin importDump.php /tmp/wikidump.xml
              .../mw-wrapper.php siteadmin rebuildrecentchanges.php
" ;
        exit($rv);
}

if (count($argv) < 3) {
	usage();
}

$wrapperscript = array_shift ($argv) ;
$fusionforgeproject = array_shift ($argv) ;
if ($fusionforgeproject == "-L") {
	if (count($argv) < 2) {
		usage();
	}
	$fusionforgeproject = array_shift($argv);
	$preload_localsettings = true;
} else {
	/*
	 * saves some warnings
	 * works if the mwscript includes e.g. commandLine.inc
	 */
	$preload_localsettings = false;
}
$mwscript = array_shift ($argv) ;

require_once dirname(__FILE__).'/../../../common/include/env.inc.php';
require_once $gfcommon.'include/pre.php';
require_once $gfcommon.'include/cron_utils.php';

// Plugins subsystem
require_once($gfcommon.'include/Plugin.class.php');
require_once($gfcommon.'include/PluginManager.class.php');

setup_plugin_manager () ;

$group = group_get_object_by_name($fusionforgeproject) ;
if (!$group || $group->isError()) {
	die("Wrong group! " .
	    ($group ? $group->getErrorMessage() : "") . "\n");
}

if (!$group->usesPlugin('mediawiki')) {
	die ("Project doesn't use the Mediawiki plugin\n") ;
}

function ffmw_wrapper_fixup_searchpath($username) {
	db_query_params("ALTER ROLE $username SET search_path = public",
	    array());
}
register_shutdown_function('ffmw_wrapper_fixup_searchpath',
    forge_get_config('database_user'));

$ff_localsettings = forge_get_config('source_path') .
    '/www/plugins/mediawiki/LocalSettings.php';
if ($preload_localsettings) {
	define("MEDIAWIKI", true);
	require_once($ff_localsettings);
}

$src_path = forge_get_config('src_path', 'mediawiki');
$mwscript = $src_path . '/maintenance/'.$mwscript ;

array_unshift($argv, $mwscript, '--conf', $ff_localsettings);

while (@ob_end_flush())
	/* nothing */;

require_once $mwscript ;
