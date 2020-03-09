<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

if (! isset($argv[1])) {
    exit(1);
}

$lockfile_path = "$argv[1]/package-lock.json";

if (! file_exists($lockfile_path)) {
    exit(0);
}

$lockfile = json_decode(file_get_contents($lockfile_path), true, 512, JSON_THROW_ON_ERROR);

if (! isset($lockfile['dependencies'])) {
    exit(0);
}

$lockfile_without_local_deps = $lockfile;

foreach ($lockfile['dependencies'] as $name => $info) {
    if (strpos($name, '@tuleap/') === 0 && strpos($info['version'], 'file:') === 0) {
        unset($lockfile_without_local_deps['dependencies'][$name]);
    }
}

file_put_contents(
    $lockfile_path,
    str_replace(
        '    ',
        '  ',
        json_encode($lockfile_without_local_deps, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ) . PHP_EOL
);
