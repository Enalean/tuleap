<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$DIR = dirname(__FILE__);
require_once($DIR.'/../include/GitDao.class.php');

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

$repositoryName = $params['repo_name'];
$userTuleapLogin = $params['login'];
$nbCommits = $params['commits_number'];

logGitPushes($repositoryName, $userTuleapLogin, $nbCommits, 101);

// Functions
function error($msg) {
    echo "*** Error: $msg".PHP_EOL;
    exit(1);
}

function logGitPushes($repositoryName, $identifier, $nbCommits, $projectId) {
        $um = UserManager::instance();
        $user = $um->getUserByIdentifier($identifier);
        $userId = $user->getId();
        /*@TODO: ** Retrieve Repository id from its name. 
                 ** Move the whole stuff to a higher layer.
         */

        $dao = new GitDao();
        $repoId = 0;
        $dar = $dao->getProjectRepositoryIDByName($repositoryName, $projectId);
        if ($dar && !empty($dar) && !$dar->isError()) {
            while ($row = $dar->getRow()) {
                $repoId = $row[GitDao::REPOSITORY_ID];
                        }
        }
        $dao->logGitPush($repoId, $userId, $nbCommits);
}

?>
