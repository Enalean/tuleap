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

/**
 * This script is called by post-receive-trigger-ci hook in order to trigger CI build after a git push
 *
 * Usage: php gitTriggerCi.php --repo_location="/path/to/git/repository/repositoryName.git"
 */

require_once('pre.php');
require_once(dirname(__FILE__).'/../include/gitPlugin.class.php');

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

if (isset($params['repo_location']) && !empty($params['repo_location'])) {
    $launcher = new Git_Ci_Launcher(
        new GitRepositoryFactory(
            new GitDao(),
            ProjectManager::instance()
        ),
        new Jenkins_Client(
            new Http_Client()
        ),
        new Git_Ci_Dao()
    );
    $launcher->launchForLocation($params['repo_location']);
}

/**
 * Print an error then exit
 *
 * @param String $msg Error message to display
 *
 * @return Void
 */
function error($msg) {
    echo "*** Error: $msg".PHP_EOL;
    exit(1);
}

?>