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

require_once 'pre.php';

$repository_path = $argv[1];
$user_name       = $argv[2];
$old_rev         = $argv[3];
$new_rev         = $argv[4];
$refname         = $argv[5];

$git_exec = new Git_Exec($repository_path, $repository_path);

$post_receive = new Git_Hook_PostReceive(
    $git_exec,
    new GitRepositoryFactory(
        new GitDao(),
        ProjectManager::instance()
    ),
    UserManager::instance(),
    new Git_Hook_ExtractCrossReferences(
        $git_exec,
        ReferenceManager::instance()
    ),
    new Git_Ci_Launcher(
        new Jenkins_Client(
            new Http_Client()
        ),
        new Git_Ci_Dao()
    )
);

$post_receive->execute($repository_path, $user_name, $old_rev, $new_rev, $refname);

?>
