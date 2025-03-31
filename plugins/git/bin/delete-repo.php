#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

$argv = $_SERVER['argv'] ?? [];

if (count($argv) !== 2) {
    fwrite(STDERR, "missing parameters\n");
    exit(1);
}

[, $repo_path] = $argv;

if ($repo_path === '' || ! is_writable($repo_path)) {
    throw new GitDriverErrorException('Empty path or permission denied ' . $repo_path);
}
\Psl\Filesystem\delete_directory($repo_path, true);
