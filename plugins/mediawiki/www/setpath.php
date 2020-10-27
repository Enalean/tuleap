<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/vendor/autoload.php';

$locar_inc_finder = new Config_LocalIncFinder();
$local_inc = $locar_inc_finder->getLocalIncPath();

require_once $local_inc;

ForgeConfig::loadFromFile($local_inc);
ForgeConfig::loadDatabaseInc();

require_once __DIR__ . '/../include/mediawikiPlugin.php';

$dao = new MediawikiSiteAdminResourceRestrictorDao();

if (isset($GLOBALS['TULEAP_MW_PROJECT'])) {
    $wiki_name = $GLOBALS['TULEAP_MW_PROJECT'];
} else {
    $uri = explode('/', $_SERVER['REQUEST_URI']);
    $wiki_name = $uri[4];
}

$mediawikipath = '/usr/share/mediawiki-tuleap-123';
$IP            = $mediawikipath;
