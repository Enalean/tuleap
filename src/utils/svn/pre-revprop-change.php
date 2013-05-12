#!/usr/bin/php
<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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
    require_once 'env.inc.php';
    require_once 'pre.php';
    require_once 'common/svn/hook/PreRevPropset.class.php';

    $repository         = $argv[1];
    $propname           = $argv[4];
    $action             = $argv[5];
    $new_commit_message = stream_get_contents(STDIN);

    $svn_commit_message = new SVN_Hook_PreRevPropset(
        new SVN_Hooks(ProjectManager::instance(), UserManager::instance()),
        ReferenceManager::instance()
    );
    $svn_commit_message->assertCanBeModified($repository, $action, $propname, $new_commit_message);
    exit(0);
} catch(Exception $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}

?>