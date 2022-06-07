#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once __DIR__ . '/../../../src/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

$argv = $_SERVER['argv'] ?? [];

if (count($argv) !== 4) {
    fwrite(STDERR, "missing parameters\n");
    exit(1);
}

[, $repo_path, $new_branch, $reference] = $argv;

// Use the same umask than gitolite, see /var/lib/gitolite/.gitolite.rc
umask(0007);

$git_exec = new Git_Exec($repo_path, $repo_path);
$git_exec->updateRef($new_branch, $reference);
