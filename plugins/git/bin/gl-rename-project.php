<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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
 * Rename project in gitolite configuration
 */

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/Git_GitoliteDriver.php';
require_once __DIR__ . '/../include/GitRepositoryUrlManager.php';

if ($argc !== 2) {
    echo 'Usage: ' . $argv[0] . ' project_id' . PHP_EOL;
    exit(1);
}

$git_plugin = PluginManager::instance()->getPluginByName('git');
\assert($git_plugin instanceof GitPlugin);
$url_manager     = new Git_GitRepositoryUrlManager($git_plugin);
$project_manager = ProjectManager::instance();
$driver          = new Git_GitoliteDriver(
    $git_plugin->getLogger(),
    $url_manager,
    new GitDao(),
    $git_plugin,
    new \Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager(
        new \Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationDao(),
        ProjectManager::instance()
    ),
    new \Tuleap\Process\SymfonyProcessFactory(),
    null,
    null,
    null,
    $project_manager,
);
$driver->dumpProjectRepoConf($project_manager->getProject((int) $argv[1]));
