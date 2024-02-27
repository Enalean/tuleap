#!/usr/bin/env php
<?php
// Copyright (c) Enalean, 2018 - Present. All rights reserved
//
// This file is a part of Tuleap.
//
// Tuleap is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// Tuleap is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Tuleap. If not, see <http://www.gnu.org/licenses/
$basedir = $argv[1];

function info($message)
{
    echo "\033[32m$message\033[0m\n";
}

function error($message)
{
    echo "\033[31m$message\033[0m\n";
}

function executeCommandAndExitIfStderrNotEmpty($command)
{
    $descriptorspec = [
        0 => STDIN,
        1 => STDOUT,
        2 => ['pipe', 'wb'],
    ];

    $process = proc_open($command, $descriptorspec, $pipes);
    if (! is_resource($process)) {
        error("Can't execute command $command");
        exit(1);
    }

    $stderr       = stream_get_contents($pipes[2]);
    $return_value = proc_close($process);

    if (! empty($stderr)) {
        error($stderr);
        exit(1);
    }

    if ($return_value !== 0) {
        exit($return_value);
    }
}

foreach (glob("$basedir/plugins/*", GLOB_ONLYDIR) as $path) {
    $manifest = "$path/build-manifest.json";
    if (is_file($manifest)) {
        $json = json_decode(file_get_contents($manifest), true);
        if (isset($json['gettext-smarty']) && is_array($json['gettext-smarty'])) {
            $translated_plugin = basename($path);
            foreach ($json['gettext-smarty'] as $component => $gettext) {
                info("[$translated_plugin][smarty][$component] Generating .mo files");
                $po       = escapeshellarg("$path/{$gettext['po']}");
                $template = escapeshellarg("$path/{$gettext['po']}/$component.pot");
                foreach (glob("$path/{$gettext['po']}/*/LC_MESSAGES/*.po") as $locale_file) {
                    $locale_dir = dirname($locale_file);
                    $mo_file    = escapeshellarg("$locale_dir/$component.mo");
                    $po_file    = escapeshellarg($locale_file);
                    executeCommandAndExitIfStderrNotEmpty("msgfmt -o $mo_file $po_file");
                }
            }
        }
    }
}
