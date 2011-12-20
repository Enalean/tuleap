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
require_once 'Git_PostReceiveMailManager.class.php';
require_once 'exceptions/Git_Command_Exception.class.php';
require_once 'PathJoinUtil.php';


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
class Git_GitoliteDriver {
    protected $oldCwd;
    protected $confFilePath;
    protected $adminPath;

    public function repoFullName(GitRepository $repo, $unix_name) {
        return unixPathJoin(array($unix_name, $repo->getFullName()));
    }

    /**
     * Constructor
     *
     * @param string $adminPath The path to admin folder of gitolite. 
     *                          Default is $sys_data_dir . "/gitolite/admin"
     */
    public function __construct($adminPath = null) {
        if (!$adminPath) {
            $adminPath = $GLOBALS['sys_data_dir'] . '/gitolite/admin';
        }
        $this->setAdminPath($adminPath);
    }
    
    /**
     * Getter for $adminPath
     *
     * @return string
     */
    public function getAdminPath() { 
        return $this->adminPath; 
    }
    
    /**
     * Get repositories path
     *
     * @return string
     */
    public function getRepositoriesPath() {
        return realpath($this->adminPath .'/../repositories'); 
    }

    public function setAdminPath($adminPath) {
        $this->oldCwd    = getcwd();
        $this->adminPath = $adminPath;
        chdir($this->adminPath);

        $this->confFilePath = 'conf/gitolite.conf';
    }
    
    public function isInitialized($repoPath) {
        try {
            $headsPath = $repoPath.'/refs/heads';
            if (is_dir($headsPath)) {
                $dir = new DirectoryIterator($headsPath);
                foreach ($dir as $fileinfo) {
                    if (!$fileinfo->isDot()) {
                        return true;
                    }
                }
            }
        } catch(Exception $e) {
            // If directory doesn't even exists, return false
        }
        return false;
    }

