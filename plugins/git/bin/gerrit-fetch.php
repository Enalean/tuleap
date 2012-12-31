<?php
/**
 * Copyright (c) Enalean, 2012. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

//Bootstrapping- there's probably a better way to do this!
set_include_path(get_include_path() . ':' . dirname(__FILE__).'/../../../src');
set_include_path(get_include_path() . ':' . dirname(__FILE__).'/../../../src/www/include');
require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
require_once(dirname(__FILE__) . '/../include/GitRepository.class.php');
require_once(dirname(__FILE__) . '/../include/Git/Driver/Gerrit/ProjectCreator.class.php');

$remote_name = Git_Driver_Gerrit_ProjectCreator::GERRIT_REMOTE_NAME;

$repository = new GitRepository();
//semi_hardcoding this as I don't know how else to get the path
$repository_dir = $repository->getGitRootPath() . '../gitolite/repositories/';

$gitDao = new GitDao();
$paths = $gitDao->getRepositoryPathsWithRemoteServersForAllProjects();

foreach ($paths as $path) {

    $repository_path = $repository_dir . $path['repository_path'];
    if (! is_dir($repository_path)) {
        continue;
    }
    
    //get a list of remote heads
    $remote_heads = array();        
    exec("cd $repository_path && git-ls-remote --heads $remote_name", $remote_heads);

    foreach ($remote_heads as $remote_head) {
        //extract the branch name- maybe use a regex
        $branch_name = substr($remote_head, 52);
        
        //updating the local repository with the remote content
        `cd $repository_path && git fetch $remote_name -q && git update-ref refs/heads/$branch_name refs/remotes/$remote_name/$branch_name`;  
    }
}
?>
