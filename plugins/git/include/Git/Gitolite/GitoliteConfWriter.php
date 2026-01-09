<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

declare(strict_types=1);

use Tuleap\Git\Gitolite\GitoliteAdministrationPendingChanges;
use Tuleap\Project\ProjectByIDFactory;

readonly class Git_Gitolite_GitoliteConfWriter
{
    /**
     * @var non-empty-string
     */
    private string $gitolite_configuration_directory;

    public function __construct(
        private Git_Gitolite_ConfigPermissionsSerializer $permissions_serializer,
        private Git_Gitolite_ProjectSerializer $project_serializer,
        private GitDao $dao,
        private \Psr\Log\LoggerInterface $logger,
        private ProjectByIDFactory $project_factory,
        string $gitolite_administration_path,
    ) {
        $this->gitolite_configuration_directory = $gitolite_administration_path . '/conf/';
    }

    public function dumpProjectRepoConf(Project $project): GitoliteAdministrationPendingChanges
    {
        $pending_changes = new GitoliteAdministrationPendingChanges();

        $this->buildAndWriteRepoConfiguration($project, $pending_changes);
        $this->buildAndWriteGitoliteConfiguration($pending_changes);

        return $pending_changes;
    }

    private function buildAndWriteRepoConfiguration(
        Project $project,
        GitoliteAdministrationPendingChanges $pending_changes,
    ): void {
        $config_file_content = $this->project_serializer->dumpProjectRepoConf($project);
        $this->writeProjectConfigurationFile($project, $config_file_content, $pending_changes);
    }

    private function buildAndWriteGitoliteConfiguration(GitoliteAdministrationPendingChanges $pending_changes): void
    {
        $project_ids                          = $this->dao->searchProjectsWithActiveRepositories();
        $expected_project_configuration_paths = [];
        foreach ($project_ids as $project_id) {
            $expected_file_path                     = $this->getProjectPermissionConfFilePath($project_id);
            $expected_project_configuration_paths[] = $expected_file_path;
            if (\Psl\Filesystem\is_file($expected_file_path)) {
                continue;
            }
            $this->buildAndWriteRepoConfiguration(
                $this->project_factory->getProjectById($project_id),
                $pending_changes,
            );
        }

        $this->writeGitoliteConfigurationFile(
            $this->getGitoliteConfFilePath(),
            $this->permissions_serializer->getGitoliteDotConf($project_ids),
            $pending_changes
        );

        $current_project_configuration_paths  = \Psl\Filesystem\read_directory($this->getGitoliteProjectConfDirectoryPath());
        $not_used_project_configuration_paths = \Psl\Dict\diff($current_project_configuration_paths, $expected_project_configuration_paths);
        foreach ($not_used_project_configuration_paths as $not_used_project_configuration_path) {
            try {
                \Psl\Filesystem\delete_file($not_used_project_configuration_path);
            } catch (\Psl\File\Exception\NotFoundException $exception) {
                // Ignore file not found, our goal is to cleanup the directory
                // If the file is already not present (e.g. deleted by another process) that goal is accomplished
            }
        }
    }

    private function writeProjectConfigurationFile(
        Project $project,
        string $config_file_content,
        GitoliteAdministrationPendingChanges $pending_changes,
    ): void {
        $project_id = (int) $project->getID();

        $this->logger->debug('Get Project Permission Conf File: #' . $project_id . '...');
        $config_file = $this->getProjectPermissionConfFilePath($project_id);
        $this->logger->debug('Get Project Permission Conf File: #' . $project_id . ': done');

        $this->logger->debug('Write Git config: #' . $project_id . '...');
        $this->writeGitoliteConfigurationFile($config_file, $config_file_content, $pending_changes);
        $this->logger->debug('Write Git config: #' . $project_id . ': done');
    }

    /**
     * @return non-empty-string
     */
    private function getProjectPermissionConfFilePath(int $project_id): string
    {
        return $this->getGitoliteProjectConfDirectoryPath() . '/' . $project_id . '.conf';
    }

    /**
     * @return non-empty-string
     */
    private function getGitoliteConfFilePath(): string
    {
        $this->createGitoliteConfigurationDirectory($this->gitolite_configuration_directory);
        return $this->gitolite_configuration_directory . '/gitolite.conf';
    }

    /**
     * @return non-empty-string
     */
    private function getGitoliteProjectConfDirectoryPath(): string
    {
        $this->createGitoliteConfigurationDirectory($this->gitolite_configuration_directory);
        $gitolite_project_conf_directory = $this->gitolite_configuration_directory . '/projects';
        $this->createGitoliteConfigurationDirectory($gitolite_project_conf_directory);

        return $gitolite_project_conf_directory;
    }

    /**
     * @param non-empty-string $file_path
     */
    private function writeGitoliteConfigurationFile(string $file_path, string $expected_content, GitoliteAdministrationPendingChanges $pending_changes): void
    {
        $file_exist = \Psl\Filesystem\is_file($file_path);

        if ($file_exist && $expected_content === '') {
            \Psl\Filesystem\delete_file($file_path);
        }

        if ($file_exist && (\Psl\File\read($file_path) === $expected_content)) {
            return;
        }

        \Tuleap\File\FileWriter::writeFile(
            $file_path,
            $expected_content,
            0660,
        );
        $success_chgrp = chgrp($file_path, 'gitolite');
        if ($success_chgrp === false) {
            throw new \RuntimeException('Not able to set the gitolite group on ' . $file_path);
        }

        $pending_changes->markAsChangesPending();
    }

    /**
     * @param non-empty-string $path
     */
    private function createGitoliteConfigurationDirectory(string $path): void
    {
        if (\Psl\Filesystem\is_directory($path)) {
            return;
        }
        \Psl\Filesystem\create_directory($path, 0770);
        $success_chgrp = chgrp($path, 'gitolite');
        if ($success_chgrp === false) {
            throw new \RuntimeException('Was not able to set the gitolite group on ' . $path);
        }
    }
}