    public function push() {
        $res = $this->gitPush();
        chdir($this->oldCwd);
        return $res;
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
            $dir = new DirectoryIterator('conf/projects');
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
     * Dump ssh keys into gitolite conf
     */
    public function dumpSSHKeys() {
        if (is_dir($this->getAdminPath())) {
            $userdao = new UserDao(CodendiDataAccess::instance());
            foreach ($userdao->searchSSHKeys() as $row) {
                $user = new User($row);
                $this->initUserKeys($user);
            }
            return $this->push();
        }
        return false;
    }

    /**
     * @param User $user
     */
    public function initUserKeys($user) {
        // First remove existing keys
        $this->removeUserExistingKeys($user);

        // Create path if need
        clearstatcache();
        $keydir = 'keydir';
        if (!is_dir($keydir)) {
            if (!mkdir($keydir)) {
                throw new Exception('Unable to create "keydir" directory in '.getcwd());
            }
        }

        // Dump keys
        $i    = 0;
        foreach ($user->getAuthorizedKeys(true) as $key) {
            $filePath = $keydir.'/'.$user->getUserName().'@'.$i.'.pub';
            if (file_put_contents($filePath, $key) == strlen($key)) {
                $this->gitAdd($filePath);
            }
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
        $keydir = 'keydir';
        if (is_dir($keydir)) {
            $dir = new DirectoryIterator($keydir);
            foreach ($dir as $file) {
                $userbase = $user->getUserName().'@';
                if (preg_match('/^'.$userbase.'[0-9]+.pub$/', $file)) {
                    //unlink($file->getPathname());
                    $this->gitRm($file->getPathname());
                }
            }
        }
    }

    protected function gitMv($from, $to) {
        $cmd = 'git mv '.escapeshellarg($from) .' '. escapeshellarg($to);
        return $this->gitCmd($cmd);
    }

    protected function gitAdd($file) {
        $cmd = 'git add '.escapeshellarg($file);
        return $this->gitCmd($cmd);
    }

    protected function gitRm($file) {
        $cmd = 'git rm '.escapeshellarg($file);
        return $this->gitCmd($cmd);
    }

    /**
     * Commit stuff to repository
     * 
     * Always force commit, even when there no changes it's mandatory with
     * dump ssh keys event, otherwise the commit is empty and it raises errors.
     * TODO: find a better way to manage that!
     *
     * @param String $message
     */
    protected function gitCommit($message) {
        $cmd = 'git commit --allow-empty -m '.escapeshellarg($message);
        return $this->gitCmd($cmd);
    }
    
    protected function gitPush() {
        $cmd = 'git push origin master';
        return $this->gitCmd($cmd);
    }
    
    protected function gitCmd($cmd) {
        $cmd = $cmd.' 2>&1';
        exec($cmd, $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            throw new Git_Command_Exception($cmd, $output, $retVal);
        }
    }
    
    /**
     * Fetch the gitolite readable conf for permissions on a repository
     *
     * @return string
     */
    public function fetchPermissions($project, $readers, $writers, $rewinders) {
        $s = '';
        
        array_walk($readers,   array($this, 'ugroupId2GitoliteFormat'), $project);
        array_walk($writers,   array($this, 'ugroupId2GitoliteFormat'), $project);
        array_walk($rewinders, array($this, 'ugroupId2GitoliteFormat'), $project);
        
        $readers   = array_filter($readers);
        $writers   = array_filter($writers);
        $rewinders = array_filter($rewinders);
        
        // Readers
        if (count($readers)) {
            $s .= ' R   = '. implode(' ', $readers);
            $s .= PHP_EOL;
        }
        
        // Writers
        if (count($writers)) {
            $s .= ' RW  = '. implode(' ', $writers);
            $s .= PHP_EOL;
        }
        
        // Rewinders
        if (count($rewinders)) {
            $s .= ' RW+ = '. implode(' ', $rewinders);
            $s .= PHP_EOL;
        }
        
        return $s;
    }

    /**
     * Returns post-receive-email hook config in gitolite format
     *
     * @param Project $project
     * @param GitRepository $repository
     */
    public function fetchMailHookConfig($project, $repository) {
        $conf  = '';
        $conf .= ' config hooks.showrev = "'. $repository->getPostReceiveShowRev(). '"';
        $conf .= PHP_EOL;
        if ($repository->getNotifiedMails() && count($repository->getNotifiedMails()) > 0) {
            $conf .= ' config hooks.mailinglist = "'. implode(', ', $repository->getNotifiedMails()). '"';
        } else {
            $conf .= ' config hooks.mailinglist = ""';
        }
        $conf .= PHP_EOL;
        if ($repository->getMailPrefix() != GitRepository::DEFAULT_MAIL_PREFIX) {
            $conf .= ' config hooks.emailprefix = "'. $repository->getMailPrefix() .'"';
            $conf .= PHP_EOL;
        }
        return $conf;
    }

    /**
     * Convert given ugroup id to a format managed by Git_GitoliteMembershipPgmTest
     *
     * @param String $ug UGroupId
     */
    protected function ugroupId2GitoliteFormat(&$ug, $key, $project) {
        if ($ug > 100) {
            $ug = '@ug_'. $ug;
        } else {
            switch ($ug) {
                case $GLOBALS['UGROUP_REGISTERED']:
                    $ug = '@site_active';
                    break;
                case $GLOBALS['UGROUP_PROJECT_MEMBERS'];
                    $ug = '@'.$project->getUnixName().'_project_members';
                    break;
                case $GLOBALS['UGROUP_PROJECT_ADMIN']:
                    $ug = '@'.$project->getUnixName().'_project_admin';
                    break;
                default:
                    $ug = null;
                    break;
            }
        }
        return false;
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
            $perms    = '';
            $pm       = $this->getPermissionsManager();
            $notifMgr = $this->getPostReceiveMailManager();
            foreach ($dar as $row) {
                $repository = new GitRepository();
                $repository->setId($row[GitDao::REPOSITORY_ID]);
                $repository->setName($row[GitDao::REPOSITORY_NAME]);
                $repository->setProject($project);
                $repository->setNotifiedMails($notifMgr->getNotificationMailsByRepositoryId($row[GitDao::REPOSITORY_ID]));
                $repository->setMailPrefix($row[GitDao::REPOSITORY_MAIL_PREFIX]);
                $repository->setNamespace($row[GitDao::REPOSITORY_NAMESPACE]);

                // Name of the repo
                $perms .= 'repo '. $this->repoFullName($repository, $project->getUnixName()) . PHP_EOL;
                
                // Hook config
                $perms .= $this->fetchMailHookConfig($project, $repository);

                // Perms
                $readers   = $this->getAuthorizedUgroupsId($row[GitDao::REPOSITORY_ID], Git::PERM_READ);
                $writers   = $this->getAuthorizedUgroupsId($row[GitDao::REPOSITORY_ID], Git::PERM_WRITE);
                $rewinders = $this->getAuthorizedUgroupsId($row[GitDao::REPOSITORY_ID], Git::PERM_WPLUS);
                $perms    .= $this->fetchPermissions($project, $readers, $writers, $rewinders);

                $perms .= PHP_EOL;
            }

            // Save into file
            $confFile = $this->getProjectPermissionConfFile($project);
            $written  = file_put_contents($confFile, $perms);
            if ($written && strlen($perms) == $written) {
                if ($this->gitAdd($confFile)) {
                    if ($this->updateMainConfIncludes($project)) {
                        return $this->gitCommit('Update: '.$project->getUnixName());
                    }
                }
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
        $prjConfDir = 'conf/projects';
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

    /**
     * Wrapper for Git_PostReceiveMailManager
     *
     * @return Git_PostReceiveMailManager
     */
    protected function getPostReceiveMailManager() {
        return new Git_PostReceiveMailManager();
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
     * @return true if success, false otherwise
     */
    public function renameProject($oldName, $newName) {
        $ok = true;
        if (is_file('conf/projects/'. $oldName .'.conf')) {
            $ok = $this->gitMv(
                'conf/projects/'. $oldName .'.conf',
                'conf/projects/'. $newName .'.conf'
            );
            if ($ok) {
                //conf/projects/newone.conf
                $orig = file_get_contents('conf/projects/'. $newName .'.conf');
                $dest = preg_replace('`(^|\n)repo '. preg_quote($oldName) .'/`', '$1repo '. $newName .'/', $orig);
                $dest = str_replace('@'. $oldName .'_project_', '@'. $newName .'_project_', $dest);
                file_put_contents('conf/projects/'. $newName .'.conf', $dest);
                $this->gitAdd('conf/projects/'. $newName .'.conf');
                
                //conf/gitolite.conf
                $orig = file_get_contents($this->confFilePath);
                $dest = str_replace('include "projects/'. $oldName .'.conf"', 'include "projects/'. $newName .'.conf"', $orig);
                file_put_contents($this->confFilePath, $dest);
                $this->gitAdd($this->confFilePath);
                
                //commit
                $ok = $this->gitCommit('Rename project '. $oldName .' to '. $newName) && $this->gitPush();
            }
        }
        return $ok;
    }
    
    public function delete($path) {
        if ( empty($path) || !is_writable($path) ) {
           throw new GitDriverErrorException('Empty path or permission denied '.$path);
        }
        $rcode = 0;
        $output = system('rm -fr '.escapeshellarg($path), $rcode);
        if ( $rcode != 0 ) {
           throw new GitDriverErrorException('Unable to delete path '.$path);
        }
        return true;
    }
    
    public function fork($repo, $old_ns, $new_ns){

        $source = unixPathJoin(array($this->getRepositoriesPath(),$old_ns, $repo)) .'.git';
        $target = unixPathJoin(array($this->getRepositoriesPath(),$new_ns, $repo)) .'.git';
        if (!is_dir($target)) {
            $cmd = 'umask 0007; sg - gitolite -c "git clone --bare '. $source .' '. $target.'"';
            $clone_result = $this->gitCmd($cmd);
            
            $copyHooks  = 'cd '.$this->getRepositoriesPath().'; ';
            $copyHooks .= 'sg - gitolite -c "cp -f '.$source.'/hooks/* '.$target.'/hooks/"';
            $this->gitCmd($copyHooks);
            
            return $clone_result;
        }
        return false;
    }
    
}

?>