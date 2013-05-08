#!/usr/bin/php
<?php

//Copyright (c) Enalean, 2013. All Rights Reserved.
//
//This file is a part of Tuleap.
//
//Tuleap is free software; you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation; either version 2 of the License, or
//(at your option) any later version.
//
//Tuleap is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
//

try {
    ini_set('include_path', '/usr/share/pear:/usr/share/codendi/src/www/include:/usr/share/codendi/src:.');

    require_once "pre.php";
    require_once "common/dao/SvnCommitsDao.class.php";
    require_once 'common/svn/SVN_CommitMessage.class.php';

    $repository = $argv[1];
    $revision   = $argv[2];
    $user       = $argv[3];

    $svn_commit_message = new SVN_CommitMessageUpdate(
        ProjectManager::instance(),
        UserManager::instance(),
        ReferenceManager::instance(),
        new SvnCommitsDao()
    );
    $svn_commit_message->update($repository, $revision, $user);
    exit(0);
} catch(Exception $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}

?>