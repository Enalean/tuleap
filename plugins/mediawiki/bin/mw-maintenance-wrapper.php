#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

function usage()
{
    echo "Usage: .../mw-maintenance-wrapper.php <projectname> <script> [ arguments... ]
For instance: .../mw-maintenance-wrapper.php projectname importDump.php /tmp/wikidump.xml
              .../mw-maintenance-wrapper.php projectname rebuildrecentchanges.php
";
    exit(1);
}

if (count($argv) < 3) {
    usage();
}

$GLOBALS['TULEAP_MW_PROJECT'] = $argv[1];
require_once __DIR__ . '/../www/setenv.php';

$wrapperscript = array_shift($argv);
$fusionforgeproject = array_shift($argv);
$mwscript = array_shift($argv);

$tuleap_src = dirname(__FILE__) . '/../../../src/';
$tuleap_src_include = dirname(__FILE__) . '/../../../src/www/include';

set_include_path("$tuleap_src:$tuleap_src_include");
$mwscript_abs_path = $IP . "/maintenance/$mwscript";
array_unshift($argv, $mwscript_abs_path, '--conf', '/usr/share/tuleap/plugins/mediawiki/www/LocalSettings.php');
require_once(__DIR__ . '/../include/constants.php');
$GLOBALS['sys_pluginsroot'] = '/usr/share/tuleap/plugins';


require_once $mwscript_abs_path;
