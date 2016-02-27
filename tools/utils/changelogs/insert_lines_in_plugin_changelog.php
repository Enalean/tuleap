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

$plugin_path       = $argv[1];
$plugin_version    = $argv[2];
$changelog_message = $argv[3];
$tuleap_version    = $argv[4];

$changelog_file_path = $plugin_path . '/ChangeLog';

if(! is_file($changelog_file_path)) {
    file_put_contents($changelog_file_path, '');
}

$changelog_content  = file($changelog_file_path, FILE_IGNORE_NEW_LINES);
$new_content_to_add = array(
    "Version $plugin_version - Tuleap $tuleap_version",
    "    * $changelog_message",
    ''
);

$new_changelog_content = array_merge($new_content_to_add, $changelog_content);

file_put_contents($changelog_file_path, implode(PHP_EOL, $new_changelog_content). PHP_EOL);