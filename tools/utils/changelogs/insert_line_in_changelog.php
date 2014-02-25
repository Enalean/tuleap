<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

$plugin         = $argv[1];
$version        = $argv[2];
$tuleap_version = $argv[3];
$item_type      = ucfirst($argv[4]);

$reference_line_to_plugin = "\t* $plugin: $version";

$changelog               = file('ChangeLog', FILE_IGNORE_NEW_LINES);
$new_changelog           = array();
$current_version_started = false;
$in_section              = false;
$current_version_stopped = false;
foreach ($changelog as $line) {
    if (!$current_version_started) {
        if (preg_match('/^Version '. preg_quote($tuleap_version) .'/i', $line)) {
            $current_version_started = true;
        }
    } else if ($current_version_started && !$current_version_stopped) {
        if (preg_match("/== $item_type ==/i", $line)) {
            $in_section = true;
        } else if (preg_match('/^Version /i', $line) || ($in_section && preg_match('/^\s*== /', $line))) {
            $last = count($new_changelog) - 1;
            if ($new_changelog[$last] == '') {
                unset($new_changelog[$last]);
            }
            $current_version_stopped = true;
            if (! $in_section) {
                $new_changelog[] = '';
                $new_changelog[] = "    == $item_type ==";
            }
            $new_changelog[] = $reference_line_to_plugin;
            $new_changelog[] = '';
        } else if ($in_section && preg_match('/^\s*\* '. preg_quote($plugin) .':/i', $line)) {
            $current_version_stopped = true;
            $line            = $reference_line_to_plugin;
        }
    }
    $new_changelog[] = $line;
}

file_put_contents('ChangeLog', implode(PHP_EOL, $new_changelog). PHP_EOL);

?>
