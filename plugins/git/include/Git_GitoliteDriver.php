<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 */

use Symfony\Component\Process\Process;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\PathJoinUtil;

/**
 * This class manage the interaction between Tuleap and Gitolite
 * Warning: as gitolite "interface" is made through a git repository
 * we need to execute git commands. Those commands are very sensitive
 * to the environement (especially the current working directory).
 * So this class expect to work in Tuleap's Gitolite admin directory
 * all the time (chdir in constructor/setAdminPath) and change back to
 * the previous location after push.
 * If you want to re-do some gitolite stuff after push; you have to either
 * + Use new object
 * + Call setAdminPath again
 * And if you don't push, you will stay in Gitolite admin directory!
 *
 */
class Git_GitoliteDriver
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Git_Exec
     */
    private $gitExec;

    protected $oldCwd;
    protected $confFilePath;
    protected $adminPath;

    /** @var Git_Gitolite_GitoliteConfWriter  */
    private $gitolite_conf_writer;

    /** @var GitDao */
    private $git_dao;

    public static $permissions_types = [
        Git::PERM_READ  => ' R  ',
        Git::PERM_WRITE => ' RW ',
        Git::PERM_WPLUS => ' RW+',
    ];

    public const OLD_AUTHORIZED_KEYS_PATH = '/usr/com/gitolite/.ssh/authorized_keys';
    public const NEW_AUTHORIZED_KEYS_PATH = '/var/lib/gitolite/.ssh/authorized_keys';

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        Git_GitRepositoryUrlManager $url_manager,
        GitDao $git_dao,
        GitPlugin $git_plugin,
        BigObjectAuthorizationManager $big_object_authorization_manager,
        ?Git_Exec $gitExec = null,
        ?GitRepositoryFactory $repository_factory = null,
        ?Git_Gitolite_ConfigPermissionsSerializer $permissions_serializer = null,
        ?Git_Gitolite_GitoliteConfWriter $gitolite_conf_writer = null,
        ?ProjectManager $project_manager = null,
    ) {
        $this->git_dao = $git_dao;
        $this->logger  = $logger;
        $adminPath     = ForgeConfig::get('sys_data_dir') . '/gitolite/admin';
        $this->setAdminPath($adminPath);
        $this->gitExec      = $gitExec ?: new Git_Exec($adminPath);
        $repository_factory = $repository_factory ?: new GitRepositoryFactory(
            $this->getDao(),
            ProjectManager::instance()
        );

        $project_manager = $project_manager ?: ProjectManager::instance();

        if ($permissions_serializer === null) {
            $permissions_serializer = new Git_Gitolite_ConfigPermissionsSerializer(
                new Git_Driver_Gerrit_ProjectCreatorStatus(
                    new Git_Driver_Gerrit_ProjectCreatorStatusDao()
                ),
                $git_plugin->getEtcTemplatesPath(),
                $git_plugin->getFineGrainedRetriever(),
                $git_plugin->getFineGrainedFactory(),
                $git_plugin->getRegexpFineGrainedRetriever(),
                EventManager::instance()
            );
        }

        $project_serializer = new Git_Gitolite_ProjectSerializer(
            $this->logger,
            $repository_factory,
            $permissions_serializer,
            $url_manager,
            $big_object_authorization_manager,
        );

        $this->gitolite_conf_writer = $gitolite_conf_writer ? $gitolite_conf_writer : new Git_Gitolite_GitoliteConfWriter(
            $permissions_serializer,
            $project_serializer,
            new Git_Gitolite_GitoliteRCReader(),
            $this->logger,
            $project_manager,
            $adminPath
        );
    }

    /**
     * Getter for $adminPath
     *
     * @return string
     */
    public function getAdminPath()
    {
        return $this->adminPath;
    }

    /**
     * Get repositories path
     *
     * @return string
     */
    public function getRepositoriesPath()
    {
        return realpath($this->adminPath . '/../repositories');
    }

    public function setAdminPath($adminPath)
    {
        $this->oldCwd    = getcwd();
        $this->adminPath = $adminPath;
        chdir($this->adminPath);

        $this->confFilePath = 'conf/gitolite.conf';
    }

    /**
     * A driver is initialized if the repository has branches.
     *
     * @param string $repoPath
     * @return bool
     */
    public function isInitialized($repoPath)
    {
        try {
            $headsPath = $repoPath . '/refs/heads';
            if (is_dir($headsPath)) {
                $dir = new DirectoryIterator($headsPath);
                foreach ($dir as $fileinfo) {
                    if (! $fileinfo->isDot()) {
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            // If directory doesn't even exists, return false
        }
        return false;
    }

    /**
     *
     * @param string $repoPath
     * @return bool
     */
    public function isRepositoryCreated($repoPath)
    {
        $headsPath = $repoPath . '/refs/heads';
        return is_dir($headsPath);
    }

    /**
     * Save on filesystem all permission configuration for a project
     *
     */
    public function dumpProjectRepoConf(Project $project)
    {
        $git_modifications = $this->gitolite_conf_writer->dumpProjectRepoConf($project);

        if ($this->addModifiedConfigurationFiles($git_modifications)) {
            return $this->updateMainConfIncludes();
        }

        return false;
    }

    public function dumpSuspendedProjectRepositoriesConfiguration(Project $project)
    {
        $git_modifications = $this->gitolite_conf_writer->dumpSuspendedProjectRepositoriesConfiguration($project);

        if ($this->addModifiedConfigurationFiles($git_modifications)) {
            return $this->updateMainConfIncludes();
        }

        return false;
    }

    private function addModifiedConfigurationFiles(Git_Gitolite_GitModifications $git_modifications)
    {
        foreach ($git_modifications->toAdd() as $file) {
            if (! $this->gitExec->add($file)) {
                return false;
            }
        }

        return true;
    }

    public function updateMainConfIncludes()
    {
        $git_modifications         = $this->gitolite_conf_writer->writeGitoliteConfiguration();
        $files_are_correctly_added = true;

        foreach ($git_modifications->toAdd() as $touched_file) {
            $files_are_correctly_added = $files_are_correctly_added && $this->gitExec->add($touched_file);
        }

        return $files_are_correctly_added;
    }

    public function push()
    {
        $this->logger->debug('Pushing in gitolite admin repository...');
        $res = $this->gitExec->push();
        $this->logger->debug('Pushing in gitolite admin repository: done');
        chdir($this->oldCwd);

        return $res;
    }

    public function commit($message)
    {
        return $this->gitExec->commit($message);
    }

    /**
     * Rename a project
     *
     * This method is intended to be called by a "codendiadm" owned process while general
     * rename process is owned by "root" (system-event) so there is dedicated script
     * (see bin/gl-rename-project.php) and more details in Git_Backend_Gitolite::glRenameProject.
     *
     * @param String $oldName The old name of the project
     * @param String $newName The new name of the project
     *
     * @return bool true if success, false otherwise
     */
    public function renameProject($oldName, $newName)
    {
        $ok = true;

        $git_modifications = $this->gitolite_conf_writer->renameProject($oldName, $newName);

        foreach ($git_modifications->toAdd() as $file) {
            $ok = $ok && $this->gitExec->add($file);
        }

        foreach ($git_modifications->toMove() as $old_file => $new_file) {
            $ok = $ok && $this->gitExec->mv($old_file, $new_file);
        }

        if ($ok) {
            $ok = $this->gitExec->commit('Rename project ' . $oldName . ' to ' . $newName) && $this->gitExec->push();
        }

        return $ok;
    }

    public function delete(string $path): void
    {
        $this->logger->debug('Removing physically the repository...');
        $delete_process = new Process(
            ['sudo', '-u', 'gitolite', 'DISPLAY_ERRORS=true', __DIR__ . '/../bin/delete-repo.php', $path]
        );
        $delete_process->mustRun();
        $this->logger->debug('Removing physically the repository: done');
    }

    public function fork($repo, $old_ns, $new_ns)
    {
        $source = PathJoinUtil::unixPathJoin([$this->getRepositoriesPath(), $old_ns, $repo]) . '.git';
        $target = PathJoinUtil::unixPathJoin([$this->getRepositoriesPath(), $new_ns, $repo]) . '.git';

        $this->executeShellCommand('sudo -u gitolite /usr/share/tuleap/plugins/git/bin/gl-clone-bundle.sh ' . escapeshellarg($source) . ' ' . escapeshellarg($target));
    }

    protected function executeShellCommand($cmd)
    {
        $cmd = $cmd . ' 2>&1';
        exec($cmd, $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            throw new Git_Command_Exception($cmd, $output, $retVal);
        }
    }

    public function checkAuthorizedKeys()
    {
        $authorized_keys_file = $this->getAuthorizedKeysPath();
        if (filesize($authorized_keys_file) == 0) {
            throw new GitAuthorizedKeysFileException($authorized_keys_file);
        }
    }

    private function getAuthorizedKeysPath()
    {
        if (! file_exists(self::OLD_AUTHORIZED_KEYS_PATH)) {
            return self::NEW_AUTHORIZED_KEYS_PATH;
        }
        return self::OLD_AUTHORIZED_KEYS_PATH;
    }

    /**
     * Backup gitolite repository
     *
     * @param String $backup_directory The repository backup directory path
     *
     */
    public function backup(GitRepository $repository, $backup_directory)
    {
        if (! is_readable($repository->getFullPath())) {
            throw new GitDriverErrorException('Gitolite backup: Empty path or permission denied ' . $repository->getFullPath());
        }
        if (! is_writable($backup_directory)) {
            throw new GitDriverErrorException('Gitolite backup: Empty backup path or permission denied ' . $backup_directory);
        }

        $backup_path      = $this->getBackupPath($repository, $backup_directory);
        $target_directory = dirname($backup_path);

        if (! is_dir($target_directory)) {
            if (! mkdir($target_directory, 0700, true)) {
                throw new GitDriverErrorException('Unable to create git backup directory: ' . $target_directory);
            }
        }

        try {
            $exec    = new System_Command();
            $command = 'umask 77 && tar cvzf ' . escapeshellarg($backup_path) . ' ' . escapeshellarg($repository->getFullPath());
            $exec->exec($command);
            $command = 'chmod 640 ' . escapeshellarg($backup_path);
            $exec->exec($command);
            chgrp($backup_path, 'gitolite');
            $this->logger->info('[Gitolite][Backup] Repository backup done in [' . $backup_path . ']');
        } catch (System_Command_CommandException $exception) {
            $this->logger->error('[Gitolite][Backup] Error when backuping repository in [' . $backup_path . '] error message : ' . $exception->getMessage());
            throw new GitDriverErrorException($exception->getMessage());
        }
    }

    public function deleteBackup(GitRepository $repository, $backup_directory)
    {
        $archive = $this->getBackupPath($repository, $backup_directory);
        if (is_file($archive)) {
            if (! unlink($archive)) {
                $this->logger->error('Unable to delete archived Gitolite repository: ' . $archive);
                throw new GitDriverErrorException('Unable to purge archived Gitolite repository: ' . $archive);
            } else {
                $this->logger->info('Purge of Gitolite repository: ' . $repository->getName() . ' terminated');
            }
        }
    }

    /**
     *
     * Restore archived repository
     *
     * @param String git_root_path
     * @param String $backup_directory
     *
     * @return bool
     *
     */
    public function restoreRepository(GitRepository $repository, $git_root_path, $backup_directory)
    {
        $repository_path = $git_root_path . $repository->getPath();
        $backup_path     = $this->getBackupPath($repository, $backup_directory);
        if (! file_exists($backup_path)) {
            $this->logger->error('[Gitolite][Restore] Unable to find repository archive: ' . $backup_path);
            return false;
        }

        $backup_path = realpath($backup_path);

        if (! $this->extractRepository($backup_path)) {
            $this->logger->error('[Gitolite][Restore] Unable to restore repository: ' . $repository->getName());
            return false;
        }
        $this->deleteBackup($repository, $backup_directory);

        if (! $this->getDao()->activate($repository->getId())) {
            $this->logger->error('[Gitolite][Restore] Unable to activate repository after restore: ' . $repository->getName());
        }
        if (! $repository->getBackend()->updateRepoConf($repository)) {
            $this->logger->warning('[Gitolite][Restore] Unable to update repository configuration after restore : ' . $repository->getName());
        }

        $this->logger->info('[Gitolite] Restore of repository "' . $repository->getName() . '" completed');
        return true;
    }

    /**
     *
     * Extract repository
     *
     * @param String $backup_path
     *
     */
    private function extractRepository($backup_path)
    {
        $this->logger->debug('[Gitolite][Restore] sudo gitolite restore');
        $base = realpath(ForgeConfig::get('codendi_bin_prefix'));

        $system_command = new System_Command();
        $command        = "sudo -u gitolite $base/restore-tar-repository.php  " . escapeshellarg($backup_path) . ' /';

        try {
            $system_command->exec($command);
        } catch (System_Command_CommandException $exception) {
            $this->logger->error('[Gitolite][Restore] Unable to extract repository from backup: [' . $backup_path . '] error message : ' . $exception->getMessage());
            return false;
        }

        $this->logger->info('[Gitolite][Restore] Repository extracted from backup: ' . $backup_path);
        return true;
    }

    private function getBackupPath(GitRepository $repository, $backup_directory)
    {
        return $backup_directory . '/' . $repository->getBackupPath() . '.tar.gz';
    }

    /**
     * Wrapper for GitDao
     *
     * @return GitDao
     */
    protected function getDao()
    {
        return $this->git_dao;
    }
}
