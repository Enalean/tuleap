#!/usr/bin/php
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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

$command = '';

$options = getopt('hv', array('help', 'version', 'clear-caches'));
foreach ($options as $option => $value) {
    switch ($option) {
        case 'h':
        case 'help':
            show_usage();
            exit(0);
            break;

        case 'v':
        case 'version':
            $command = 'version';
            break;

        case 'clear-caches':
            $command = 'clear-caches';
            break;
    }
}

if (! $command) {
    show_usage();
    exit(0);
}

require_once 'pre.php';

switch ($command) {
    case 'clear-caches':
        $site_cache = new SiteCache(new Log_ConsoleLogger());
        $site_cache->invalidatePluginBasedCaches();
        break;

    case 'version':
        show_version();
        break;

    default:
        show_usage();
}

function show_usage() {
    echo <<<EOT
Usage: tuleap COMMAND

Tuleap administration command line

Options:

    -h, --help          Print usage
    -v, --version       Tuleap version
    --clear-caches      Clear caches

EOT;
}

function show_version() {
    echo trim(file_get_contents(ForgeConfig::get('codendi_dir').DIRECTORY_SEPARATOR.'VERSION')).PHP_EOL;
}
