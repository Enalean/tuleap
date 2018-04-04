<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\ForgeAccess\UnknownForgeAccessValueException;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;

$tuleap_short_options = 'hvcr';
$tuleap_long_options  = array('help', 'version', 'clear-caches', 'restore-caches', 'platform-access-control::');

$commands = array();

$options = getopt($tuleap_short_options, $tuleap_long_options);

foreach ($options as $option => $value) {
    switch ($option) {
        case 'h':
        case 'help':
            show_usage();
            exit(0);
            break;

        case 'v':
        case 'version':
            $commands = array('version');
            break 2;

        case 'c':
        case 'clear-caches':
            $commands[] = 'clear-caches';
            break;

        case 'r':
        case 'restore-caches':
            $commands[] = 'restore-caches';
            break;

        case 'platform-access-control':
            $commands[] = 'platform-access-control';
            break;
    }
}

if (! $commands) {
    show_usage();
    exit(0);
}

require_once 'pre.php';

foreach ($commands as $command) {
    switch ($command) {
        case 'clear-caches':
            $site_cache = new SiteCache(new Log_ConsoleLogger());
            $site_cache->invalidatePluginBasedCaches();
            break;

        case 'restore-caches':
            $site_cache = new SiteCache(new Log_ConsoleLogger());
            $site_cache->restoreCacheDirectories();
            $site_cache->restoreOwnership();
            break;

        case 'version':
            show_version();
            break;

        case 'platform-access-control':
            process_platform_access_control($options[$command]);
            break;

        default:
            show_usage();
    }
}

function show_usage() {
    echo <<<EOT
Usage: tuleap COMMAND

Tuleap administration command line

Options:

    -h, --help                   Print usage
    -v, --version                Tuleap version
    -c, --clear-caches           Clear caches
    -r, --restore-caches         Recreate cache directories if needed
    --platform-access-control    Show or set the platform access control

EOT;
}

function show_version() {
    echo trim(file_get_contents(ForgeConfig::get('codendi_dir').DIRECTORY_SEPARATOR.'VERSION')).PHP_EOL;
}

function process_platform_access_control($value) {
    $current_platform_access_value = ForgeConfig::get(ForgeAccess::CONFIG);
    if ($value === false) {
        echo $current_platform_access_value . PHP_EOL;
        return;
    }

    $forge_access_properties_manager = new ForgeAccess_ForgePropertiesManager(
        new ConfigDao(),
        ProjectManager::instance(),
        PermissionsManager::instance(),
        EventManager::instance(),
        new FRSPermissionCreator(
            new FRSPermissionDao(),
            new UGroupDao()
        )
    );

    try {
        $forge_access_properties_manager->updateAccess($value, $current_platform_access_value);
    } catch (UnknownForgeAccessValueException $e) {
        $logger = new Log_ConsoleLogger();
        $logger->error($e->getMessage());
        exit(1);
    }
}