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
        private \Psr\Log\LoggerInterface $logger,
        private ProjectByIDFactory $project_factory,
        string $gitolite_administration_path,
    ) {
        $this->gitolite_configuration_directory = $gitolite_administration_path . '/conf/';
    }

    public function writeGitoliteConfiguration(): GitoliteAdministrationPendingChanges
    {
        $pending_changes = new GitoliteAdministrationPendingChanges();

        $this->writeGitoliteConfigurationFile(
            $this->getGitoliteConfFilePath(),
            $this->permissions_serializer->getGitoliteDotConf($this->getProjectList()),
            $pending_changes
        );

        return $pending_changes;
    }

    public function renameProject(int $project_id, string $old_name): GitoliteAdministrationPendingChanges
    {
        $pending_changes = new GitoliteAdministrationPendingChanges();

        $project = $this->project_factory->getProjectById($project_id);

        if ($this->dumpProjectRepoConf($project)->areTherePendingChangesThatMustBeApplied()) {
            $pending_changes->markAsChangesPending();
        }

        $old_conf_file_path = $this->getProjectPermissionConfFilePath($old_name);
        if ($old_name !== $project->getUnixName() && \Psl\Filesystem\is_file($old_conf_file_path)) {
            \Psl\Filesystem\delete_file($old_conf_file_path);
        }

        if ($this->writeGitoliteConfiguration()->areTherePendingChangesThatMustBeApplied()) {
            $pending_changes->markAsChangesPending();
        }

        return $pending_changes;
    }

    public function dumpProjectRepoConf(Project $project): GitoliteAdministrationPendingChanges
    {
        $pending_changes = new GitoliteAdministrationPendingChanges();

        $config_file_content = $this->project_serializer->dumpProjectRepoConf($project);
        $this->writeProjectConfigurationFile($project, $config_file_content, $pending_changes);

        return $pending_changes;
    }

    public function dumpSuspendedProjectRepositoriesConfiguration(Project $project): GitoliteAdministrationPendingChanges
    {
        $pending_changes = new GitoliteAdministrationPendingChanges();

        $config_file_content = $this->project_serializer->dumpSuspendedProjectRepositoriesConfiguration($project);
        $this->writeProjectConfigurationFile($project, $config_file_content, $pending_changes);

        return $pending_changes;
    }

    private function writeProjectConfigurationFile(
        Project $project,
        string $config_file_content,
        GitoliteAdministrationPendingChanges $pending_changes,
    ): void {
        $this->logger->debug('Get Project Permission Conf File: ' . $project->getUnixName() . '...');
        $config_file = $this->getProjectPermissionConfFilePath($project->getUnixName());
        $this->logger->debug('Get Project Permission Conf File: ' . $project->getUnixName() . ': done');

        $this->logger->debug('Write Git config: ' . $project->getUnixName() . '...');
        $this->writeGitoliteConfigurationFile($config_file, $config_file_content, $pending_changes);
        $this->logger->debug('Write Git config: ' . $project->getUnixName() . ': done');
    }

    /**
     * @return non-empty-string
     */
    private function getProjectPermissionConfFilePath(string $project_unix_name): string
    {
        return $this->getGitoliteProjectConfDirectoryPath() . '/' . $project_unix_name . '.conf';
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
     * @return list<string>
     */
    private function getProjectList(): array
    {
        return $this->readProjectListFromPath($this->getGitoliteProjectConfDirectoryPath());
    }

    /**
     * @param non-empty-string $dir_path
     * @return list<string>
     */
    private function readProjectListFromPath(string $dir_path): array
    {
        $project_names = [];

        if (! is_dir($dir_path)) {
            return $project_names;
        }

        $dir = new DirectoryIterator($dir_path);
        foreach ($dir as $file) {
            if (! $file->isDot()) {
                $project_names[] = basename($file->getFilename(), '.conf');
            }
        }
        return $project_names;
    }

    /**
     * @param non-empty-string $file_path
     */
    private function writeGitoliteConfigurationFile(string $file_path, string $expected_content, GitoliteAdministrationPendingChanges $pending_changes): void
    {
        if (\Psl\Filesystem\is_file($file_path) && (\Psl\File\read($file_path) === $expected_content)) {
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
