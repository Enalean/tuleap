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

$logger = new TruncateLevelLogger(
    new BackendLogger(),
    Config::get('sys_logger_level')
);
$git_dao                = new GitDao();
$user_manager           = UserManager::instance();
$git_repository_factory = new GitRepositoryFactory(
    $git_dao,
    ProjectManager::instance()
);

if ($argv[1] == "--init") {
    $repository_path = $argv[2];
    $user_name       = $argv[3];

    $git_plugin = PluginManager::instance()->getPluginByName('git');

    $manifest_manager = new Git_Mirror_ManifestManager(
        new Git_Mirror_MirrorDataMapper(
            new Git_Mirror_MirrorDao(),
            $user_manager
        ),
        $logger,
        $git_plugin->getConfigurationParameter('grokmanifest_path')
    );
    $repository = $git_repository_factory->getFromFullPath($repository_path);
    if ($repository) {
        $manifest_manager->triggerUpdate($repository);
    }
} else {
    $repository_path = $argv[1];
    $user_name       = $argv[2];
    $old_rev         = $argv[3];
    $new_rev         = $argv[4];
    $refname         = $argv[5];
    try {

        $git_exec = new Git_Exec($repository_path, $repository_path);


        $post_receive = new Git_Hook_PostReceive(
            new Git_Hook_LogAnalyzer(
                $git_exec,
                $logger
            ),
            $git_repository_factory,
            $user_manager,
            new Git_Ci_Launcher(
                new Jenkins_Client(
                    new Http_Client()
                ),
                new Git_Ci_Dao(),
                $logger
            ),
            new Git_Hook_ParseLog(
                new Git_Hook_LogPushes(
                    $git_dao
                ),
                new Git_Hook_ExtractCrossReferences(
                    $git_exec,
                    ReferenceManager::instance()
                ),
                $logger
            )
        );

        $post_receive->execute($repository_path, $user_name, $old_rev, $new_rev, $refname);
    } catch (Exception $exception) {
        $logger->error("[git post-receive] $repository_path $user_name $refname $old_rev $new_rev ".$exception->getMessage());
        exit(1);
    }
}