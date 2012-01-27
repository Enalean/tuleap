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
 * Usage: php gitLog.php --group_name="gpig" --login="disciplus_simplex" --type="git_commit" --repo_name="gpigRepo" --commits_number="12"
 */

require_once(dirname(__FILE__).'/../include/GitRepository.class.php');
require_once('pre.php');

// Check script parameters
if ($argc != 7) {
    error("Wrong number of arguments");
}

$params = array();
foreach ($argv as $arg) {
    if (preg_match('/^--(.*)=(.*)$/', $arg, $matches)) {
        $params[$matches[1]] = $matches[2];
    }
}

$repositoryName  = $params['repo_name'];
$userTuleapLogin = $params['login'];
$groupName       = $params['group_name'];
$nbCommits       = $params['commits_number'];
$gitoliteUser    = $params['gitolite_user'];
$pushTimestamp   = $params['push_timestamp'];

logGitPushes($repositoryName, $userTuleapLogin, $groupName, $pushTimestamp, $nbCommits, $gitoliteUser);

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
 * @param String  $repositoryName Name of the git repository
 * @param String  $identifier     Name of the gitshell user that performed the push, retrived from whoami output.
 * @param String  $projectName    Unix name of the project
 * @param Integer $pushTimestamp  Date of the commit
 * @param Integer $commitsNumber  Number of commits
 * @param String  $gitoliteUser   Name of the gitolite user that performed the push, retrived from environment var $GL_USER.
 *
 * @return void
 */
function logGitPushes($repositoryName, $identifier, $projectName, $pushTimestamp, $commitsNumber, $gitoliteUser) {
    $repository = new GitRepository();
    $repoId = $repository->getRepositoryIDByName($repositoryName, $projectName);
    $repository->setId($repoId);
    try {
        $repository->load();
    } catch (Exception $e) {
        error("Unable to load repository");
    }
    $repository->logGitPush($identifier, $pushTimestamp, $commitsNumber, $gitoliteUser);
}

?>
