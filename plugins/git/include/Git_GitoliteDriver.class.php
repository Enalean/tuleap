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
require_once 'common/project/UGroupManager.class.php';
require_once 'common/project/UGroupLiteralizer.class.php';
require_once 'GitDao.class.php';
require_once 'Git_PostReceiveMailManager.class.php';
require_once 'PathJoinUtil.php';
require_once 'Git_Exec.class.php';


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

    /**
     * @var Git_Exec
     */
    private $gitExec;

    protected $oldCwd;
    protected $confFilePath;
    protected $adminPath;
    public static $permissions_types = array(
        Git::PERM_READ  => ' R  ',
        Git::PERM_WRITE => ' RW ',
        Git::PERM_WPLUS => ' RW+'
    );

    /**
     * Constructor
     *
     * @param string $adminPath The path to admin folder of gitolite. 
     *                          Default is $sys_data_dir . "/gitolite/admin"
     */
    public function __construct($adminPath = null, Git_Exec $gitExec = null) {
        if (!$adminPath) {
            $adminPath = $GLOBALS['sys_data_dir'] . '/gitolite/admin';
        }
        $this->setAdminPath($adminPath);
        $this->gitExec = $gitExec ? $gitExec : new Git_Exec($adminPath);
    }

    public function repoFullName(GitRepository $repo, $unix_name) {
        return unixPathJoin(array($unix_name, $repo->getFullName()));
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
        $res = $this->gitExec->push();
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
                return $this->gitExec->add($this->confFilePath);
            }
            return false;
        }
        return true;
    }
    
    /**
     * Dump ssh keys into gitolite conf
     */
    public function dumpSSHKeys(User $user = null) {
        if (is_dir($this->getAdminPath())) {
            if ($user) {
                $this->initUserKeys($user);
                $commit_msg = 'Update '.$user->getUserName().' (Id: '.$user->getId().') SSH keys';
            } else {
                $userdao = new UserDao();
                foreach ($userdao->searchSSHKeys() as $row) {
                    $user = new User($row);
                    $this->initUserKeys($user);
                }
                $commit_msg = 'SystemEvent update all user keys';
            }
            $this->gitExec->add('keydir');
            $this->gitExec->commit($commit_msg);
            return $this->push();
        }
        return false;
    }

    /**
     * @param User $user
     */
    private function initUserKeys($user) {
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
            file_put_contents($filePath, $key);
            $i++;
        }
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
                     $this->gitExec->rm($file->getPathname());
                }
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
        if (!$dar || $dar->isError()) {
            return;
        }
        $project_config   = '';
        $notification_manager = $this->getPostReceiveMailManager();
        foreach ($dar as $row) {
            $repository      = $this->buildRepositoryFromRow($row, $project, $notification_manager);
            $project_config .= $this->fetchReposConfig($project, $repository);  
        }
        
        $config_file = $this->getProjectPermissionConfFile($project);
        if ($this->writeGitConfig($config_file, $project_config)) {
            return $this->commitConfigFor($project);
        }
    }
    
    protected function buildRepositoryFromRow($row, $project, $notification_manager = null) {
        $repository_id = $row[GitDao::REPOSITORY_ID];
        $repository = new GitRepository();
        $repository->setId($repository_id);
        $repository->setName($row[GitDao::REPOSITORY_NAME]);
        $repository->setProject($project);
        if (! $notification_manager ) {
            $notification_manager = $this->getPostReceiveMailManager();
        }
        $notified_mails = $notification_manager->getNotificationMailsByRepositoryId($repository_id);
        $repository->setNotifiedMails($notified_mails);
        $repository->setDescription($row[GitDao::REPOSITORY_DESCRIPTION]);
        $repository->setMailPrefix($row[GitDao::REPOSITORY_MAIL_PREFIX]);
        $repository->setNamespace($row[GitDao::REPOSITORY_NAMESPACE]);
        return $repository;
    }
    
    protected function fetchReposConfig(Project $project, $repository) {
        $repo_full_name   = $this->repoFullName($repository, $project->getUnixName());
        $repo_config  = 'repo '. $repo_full_name . PHP_EOL;
        $repo_config .= $this->fetchMailHookConfig($project, $repository);
        $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_READ);
        $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_WRITE);
        $repo_config .= $this->fetchConfigPermissions($project, $repository, Git::PERM_WPLUS);
        
        // Do not dump repository description as it seems to produce wiered effect @ST
        // 
        // More informations about the feature:
        // @see https://github.com/sitaramc/gitolite/blob/v1.5.9.1/doc/2-admin.mkd#specifying-gitweb-and-daemon-access
        // 
        // $description = preg_replace( "% *\n *%", ' ', $repository->getDescription());
        // $repo_config .= "$repo_full_name = \"$description\"".PHP_EOL;
        
        return $repo_config. PHP_EOL;
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
     * Fetch the gitolite readable conf for permissions on a repository
     *
     * @return string
     */
    public function fetchConfigPermissions($project, $repository, $permission_type) {
        if (!isset(self::$permissions_types[$permission_type])) {
            return '';
        }
        
        $ugroup_literalizer = new UGroupLiteralizer();
        $repository_groups  = $ugroup_literalizer->getUGroupsThatHaveGivenPermissionOnObject($project, $repository->getId(), $permission_type);
        if (count($repository_groups) == 0) {
            return '';
        }
        return self::$permissions_types[$permission_type] . ' = ' . implode(' ', $repository_groups) . PHP_EOL; 
    }
    
    protected function getProjectPermissionConfFile($project) {
        $prjConfDir = 'conf/projects';
        if (!is_dir($prjConfDir)) {
            mkdir($prjConfDir);
        }
        return $prjConfDir.'/'.$project->getUnixName().'.conf';
    }
    
    protected function writeGitConfig($config_file, $config_datas) {
        if (strlen($config_datas) !== file_put_contents($config_file, $config_datas)) {
            return false;
        }
        return $this->gitExec->add($config_file);
    }
    
    protected function commitConfigFor($project) {
        if ($this->updateMainConfIncludes($project)) {
            return $this->gitExec->commit('Update: '.$project->getUnixName());
        }
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
            $ok = $this->gitExec->mv(
                'conf/projects/'. $oldName .'.conf',
                'conf/projects/'. $newName .'.conf'
            );
            if ($ok) {
                //conf/projects/newone.conf
                $orig = file_get_contents('conf/projects/'. $newName .'.conf');
                $dest = preg_replace('`(^|\n)repo '. preg_quote($oldName) .'/`', '$1repo '. $newName .'/', $orig);
                $dest = str_replace('@'. $oldName .'_project_', '@'. $newName .'_project_', $dest);
                $dest = preg_replace("%$oldName/(.*) = \"%", "$newName/$1 = \"", $dest);
                file_put_contents('conf/projects/'. $newName .'.conf', $dest);
                $this->gitExec->add('conf/projects/'. $newName .'.conf');
                
                //conf/gitolite.conf
                $orig = file_get_contents($this->confFilePath);
                $dest = str_replace('include "projects/'. $oldName .'.conf"', 'include "projects/'. $newName .'.conf"', $orig);
                file_put_contents($this->confFilePath, $dest);
                $this->gitExec->add($this->confFilePath);
                
                //commit
                $ok = $this->gitExec->commit('Rename project '. $oldName .' to '. $newName) && $this->gitExec->push();
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
    
    public function fork($repo, $old_ns, $new_ns) {
        $source = unixPathJoin(array($this->getRepositoriesPath(), $old_ns, $repo)) .'.git';
        $target = unixPathJoin(array($this->getRepositoriesPath(), $new_ns, $repo)) .'.git';
        if (!is_dir($target)) {
            $asGroupGitolite = 'sg - gitolite -c ';
            $cmd = 'umask 0007; '.$asGroupGitolite.' "git clone --bare '. $source .' '. $target.'"';
            $clone_result = $this->executeShellCommand($cmd);
            
            $copyHooks  = 'cd '.$this->getRepositoriesPath().'; ';
            $copyHooks .= $asGroupGitolite.' "cp -f '.$source.'/hooks/* '.$target.'/hooks/"';
            $this->executeShellCommand($copyHooks);
            
            $saveNamespace = 'cd '.$this->getRepositoriesPath().'; ';
            $saveNamespace .= $asGroupGitolite.' "echo -n '.$new_ns.' > '.$target.'/tuleap_namespace"';
            $this->executeShellCommand($saveNamespace);
            
            return $clone_result;
        }
        return false;
    }

    protected function executeShellCommand($cmd) {
        $cmd = $cmd.' 2>&1';
        exec($cmd, $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            throw new Git_Command_Exception($cmd, $output, $retVal);
        }
    }

}
?>
