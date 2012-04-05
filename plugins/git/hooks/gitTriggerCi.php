<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../include/GitRepository.class.php');
require_once('pre.php');

// Check script parameters
if ($argc != 2) {
    error("Wrong number of arguments");
}

$params = array();
foreach ($argv as $arg) {
    if (preg_match('/^--(.*)=(.*)$/', $arg, $matches)) {
        $params[$matches[1]] = $matches[2];
    }
}

triggerCiBuild($params['repo_location']);

/**
 * Pint an error then exit
 *
 * @param String $msg Error message to display
 *
 * @return Void
 */
function error($msg) {
    echo "*** Error: $msg".PHP_EOL;
    exit(1);
}

/**
 * Trigger jobs corresponding to the Git repository
 *
 * @param String $repositoryLocation Name of the git repository
 *
 * @return Void
 */
function triggerCiBuild($repositoryLocation) {
    $pm = ProjectManager::instance();
    $repoFactory = new GitRepositoryFactory(new GitDao(), $pm);
    $repository  = $repoFactory->getFromFullPath($repositoryLocation);
    if ($repository) {
        if ($repository->getProject()->usesService('hudson')) {
            $gitCi = new Git_CI();
            $gitCi->triggerCiBuild($repository->getId());
        }
    }
}

?>