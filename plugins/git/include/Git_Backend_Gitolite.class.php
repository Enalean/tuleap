<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once 'Git_Backend_Interface.php';
require_once 'Git.class.php';

class Git_Backend_Gitolite implements Git_Backend_Interface {
    /**
     * @var Git_GitoliteDriver
     */
    protected $driver;

    /**
     * @var GitDao
     */
    protected $dao;

    /**
     * Constructor
     * 
     * @param Git_GitoliteDriver $driver
     */
    public function __construct($driver) {
        $this->driver = $driver;
    }

    /**
     * Create new reference
     *
     * @see plugins/git/include/Git_Backend_Interface::createReference()
     * @param GitRepository $repository
     */
    public function createReference($repository) {
        $id = $this->getDao()->save($repository);
        $this->driver->dumpProjectRepoConf($repository->getProject());
        $this->driver->push();
    }

    /**
     * Verify if the repository as already some content within
     *
     * @see    plugins/git/include/Git_Backend_Interface::isInitialized()
     * @param  GitRepository $repository
     * @return Boolean
     */
    public function isInitialized($repository) {
        $masterExists = $this->driver->masterExists($this->getGitRootPath().'/'.$repository->getPath());
        if ($masterExists) {
            $this->getDao()->initialize($repository->getId());
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return URL to access the respository for remote git commands
     *
     * @param  GitRepository $repository
     * @return String
     */
    public function getAccessUrl($repository) {
        $serverName  = $_SERVER['SERVER_NAME'];
        return  'gitolite@'.$serverName.':'.$repository->getProject()->getUnixName().'/'.$repository->getName().'.git';
    }

    /**
     * Return the base root of all git repositories
     *
     * @return String
     */
    public function getGitRootPath() {
        return $GLOBALS['sys_data_dir'] .'/gitolite/repositories/';
    }

    /**
     * Wrapper for GitDao
     * 
     * @return GitDao
     */
    protected function getDao() {
        if (!$this->dao) {
            $this->dao = new GitDao();
        }
        return $this->dao;
    }

    /**
     * Verify if given name is not already reserved on filesystem
     *
     * @return bool
     */
    public function isNameAvailable($newName) {
        return ! file_exists($this->getGitRootPath() .'/'. $newName);
    }
    
    /**
     * Save the permissions of the repository
     *
     * @param GitRepository $repository
     * @param array         $perms
     *
     * @return bool true if success, false otherwise
     */
    public function savePermissions($repository, $perms) {
        $msgs = array();
        $ok   = false;
        if (isset($perms['read']) && is_array($perms['read'])) {
            $success = permission_process_selection_form($repository->getProjectId(), 'PLUGIN_GIT_READ', $repository->getId(), $perms['read']);
        }
        $msgs[] = $success[1];
        if ($success[0]) {
            if (isset($perms['write']) && is_array($perms['write'])) {
                $success = permission_process_selection_form($repository->getProjectId(), 'PLUGIN_GIT_WRITE', $repository->getId(), $perms['write']);
            }
            $msgs[] = $success[1];
            if ($success[0]) {
                if (isset($perms['wplus']) && is_array($perms['wplus'])) {
                    $success = permission_process_selection_form($repository->getProjectId(), 'PLUGIN_GIT_WPLUS', $repository->getId(), $perms['wplus']);
                }
                $msgs[] = $success[1];
                $ok = $success[0];
            }
        }
        $this->driver->dumpProjectRepoConf($repository->getProject());
        $this->driver->push();
        foreach ($msgs as $msg) {
            $GLOBALS['Response']->addFeedback($ok ? 'info' : 'error', $msg);
        }
        return $ok;
    }
    
    /**
     * Delete the permissions of the repository
     *
     * @param GitRepository $repository
     *
     * @return bool true if success, false otherwise
     */
    public function deletePermissions($repository) {
        
        $group_id = $repository->getProjectId();
        $object_id = $repository->getId();
        return permission_clear_all($group_id, Git::PERM_READ, $object_id)
            && permission_clear_all($group_id, Git::PERM_WRITE, $object_id)
            && permission_clear_all($group_id, Git::PERM_WPLUS, $object_id);
    }
    

    /**
     * Test is user can read the content of this repository and metadata
     *
     * @param User          $user       The user to test
     * @param GitRepository $repository The repository to test
     *
     * @return Boolean
     */
    public function userCanRead($user, $repository) {
        return $user->isMember($repository->getProjectId(), 'A')
               || $user->hasPermission(Git::PERM_READ, $repository->getId(), $repository->getProjectId())
               || $user->hasPermission(Git::PERM_WRITE, $repository->getId(), $repository->getProjectId())
               || $user->hasPermission(Git::PERM_WPLUS, $repository->getId(), $repository->getProjectId());
    }

    /**
     * Save the repository
     *
     * @param GitRepository $repository
     *
     * @return bool
     */
    public function save($repository) {
        //TODO: change teh description in the driver (see gitshell driver)
        return $this->getDao()->save($repository);
    }

    /**
     * Update list of people notified by post-receive-email hook
     *
     * @param GitRepository $repository
     */
    public function changeRepositoryMailingList($repository) {
        $this->getDriver()->setAdminPath($this->getDriver()->getAdminPath());
        $this->getDriver()->dumpProjectRepoConf($repository->getProject());
        return $this->getDriver()->push();
    }

    /**
     * Change post-receive-email hook mail prefix
     *
     * @param GitRepository $repository
     *
     * @return Boolean
     */
    public function changeRepositoryMailPrefix($repository) {
        return $this->changeRepositoryMailingList($repository);
    }

    /**
     * Get the regexp pattern to use for name repository validation
     *
     * @return string
     */
    public function getAllowedCharsInNamePattern() {
        //alphanums, underscores, slashes and dash
        return 'a-zA-Z0-9/_.-';
    }
    
    /**
     * Rename a project
     *
     * @param Project $project The project to rename
     * @param string  $newName The new name of the project
     *
     * @return true if success, false otherwise
     */
    public function renameProject(Project $project, $newName) {
        if (is_dir($this->driver->getRepositoriesPath() .'/'. $project->getUnixName())) {
            $backend = $this->getBackend();
            $ok = rename(
                $this->driver->getRepositoriesPath() .'/'. $project->getUnixName(), 
                $this->driver->getRepositoriesPath() .'/'. $newName
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
     * @return Boolean
     */
    protected function glRenameProject($oldName, $newName) {
        $retVal = 0;
        $output = array();
        $mvCmd  = $GLOBALS['codendi_dir'].'/src/utils/php-launcher.sh '.$GLOBALS['codendi_dir'].'/plugins/git/bin/gl-rename-project.php '.escapeshellarg($oldName).' '.escapeshellarg($newName);
        $cmd    = 'su -l codendiadm -c "'.$mvCmd.' 2>&1"';
        exec($cmd, $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            throw new Exception('Rename: Unable to propagate rename to gitolite conf (error code: '.$retVal.'): '.implode('%%%', $output));
            return false;
        }
    }
    
    public function delete($repository) {
        $path = $repository->getPath();
        if ( empty($path) ) {
            throw new GitBackendException('Bad repository path: '.$path);
        }
        $path = $this->getGitRootPath().$path;
        
        if ( $this->getDao()->hasChild($repository) === true ) {
            throw new GitBackendException( $GLOBALS['Language']->getText('plugin_git', 'backend_delete_haschild_error') );
        }
        
        $referencePath = $this->getDriver()->getRepositoriesPath().'/'.$repository->getProject()->getUnixName();
        
        if ($this->isSubPath($referencePath, $path) && $this->isDotGit($path)) {
            if ($this->getDao()->delete($repository) && $this->deletePermissions($repository)) {
                $this->getDriver()->setAdminPath($this->getDriver()->getAdminPath());
                $this->getDriver()->dumpProjectRepoConf($repository->getProject());
                $this->getDriver()->push();
                $this->getDriver()->delete($path);
                return true;
            }
        } else {
            throw new GitBackendException( $GLOBALS['Language']->getText('plugin_git', 'backend_delete_path_error') );
        }
        
        return false;
    }
    
    /**
     * Set $driver
     *
     * @param Git_GitoliteDriver $driver The driver
     */
    public function setDriver($driver) {
        $this->driver = $driver;
    }
    
    /**
     * Wrapper for Backend object
     *
     * @return Backend
     */
    protected function getBackend() {
        return Backend::instance();
    }
    
    public function getDriver() {
        return $this->driver;
    }
    
    /**
     * Check if path is a subpath of referencepath
     *
     * @param String $referencePath
     * @param String $path
     *
     * @return Boolean
     */
    public function isSubPath($referencePath, $path) {
        if (strpos(realpath($path), realpath($referencePath)) === 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Check if path contains .git at the end
     *
     * @param String $path
     *
     * @return Boolean
     */
    public function isDotGit($path) {
        return (substr($path, -4) == '.git');
    }
}

?>
