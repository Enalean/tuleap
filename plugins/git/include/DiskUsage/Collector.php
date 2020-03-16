<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\Git\DiskUsage;

use Statistics_DiskUsageManager;
use Git_LogDao;
use ForgeConfig;
use Project;
use DateTime;

class Collector
{
    /**
     * @var Statistics_DiskUsageManager
     */
    private $disk_usage_manager;

    /**
     * @var Git_LogDao
     */
    private $git_log_dao;

    /**
     * @var Retriever
     */
    private $retriever;

    public function __construct(
        Statistics_DiskUsageManager $disk_usage_manager,
        Git_LogDao $git_log_dao,
        Retriever $retriever
    ) {
        $this->disk_usage_manager = $disk_usage_manager;
        $this->git_log_dao        = $git_log_dao;
        $this->retriever          = $retriever;
    }

    public function collectForGitoliteRepositories(Project $project)
    {
        $yesterday           = new DateTime("yesterday midnight");
        $yesterday_timestamp = $yesterday->getTimestamp();
        $project_id          = $project->getID();

        if ($this->git_log_dao->hasRepositoriesUpdatedAfterGivenDate($project_id, $yesterday_timestamp) ||
            ! $this->git_log_dao->hasRepositories($project->getID())) {
            return $this->extractInFileSystem($project);
        }

        return $this->getDiskUsageForYesterday($project);
    }

    private function getDiskUsageForYesterday(Project $project)
    {
        return $this->retriever->getLastSizeForProject($project);
    }

    private function extractInFileSystem(Project $project)
    {
        $git_shell_size = (int) $this->getGitShellSizeOnFileSystem($project);
        $giolite_size   = (int) $this->getGitoliteSizeOnFileSystem($project);

        return $git_shell_size + $giolite_size;
    }

    private function getGitoliteSizeOnFileSystem(Project $project)
    {
        $path = ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/' . $project->getUnixNameLowerCase();

        return $this->disk_usage_manager->getDirSize($path);
    }

    private function getGitShellSizeOnFileSystem(Project $project)
    {
        $path = ForgeConfig::get('sys_data_dir') . '/gitroot/' . $project->getUnixNameLowerCase();

        return $this->disk_usage_manager->getDirSize($path);
    }
}
