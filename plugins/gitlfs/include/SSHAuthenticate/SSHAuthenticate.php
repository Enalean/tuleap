<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\GitLFS\SSHAuthenticate;

class SSHAuthenticate
{
    /**
     * @var \GitRepositoryFactory
     */
    private $git_repository_factory;
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \gitlfsPlugin
     */
    private $plugin;

    public function __construct(\ProjectManager $project_manager, \UserManager $user_manager, \GitRepositoryFactory $git_repository_factory, \gitlfsPlugin $plugin = null)
    {
        $this->project_manager        = $project_manager;
        $this->user_manager           = $user_manager;
        $this->git_repository_factory = $git_repository_factory;
        $this->plugin                 = $plugin;
    }

    public function main($username, array $argv)
    {
        if ($this->plugin === null) {
            throw new InvalidCommandException('git-lfs-authenticate is not available when Tuleap gitlfs plugin is disabled');
        }

        if (count($argv) !== 3) {
            throw new InvalidCommandException('git-lfs-authenticate must have 2 args');
        }

        if (! in_array($argv[2], ['download', 'upload'], true)) {
            throw new InvalidCommandException('git-lfs-authenticate arg 2 must be either `download` or `upload`');
        }

        $repository_path = explode('/', $argv[1]);
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($repository_path[0]);
        if ($project === null || $project->isActive() !== true) {
            throw new InvalidCommandException('git-lfs-authenticate arg 1 must be in a valid project');
        }

        if (! $this->plugin->isAllowed($project->getID())) {
            throw new InvalidCommandException('git-lfs-authenticate project is not allowed to do git-lfs');
        }

        $repository = $this->git_repository_factory->getRepositoryByPath($project->getID(), $argv[1]);
        if ($repository === null) {
            throw new InvalidCommandException('git-lfs-authenticate arg 1 must be a valid repository');
        }

        $user = $this->user_manager->getUserByUserName($username);
        if ($user === null || ! $user->isAlive()) {
            throw new InvalidCommandException('git-lfs-authenticate arg 1 must be a valid user');
        }

        if (! $repository->userCanRead($user)) {
            throw new InvalidCommandException('git-lfs-authenticate: user cannot access this repository');
        }
    }
}
