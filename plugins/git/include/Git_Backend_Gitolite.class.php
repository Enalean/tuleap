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
 */

use Tuleap\Event\Events\ArchiveDeletedItemEvent;
use Tuleap\Event\Events\ArchiveDeletedItemFileProvider;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;

class Git_Backend_Gitolite extends GitRepositoryCreatorImpl implements Git_Backend_Interface
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Git_GitoliteDriver
     */
    protected $driver;

    /**
     * @var GitDao
     */
    protected $dao;

    /**
     * @var PermissionsManager
     */
    protected $permissionsManager;

    /**
     * @var gitPlugin
     */
    protected $gitPlugin;

    public const PREFIX = "gitolite_";
    /**
     * @var GitoliteAccessURLGenerator
     */
    private $gitolite_access_URL_generator;

    public function __construct(
        Git_GitoliteDriver $driver,
        GitoliteAccessURLGenerator $gitolite_access_URL_generator,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->driver                        = $driver;
        $this->gitolite_access_URL_generator = $gitolite_access_URL_generator;
        $this->logger                        = $logger;
    }

    /**
     * Create new reference
     *
     * @see plugins/git/include/Git_Backend_Interface::createReference()
     * @param GitRepository $repository
     */
    public function createReference($repository)
    {
    }

    /**
     * @return bool
     */
    public function updateRepoConf($repository)
    {
        return $this->driver->dumpProjectRepoConf($repository->getProject());
    }

    /**
     * Verify if the repository as already some content within
     *
     * @see    plugins/git/include/Git_Backend_Interface::isInitialized()
     * @return bool
     */
    public function isInitialized(GitRepository $repository)
    {
        $init = $this->driver->isInitialized($this->getGitRootPath() . '/' . $repository->getPath());
        if ($init) {
            $this->getDao()->initialize($repository->getId());
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @return bool
     */
    public function isCreated(GitRepository $repository)
    {
        return $this->driver->isRepositoryCreated($this->getGitRootPath() . '/' . $repository->getPath());
    }

    /**
     * Return URL to access the respository for remote git commands
     *
     * @return array
     */
    public function getAccessURL(GitRepository $repository)
    {
        $transports = array();
        $ssh_transport = $this->gitolite_access_URL_generator->getSSHURL($repository);
        if ($ssh_transport) {
            $transports['ssh'] = $ssh_transport;
        }
        $http_transport = $this->gitolite_access_URL_generator->getHTTPURL($repository);
        if ($http_transport) {
            $transports['http'] = $http_transport;
        }
        return $transports;
    }

    /**
     * Return the base root of all git repositories
     *
     * @return String
     */
    public function getGitRootPath()
    {
        return $GLOBALS['sys_data_dir'] . '/gitolite/repositories/';
    }

    /**
     * Wrapper for GitDao
     *
     * @return GitDao
     */
    protected function getDao()
    {
        if (!$this->dao) {
            $this->dao = new GitDao();
        }
        return $this->dao;
    }

    public function setDao($dao)
    {
        $this->dao = $dao;
    }

    /**
     * Verify if given name is not already reserved on filesystem
     *
     * @return bool
     */
    public function isNameAvailable($newName)
    {
        return ! file_exists($this->getGitRootPath() . '/' . $newName);
    }

    /**
     * Save the permissions of the repository
     *
     * @param array         $perms
     *
     * @return bool true if success, false otherwise
     */
    public function savePermissions(GitRepository $repository, $perms)
    {
        $project_creator_status = new Git_Driver_Gerrit_ProjectCreatorStatus(
            new Git_Driver_Gerrit_ProjectCreatorStatusDao()
        );
        $ok = true;
        $ok &= $this->savePermission($repository, Git::PERM_READ, $perms);
        if ($project_creator_status->canModifyPermissionsTuleapSide($repository)) {
            if ($ok) {
                $ok &= $this->savePermission($repository, Git::PERM_WRITE, $perms);
            }
            if ($ok) {
                $ok &= $this->savePermission($repository, Git::PERM_WPLUS, $perms);
            }
        }
        return $ok;
    }

    private function savePermission(GitRepository $repository, $type, array $perms)
    {
        try {
            if (isset($perms[$type]) && is_array($perms[$type])) {
                $override_collection = PermissionsManager::instance()->savePermissionsWithoutHistory(
                    $repository->getProject(),
                    $repository->getId(),
                    $type,
                    $perms[$type]
                );
                $override_collection->emitFeedback($type);
            }
            return true;
        } catch (PermissionDaoException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('project_admin_permissions', 'save_db_error'));
            $this->logger->error($exception->getMessage());
        }
        return false;
    }

    /**
     * Delete the permissions of the repository
     *
     * @param GitRepository $repository
     *
     * @return bool true if success, false otherwise
     */
    public function deletePermissions($repository)
    {
        $group_id = $repository->getProjectId();
        $object_id = $repository->getId();
        return permission_clear_all($group_id, Git::PERM_READ, $object_id)
            && permission_clear_all($group_id, Git::PERM_WRITE, $object_id)
            && permission_clear_all($group_id, Git::PERM_WPLUS, $object_id);
    }


    /**
     * Test is user can read the content of this repository and metadata
     *
     * @param PFUser          $user       The user to test
     * @param GitRepository $repository The repository to test
     *
     * @return bool
     */
    public function userCanRead($user, $repository)
    {
        if ($user->isMember($repository->getProjectId(), 'A')) {
            return true;
        }

        if ($user->hasPermission(Git::PERM_READ, $repository->getId(), $repository->getProjectId())) {
            return true;
        }

        if (! $repository->isMigratedToGerrit()) {
            return $user->hasPermission(Git::PERM_WRITE, $repository->getId(), $repository->getProjectId())
               || $user->hasPermission(Git::PERM_WPLUS, $repository->getId(), $repository->getProjectId());
        }

        return false;
    }

    /**
     * Save the repository
     *
     * @param GitRepository $repository
     *
     * @return bool
     */
    public function save($repository)
    {
        return $this->getDao()->save($repository);
    }

    /**
     * Update list of people notified by post-receive-email hook
     *
     * @param GitRepository $repository
     */
    public function changeRepositoryMailingList($repository)
    {
        return true;
    }

    /**
     * Change post-receive-email hook mail prefix
     *
     * @param GitRepository $repository
     *
     * @return bool
     */
    public function changeRepositoryMailPrefix($repository)
    {
        return $this->changeRepositoryMailingList($repository);
    }

    /**
     * Rename a project
     *
     * @param Project $project The project to rename
     * @param string  $newName The new name of the project
     *
     * @return bool true if success, false otherwise
     */
    public function renameProject(Project $project, $newName)
    {
        if (is_dir($this->driver->getRepositoriesPath() . '/' . $project->getUnixName())) {
            $backend = $this->getBackend();
            $ok = rename(
                $this->driver->getRepositoriesPath() . '/' . $project->getUnixName(),
                $this->driver->getRepositoriesPath() . '/' . $newName
            );
            if ($ok) {
                try {
                    $this->glRenameProject($project->getUnixName(), $newName);
                } catch (Exception $e) {
                    $backend->log($e->getMessage(), Backend::LOG_ERROR);
                    return false;
                }
            } else {
                $backend->log("Rename: Unable to rename gitolite top directory", Backend::LOG_ERROR);
            }
        }
        return true;
    }

    /**
     * Trigger rename of gitolite repositories in configuration files
     *
     * All the rename process is owned by 'root' user but gitolite modification has to be
     * modified as 'codendiadm' because the config is localy edited and then pushed in 'gitolite'
     * user repo. In order to make this work, the ~/.ssh/config is modified (otherwise git would
     * not use a custom ssh key to access the repo).
     * To make a long story short: we need to execute the following code as codendiadm (so 'su' is used)
     * and as the new name of the project is already updated in the db we need to pass the old name (instead
     * of the project Id).
     *
     * @param String $oldName The old name of the project
     * @param String $newName The new name of the project
     * @throws Exception
     *
     * @return bool
     */
    protected function glRenameProject($oldName, $newName)
    {
        $retVal = 0;
        $output = array();
        $mvCmd  = $GLOBALS['codendi_dir'] . '/src/utils/php-launcher.sh ' . $GLOBALS['codendi_dir'] . '/plugins/git/bin/gl-rename-project.php ' . escapeshellarg($oldName) . ' ' . escapeshellarg($newName);
        $cmd    = 'su -l codendiadm -c "' . $mvCmd . ' 2>&1"';
        exec($cmd, $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            throw new Exception('Rename: Unable to propagate rename to gitolite conf (error code: ' . $retVal . '): ' . implode('%%%', $output));
            return false;
        }
    }

    public function canBeDeleted(GitRepository $repository)
    {
        return true;
    }

    public function markAsDeleted(GitRepository $repository)
    {
        $this->getDao()->delete($repository);
    }

    public function delete(GitRepository $repository)
    {
        $this->updateRepoConf($repository);
        $this->logger->debug('Backuping ' . $repository->getPath());
        $backup_dir = $this->getGitPlugin()->getConfigurationParameter('git_backup_dir');
        if ($backup_dir && is_dir($backup_dir)) {
            $this->getDriver()->backup($repository, $backup_dir);
        }
        $this->getDriver()->delete($repository->getFullPath());
    }

    public function deleteArchivedRepository(GitRepository $repository)
    {
        $this->logger->debug('Delete backup ' . $repository->getBackupPath());
        $this->getDriver()->deleteBackup(
            $repository,
            $this->getGitPlugin()->getConfigurationParameter('git_backup_dir')
        );
    }

    /**
     * Invoque 'archive deleted item' hook in order to make a backup of the git repository archive
     *
     *
     * @return bool
     */
    public function archiveBeforePurge(GitRepository $repository)
    {
        $backup = $this->getGitPlugin()->getConfigurationParameter('git_backup_dir');

        if (dirname($backup)) {
            $event = new ArchiveDeletedItemEvent(new ArchiveDeletedItemFileProvider($backup . '/' . $repository->getBackupPath() . '.tar.gz', self::PREFIX));

            $this->getEventManager()->processEvent($event);

            if ($event->isSuccessful()) {
                $this->logger->info('The repository' . $repository->getName() . ' has been moved to the archiving area before purge ');
                return true;
            }

            $this->logger->warning('Can not move the repository' . $repository->getName() . ' to the archiving area before purge');
            return false;
        }

        $this->logger->error('An error occured: The backup ' . $backup . ' is not a directory or does not exist');
        return false;
    }

    private function getEventManager()
    {
        return EventManager::instance();
    }

    /**
     * @throws GitRepositoryAlreadyExistsException
     */
    public function fork(GitRepository $old, GitRepository $new, array $forkPermissions)
    {
        $new_project = $new->getProject();
        if ($this->getDao()->isRepositoryExisting($new_project->getId(), $new->getPath())) {
            throw new GitRepositoryAlreadyExistsException('Respository already exists');
        } else {
            $id = $this->getDao()->save($new);
            $new->setId($id);
            if (empty($forkPermissions)) {
                $this->clonePermissions($old, $new);
            } else {
                $this->savePermissions($new, $forkPermissions);
            }
            return $id;
        }
    }

    public function forkOnFilesystem(GitRepository $old, GitRepository $new)
    {
        $name = $old->getName();
        //TODO use $old->getRootPath() (good luck for Unit Tests!)
        $old_namespace = $old->getProject()->getUnixName() . '/' . $old->getNamespace();
        $new_namespace = $new->getProject()->getUnixName() . '/' . $new->getNamespace();

        $this->getDriver()->fork($name, $old_namespace, $new_namespace);
        $this->updateRepoConf($new);
    }


    public function clonePermissions(GitRepository $old, GitRepository $new)
    {
        $pm = $this->getPermissionsManager();

        if ($this->inSameProject($old, $new)) {
            $pm->duplicateWithStatic($old->getId(), $new->getId(), Git::allPermissionTypes());
        } else {
            $pm->duplicateWithoutStatic($old->getId(), $new->getId(), Git::allPermissionTypes());
        }
    }

    private function inSameProject(GitRepository $repository1, GitRepository $repository2)
    {
        return ($repository1->getProject()->getId() == $repository2->getProject()->getId());
    }

    public function setPermissionsManager(PermissionsManager $permissionsManager)
    {
        $this->permissionsManager = $permissionsManager;
    }

    public function getPermissionsManager()
    {
        if (!$this->permissionsManager) {
            $this->permissionsManager = PermissionsManager::instance();
        }
        return $this->permissionsManager;
    }

    /**
     * Load a repository from its id. Mainly used as a wrapper for tests
     *
     * @param $repositoryId Id of the repository
     *
     * @return GitRepository
     */
    public function loadRepositoryFromId($repositoryId)
    {
        $repository = new GitRepository();
        $repository->setId($repositoryId);
        $repository->load();
        return $repository;
    }

    /**
     * Set $driver
     *
     * @param Git_GitoliteDriver $driver The driver
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
    }

    /**
     * Wrapper for Backend object
     *
     * @return Backend
     */
    protected function getBackend()
    {
        return Backend::instance();
    }

    public function getDriver()
    {
        return $this->driver;
    }

    protected function getGitPlugin()
    {
        if (!$this->gitPlugin) {
            $plugin_manager  = PluginManager::instance();
            $this->gitPlugin = $plugin_manager->getPluginByName('git');
        }
        return $this->gitPlugin;
    }

    /**
     * Setter for tests
     *
     */
    public function setGitPlugin(GitPlugin $gitPlugin)
    {
        $this->gitPlugin = $gitPlugin;
    }

    public function disconnectFromGerrit(GitRepository $repository)
    {
        $this->getDao()->disconnectFromGerrit($repository->getId());
    }

    public function setGerritProjectAsDeleted(GitRepository $repository)
    {
        $this->getDao()->setGerritProjectAsDeleted($repository->getId());
    }

    /**
     * @param array $repositor_ids
     * @return array
     */
    public function searchOtherRepositoriesInSameProjectFromRepositoryList(GitRepository $repository, $repositor_ids)
    {
        $project_repositories = array();

        $result = $this->getDao()->searchRepositoriesInSameProjectFromRepositoryList($repositor_ids, $repository->getProjectId());
        if (! $result) {
            return $project_repositories;
        }

        foreach ($result as $repo) {
            if ($repo['repository_id'] == $repository->getId()) {
                continue;
            }

            $project_repositories[] = $repo['repository_id'];
        }

        return $project_repositories;
    }

    /**
     *
     * Restore archived Gitolite repositories
     *
     *
     */
    public function restoreArchivedRepository(GitRepository $repository)
    {
        $this->logger->info('[Gitolite]Restoring repository : ' . $repository->getName());
        $backup_directory = realpath($this->getGitPlugin()->getConfigurationParameter('git_backup_dir') . '/');
        return $this->getDriver()->restoreRepository(
            $repository,
            $this->getGitRootPath(),
            $backup_directory
        );
    }
}
