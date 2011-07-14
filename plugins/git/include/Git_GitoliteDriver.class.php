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

require_once 'common/project/Project.class.php';
require_once 'common/user/User.class.php';
require_once 'common/permission/PermissionsManager.class.php';
require_once 'GitDao.class.php';

class Git_GitoliteDriver {
    protected $oldCwd;
    protected $confFilePath;
    protected $adminPath;

    /**
     * The project on which driver applies
     * @var Project
     */
    protected $project;

    public function __construct($adminPath) {
        $this->setAdminPath($adminPath);
    }

    public function setAdminPath($adminPath) {
        $this->adminPath = $adminPath;
        $this->confFilePath = $adminPath.'/conf/gitolite.conf';
        $this->oldCwd = getcwd();
        chdir($this->adminPath);
    }
    
    public function masterExists($repoPath) {
        if (file_exists($repoPath.'/refs/heads/master')) {
            return true;
        }
        return false;
    }

    public function push() {
        $this->gitPush();
        chdir($this->oldCwd);
    }

    public function init($project, $repoPath) {
        $prjConfDir = $this->adminPath.'/conf/projects';
        if (!is_dir($prjConfDir)) {
            mkdir($prjConfDir);
        }
        $conf = PHP_EOL;
        $conf .= 'repo '.$project->getUnixName().'/'.$repoPath.PHP_EOL;
        $conf .= "\tRW = @".$project->getUnixName().'_project_members'.PHP_EOL;

        $confFile = $prjConfDir.'/'.$project->getUnixName().'.conf';
        if (file_put_contents($confFile, $conf, FILE_APPEND)) {
            if ($this->gitAdd($confFile)) {
                if ($this->updateMainConfIncludes($project)) {
                    return $this->gitCommit('New repo: '.$project->getUnixName().'/'.$repoPath);
                }
            }
        }
        return false;
    }

    public function updateMainConfIncludes($project) {
        if (is_file($this->confFilePath)) {
            $conf = file_get_contents($this->confFilePath);
        } else {
            $conf = '';
        }
        if (strpos($conf, 'include "projects/'.$project->getUnixName().'.conf"') === false) {
            $backend = Backend::instance();
            if ($conf) {
                $backend->removeBlock($this->confFilePath);
            }
            $newConf = '';
            $dir = new DirectoryIterator($this->adminPath.'/conf/projects');
            foreach ($dir as $file) {
                if (!$file->isDot()) {
                    $newConf .= 'include "projects/'.basename($file->getFilename()).'"'.PHP_EOL;
                }
            }
            if ($backend->addBlock($this->confFilePath, $newConf)) {
                return $this->gitAdd($this->confFilePath);
            }
            return false;
        }
        return true;
    }

    /**
     * @param User $user
     */
    public function initUserKeys($user) {
        // First remove existing keys
        $this->removeUserExistingKeys($user);

        // Create path if need
        $keydir = $this->adminPath.'/keydir';
        if (!is_dir($keydir)) {
            mkdir($keydir);
        }

        // Dump keys
        $i    = 0;
        foreach ($user->getAuthorizedKeys(true) as $key) {
            $filePath = $keydir.'/'.$user->getUserName().'@'.$i.'.pub';
            file_put_contents($filePath, $key);
            $this->gitAdd($filePath);
            $i++;
        }

        $this->gitCommit('Update '.$user->getUserName().' (Id: '.$user->getId().') SSH keys');
    }

    /**
     * Remove all pub SSH keys previously associated to a user
     *
     * @param User $user
     */
    protected function removeUserExistingKeys($user) {
        $keydir = $this->adminPath.'/keydir';
        if (is_dir($keydir)) {
            $dir = new DirectoryIterator($keydir);
            foreach ($dir as $file) {
                $userbase = $user->getUserName().'@';
                if (preg_match('/^'.$userbase.'[0-9]+.pub$/', $file)) {
                    unlink($file->getPathname());
                    $this->gitRm($file->getPathname());
                }
            }
        }
    }

    protected function gitAdd($file) {
        exec('git add '.escapeshellarg($file), $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            return false;
        }
    }

