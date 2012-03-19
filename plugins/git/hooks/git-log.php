<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 *
 * This script is called by post-receive-log hook in order to store git pushes in Tuleap db.
 *
 * Usage: php gitLog.php --repo_location="/data/lib/tuleap/gitolite/repositories/gpig/u/disciplus_simplex/repo.git" --login="disciplus_simplex" --type="git_commit" --commits_number="12"
 */

require_once(dirname(__FILE__).'/../include/GitRepository.class.php');
require_once('pre.php');

// Check script parameters
if ($argc != 6) {
    error("Wrong number of arguments");
}

$params = array();
foreach ($argv as $arg) {
    if (preg_match('/^--(.*)=(.*)$/', $arg, $matches)) {
        $params[$matches[1]] = $matches[2];
    }
}

$repoLocation    = $params['repo_location'];
$userTuleapLogin = $params['login'];
$nbCommits       = $params['commits_number'];
$gitoliteUser    = $params['gitolite_user'];
$pushTimestamp   = $params['push_timestamp'];

logGitPushes($repoLocation, $userTuleapLogin, $pushTimestamp, $nbCommits, $gitoliteUser);

/**
 * Pint an error then exit
 *
 * @param String $msg Error message to display
 *
 * @return void
 */
function error($msg) {
    echo "*** Error: $msg".PHP_EOL;
    exit(1);
}

/**
 * Store details about the push in the DB
 *
 * @param String  $repositoryLocation Name of the git repository
 * @param String  $identifier     Name of the gitshell user that performed the push, retrived from whoami output.
 * @param Integer $pushTimestamp  Date of the commit
 * @param Integer $commitsNumber  Number of commits
 * @param String  $gitoliteUser   Name of the gitolite user that performed the push, retrived from environment var $GL_USER.
 *
 * @return void
 */
function logGitPushes($repositoryLocation, $identifier, $pushTimestamp, $commitsNumber, $gitoliteUser) {
    $repoFactory = new GitRepositoryFactory(new GitDao(), ProjectManager::instance());
    $repository  = $repoFactory->getFromFullPath($repositoryLocation);
    if ($repository) {
        $repository->logGitPush($identifier, $pushTimestamp, $commitsNumber, $gitoliteUser);
    }
}

?>
