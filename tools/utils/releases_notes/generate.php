#!/usr/bin/php
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

require_once 'ChangeLogReader.class.php';

$formats = array('html', 'frs', 'marketing');
if ($argc < 2 || !in_array($argv[1], $formats)) {
    echo "\nUsage: {$argv[0]} <format> [<version>]\n
    with <format>  = marketing | html | frs
         <version> = 5.6 (by default it looks into the VERSION file)
    ";
    die();
}
$format  = $argv[1];
$version = isset($argv[2]) ? $argv[2] : null;

$reader  = new ChangeLogReader($version);
$release = $reader->parse();
include 'templates/'. $format .'/index.php';

?>
