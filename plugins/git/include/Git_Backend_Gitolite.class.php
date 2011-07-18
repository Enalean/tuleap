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
     * Test is user can read the content of this repository and metadata
     *
     * @param User          $user       The user to test
     * @param GitRepository $repository The repository to test
     *
     * @return Boolean
     */
    public function userCanRead($user, $repository) {
        return $user->hasPermission(Git::PERM_READ, $repository->getId(), $repository->getProjectId())
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
     * Get the regexp pattern to use for name repository validation
     *
     * @return string
     */
    public function getAllowedCharsInNamePattern() {
        //alphanums, underscores, slashes and dash
        return 'a-zA-Z0-9/_.-';
    }
}

?>
