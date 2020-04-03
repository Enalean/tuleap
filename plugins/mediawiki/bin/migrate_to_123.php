#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

if (! isset($argv[1])) {
    echo "you must specify a project name\n";
    exit(1);
}

$fusionforgeproject      = $argv[1];
$is_tuleap_mediawiki_123 = true;
$IS_RUNNING_UPDATE       = true;

if (file_exists('/usr/share/mediawiki-tuleap-123/maintenance/update.php')) {
    require_once __DIR__ . '/../../../src/www/include/pre.php';
    require_once __DIR__ . '/../include/mediawikiPlugin.php';
    include('/usr/share/mediawiki-tuleap-123/maintenance/update.php');
} else {
    fwrite(STDERR, "Unable to find /usr/share/mediawiki-tuleap-123, did you install RPMs ?\n");
    exit(1);
}
