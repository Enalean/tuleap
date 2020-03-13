#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright Enalean (c) 2011 - Present. All rights reserved.
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

try {
    /** @psalm-suppress MissingFile This file is deployed under /usr/lib/tuleap/, an absolute path is needed */
    require_once '/usr/share/tuleap/src/www/include/pre.php';

    $repository         = $argv[1];
    $propname           = $argv[4];
    $action             = $argv[5];
    $new_commit_message = stream_get_contents(STDIN);

    $svn_commit_message = new SVN_Hook_PreRevPropset(
        new SVN_Hooks(ProjectManager::instance(), UserManager::instance()),
        new SVN_CommitMessageValidator(ReferenceManager::instance())
    );
    $svn_commit_message->assertCanBeModified($repository, $action, $propname, $new_commit_message);
    exit(0);
} catch (Exception $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
