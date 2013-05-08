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
    ini_set('include_path', '/usr/share/pear:/usr/share/codendi/src/www/include:/usr/share/codendi/src:.');

    require_once "pre.php";
    require_once "common/dao/SvnCommitsDao.class.php";
    require_once 'common/svn/SVN_CommitMessage.class.php';

    $repository         = $argv[1];
    $revision           = $argv[2];
    $user               = $argv[3];
    $old_commit_message = stream_get_contents(STDIN);

    $svn_commit_message = new SVN_CommitMessageUpdate(
        new SVN_Hooks(ProjectManager::instance(), UserManager::instance()),
        ReferenceManager::instance(),
        new SvnCommitsDao()
    );
    $svn_commit_message->update($repository, $revision, $user, $old_commit_message);
    exit(0);
} catch(Exception $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}

?>