    protected function gitRm($file) {
        exec('git rm '.escapeshellarg($file), $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            return false;
        }
    }

    protected function gitCommit($message) {
        exec('git commit -m '.escapeshellarg($message).' 2>&1 >/dev/null', $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            return false;
        }
    }
    
    protected function gitPush() {
        exec('git push origin master', $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Fetch the gitolite readable conf for permissions on a repository
     *
     * @return string
     */
    public function fetchPermissions($project, $repo, $readers, $writers, $rewinders) {
        $this->project = $project;
        $s = '';
        
        array_walk($readers, array($this, 'ugroupId2GitoliteFormat'));
        array_walk($writers, array($this, 'ugroupId2GitoliteFormat'));
        array_walk($rewinders, array($this, 'ugroupId2GitoliteFormat'));
        
        //Name of the repo
        $s .= 'repo '. $this->project->getUnixName(). '/' . $repo . PHP_EOL;
        
        // Readers
        $s .= ' R   = '. implode(' ', $readers);
        $s .= PHP_EOL;
        
        // Writers
        $s .= ' RW  = '. implode(' ', $writers);
        $s .= PHP_EOL;
        
        // Rewinders
        $s .= ' RW+ = '. implode(' ', $rewinders);
        $s .= PHP_EOL;
        
        $s .= PHP_EOL;
        return $s;
    }

    /**
     * Convert given ugroup id to a format managed by Git_GitoliteMembershipPgmTest
     *
     * @param String $ug UGroupId
     */
    protected function ugroupId2GitoliteFormat(&$ug) {
        if ($ug > 100) {
            $ug = '@ug_'. $ug;
        } else {
            switch ($ug) {
                case $GLOBALS['UGROUP_REGISTERED']:
                    $ug = '@site_active';
                    break;
                case $GLOBALS['UGROUP_PROJECT_MEMBERS'];
                    $ug = '@'.$this->project->getUnixName().'_project_members';
                    break;
                case $GLOBALS['UGROUP_PROJECT_ADMIN']:
                    $ug = '@'.$this->project->getUnixName().'_project_admin';
                    break;
            }
            
        }
    }

    /**
     * Save on filesystem all permission configuration for a project
     *
     * @param Project $project
     */
    public function dumpProjectRepoConf($project) {
        $dar = $this->getDao()->getAllGitoliteRespositories($project->getId());
        if ($dar && !$dar->isError()) {
            // Get perms
            $perms = '';
            $pm    = $this->getPermissionsManager();
            foreach ($dar as $row) {
                $readers   = $this->getAuthorizedUgroupsId($row['repository_id'], Git::PERM_READ);
                $writers   = $this->getAuthorizedUgroupsId($row['repository_id'], Git::PERM_WRITE);
                $rewinders = $this->getAuthorizedUgroupsId($row['repository_id'], Git::PERM_WPLUS);
                $perms    .= $this->fetchPermissions($project, $row[GitDao::REPOSITORY_NAME], $readers, $writers, $rewinders);
            }

            // Save into file
            $confFile = $this->getProjectPermissionConfFile($project);
            $written = file_put_contents($confFile, $perms);
            if ($written && strlen($perms) == $written) {
                return true;
            }
        }
    }

    protected function getAuthorizedUgroupsId($id, $perm) {
        $ug  = array();
        $pm  = $this->getPermissionsManager();
        $dar = $pm->getAuthorizedUgroups($id, $perm);
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $ug[] = $row['ugroup_id'];
            }
        }
        return $ug;
    }
    
    protected function getProjectPermissionConfFile($project) {
        $prjConfDir = $this->adminPath.'/conf/projects';
        if (!is_dir($prjConfDir)) {
            mkdir($prjConfDir);
        }
        return $prjConfDir.'/'.$project->getUnixName().'.conf';
    }
    
    /**
     * Wrapper for PermissionsManager
     *
     * @return PermissionsManager
     */
    protected function getPermissionsManager() {
        return PermissionsManager::instance();
    }

    /**
     * Wrapper for GitDao
     *
     * @return GitDao
     */
    protected function getDao() {
        return new GitDao();
    }
}

?>