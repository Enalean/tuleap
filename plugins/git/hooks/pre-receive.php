#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright Enalean (c) 2023-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';

$logger = BackendLogger::getDefaultLogger();

if (isset($argv[1])) {
    $repository_path = $argv[1];
} else {
    throw new \RuntimeException('No git repository path was supplied in pre-receive.php');
}

$user_name = getenv('GL_USER');
if ($user_name === false) {
    $user_informations = posix_getpwuid(posix_geteuid());
    $user_name         = $user_informations['name'];
}

while ($line = fgets(STDIN)) {
    $revs = explode(' ', trim($line));
    if (count($revs) === 3) {
        $logger->log('debug', "[pre-receive] $repository_path $user_name $revs[0] $revs[1] $revs[2]");
    } else {
        throw new \RuntimeException('Wrong number of arguments submitted, three arguments of the form old_rev new_rev refname expected on STDIN');
    }
}

exit(0);
