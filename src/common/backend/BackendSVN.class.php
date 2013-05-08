<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 *
 * 
 */

require_once('common/backend/Backend.class.php');
require_once('common/dao/UGroupDao.class.php');
require_once('common/project/UGroup.class.php');
require_once('common/dao/ServiceDao.class.php');
require_once('common/svn/SVNAccessFile.class.php');
require_once('common/include/Error.class.php');
require_once('www/svn/svn_utils.php');
require_once('common/svn/SVN_Apache_SvnrootConf.class.php');
require_once('common/include/Config.class.php');

/**
 * Backend class to work on subversion repositories
 */
class BackendSVN extends Backend {

    protected $SVNApacheConfNeedUpdate;

    /**
     * For mocking (unit tests)
     * 
     * @return UGroupDao
     */
    protected function getUGroupDao() {
        return new UGroupDao(CodendiDataAccess::instance());
    }
     /**
     * For mocking (unit tests)
     *
     * @param array $row a row from the db for a ugroup
     * 
     * @return UGroup
     */
    protected function getUGroupFromRow($row) {
        return new UGroup($row);
    }
    /**
     * For mocking (unit tests)
     * 
     * @return ServiceDao
     */
    function _getServiceDao() {
        return new ServiceDao(CodendiDataAccess::instance());
    }

    
    /**
     * Wrapper for Config
     * 
     * @return Config
     */
    protected function getConfig($var) {
        return Config::get($var);
    }
    
    /**
     * Create project SVN repository
     * If the directory already exists, nothing is done.
     * 
     * @param int $group_id The id of the project to work on
     * 
     * @return boolean true if repo is successfully created, false otherwise
     */
    public function createProjectSVN($group_id) {
        $project=$this->getProjectManager()->getProject($group_id);
        if (!$project) {
            return false;
        }
        $unix_group_name=$project->getUnixName(false); // May contain upper-case letters
        $svn_dir=$GLOBALS['svn_prefix']."/".$unix_group_name;
        if (!is_dir($svn_dir)) {
            // Let's create a SVN repository for this group
            if (!mkdir($svn_dir)) {
                $this->log("Can't create project SVN dir: $svn_dir", Backend::LOG_ERROR);
                return false;
            }
            system($GLOBALS['svnadmin_cmd']." create $svn_dir --fs-type fsfs");

            $this->recurseChownChgrp($svn_dir, $this->getHTTPUser(), $unix_group_name);
            system("chmod g+rw $svn_dir");
        }


        // Put in place the svn post-commit hook for email notification
        if (!$this->updateHooks($project)) {
            return false;
        }

        if (!$this->updateSVNAccess($group_id)) {
            $this->log("Can't update SVN access file", Backend::LOG_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Check if repository of given project exists
     * @param Project
     * @return true is repository already exists, false otherwise
     */
    function repositoryExists($project) {
        $unix_group_name=$project->getUnixName(false); // May contain upper-case letters
        $svn_dir=$GLOBALS['svn_prefix']."/".$unix_group_name;
        if (is_dir($svn_dir)) {
          return true;
        } else return false; 
    }


    /**
     * Put in place the svn post-commit hook for email notification
     * if not present (if the file does not exist it is created)
     * 
     * @param Project $project The project to work on
     * 
     * @return boolean true on success or false on failure
     */
    public function updateHooks($project) {
        $unix_group_name=$project->getUnixName(false); // May contain upper-case letters
        $svn_dir=$GLOBALS['svn_prefix']."/".$unix_group_name;

        if ($project->isSVNTracked()) {
            $filename = "$svn_dir/hooks/post-commit";
            $update_hook=false;
            if (! is_file($filename)) {
                // File header
                $fp = fopen($filename, 'w');
                fwrite($fp, "#!/bin/sh\n");
                fwrite($fp, "# POST-COMMIT HOOK\n");
                fwrite($fp, "#\n");
                fwrite($fp, "# The post-commit hook is invoked after a commit.  Subversion runs\n");
                fwrite($fp, "# this hook by invoking a program (script, executable, binary, etc.)\n");
                fwrite($fp, "# named 'post-commit' (for which this file is a template) with the \n");
                fwrite($fp, "# following ordered arguments:\n");
                fwrite($fp, "#\n");
                fwrite($fp, "#   [1] REPOS-PATH   (the path to this repository)\n");
                fwrite($fp, "#   [2] REV          (the number of the revision just committed)\n\n");
                fclose($fp);
                $update_hook=true;
            } else {
                $file_array=file($filename);
                if (!in_array($this->block_marker_start, $file_array)) {
                    $update_hook=true;
                }
            }
            if ($update_hook) {
                $command  ='REPOS="$1"'."\n";
                $command .='REV="$2"'."\n";
                $command .=$GLOBALS['codendi_bin_prefix'].'/commit-email.pl "$REPOS" "$REV" 2>&1 >/dev/null';
                $this->addBlock($filename, $command);
                $this->chown($filename, $this->getHTTPUser());
                $this->chgrp($filename, $unix_group_name);
                chmod("$filename", 0775);
            }
        } else {
            // Make sure that the Codendi blocks are removed
            $filename = "$svn_dir/hooks/post-commit";
            $update_hook=false;
            if (is_file($filename)) {
                $file_array=file($filename);
                if (in_array($this->block_marker_start, $file_array)) {
                    $this->removeBlock($filename);
                }
            }
        }

        // Put in place the Codendi svn pre-commit hook
        // if not present (if the file does not exist it is created)
        $filename = "$svn_dir/hooks/pre-commit";
        $update_hook = false;
        if (! is_file($filename)) {
            // File header
            $fp = fopen($filename, 'w');
            fwrite($fp, "#!/bin/sh\n\n");
            fwrite($fp, "# PRE-COMMIT HOOK\n");
            fwrite($fp, "#\n");
            fwrite($fp, "# The pre-commit hook is invoked before a Subversion txn is\n");
            fwrite($fp, "# committed.  Subversion runs this hook by invoking a program\n");
            fwrite($fp, "# (script, executable, binary, etc.) named 'pre-commit' (for which\n");
            fwrite($fp, "# this file is a template), with the following ordered arguments:\n");
            fwrite($fp, "#\n");
            fwrite($fp, "#   [1] REPOS-PATH   (the path to this repository)\n");
            fwrite($fp, "#   [2] TXN-NAME     (the name of the txn about to be committed)\n");
            $update_hook=true;
        } else {
            $file_array=file($filename);
            if (!in_array($this->block_marker_start, $file_array)) {
                $update_hook=true;
            }
        }
        if ($update_hook) {
            $command  = 'REPOS="$1"'."\n";
            $command .= 'TXN="$2"'."\n";
            $command .= $GLOBALS['codendi_dir'].'/src/utils/php-launcher.sh '.$GLOBALS['codendi_bin_prefix'].'/codendi_svn_pre_commit.php "$REPOS" "$TXN" || exit 1';
            $this->addBlock($filename, $command);
            $this->chown($filename, $this->getHTTPUser());
            $this->chgrp($filename, $unix_group_name);
            chmod("$filename", 0775);
        }

        if ($project->canChangeSVNLog()) {
            $this->enableCommitMessageUpdate($unix_group_name);
        } else {
            $this->disableCommitMessageUpdate($unix_group_name);
        }
        
        return true;
    }

    /**
     * Wrapper for tests
     *
     * @return SVNAccessFile
     */
    function _getSVNAccessFile() {
        return new SVNAccessFile();
    }

    /**
     * Update Subversion DAV access control file if needed
     *
     * @param int    $group_id        the id of the project
     * @param String $ugroup_name     New name of the renamed ugroup (if any)
     * @param String $ugroup_old_name Old name of the renamed ugroup (if any)
     *
     * @return boolean true on success or false on failure
     */
    public function updateSVNAccess($group_id, $ugroup_name = null, $ugroup_old_name = null) {
        $project = $this->getProjectManager()->getProject($group_id);
        if (!$project) {
            return false;
        }
        $unix_group_name = $project->getUnixName(false); // May contain upper-case letters
        $svn_dir = $GLOBALS['svn_prefix']."/".$unix_group_name;
        if (!is_dir($svn_dir)) {
            $this->log("Can't update SVN Access file: project SVN repo is missing: $svn_dir", Backend::LOG_ERROR);
            return false;
        }

        $svnaccess_file = $svn_dir."/.SVNAccessFile";
        $svnaccess_file_old = $svnaccess_file.".old";
        $svnaccess_file_new = $svnaccess_file.".new";
        // if you change these block markers also change them in
        // src/www/svn/svn_utils.php
        $default_block_start="# BEGIN CODENDI DEFAULT SETTINGS - DO NOT REMOVE\n";
        $default_block_end="# END CODENDI DEFAULT SETTINGS\n";
        $custom_perms='';
        $public_svn = 1; // TODO
        
        $defaultBlock = '';
        $defaultBlock .= $this->getSVNAccessGroups($project);
        $defaultBlock .= $this->getSVNAccessRootPathDef($project);
        
        // Retrieve custom permissions, if any
        if (is_file("$svnaccess_file")) {
            $svnaccess_array = file($svnaccess_file);
            $configlines = false;
            $contents = '';
            while ($line = array_shift($svnaccess_array)) {
                if ($configlines) {
                    $contents .= $line;
                }
                if (strcmp($line, $default_block_end) == 0) { 
                    $configlines=1;
                }
            }
            $saf = $this->_getSVNAccessFile();
            $saf->setRenamedGroup($ugroup_name, $ugroup_old_name);
            $saf->setPlatformBlock($defaultBlock);
            $custom_perms .= $saf->parseGroupLines($project, $contents);
        }

        $fp = fopen($svnaccess_file_new, 'w');

        // Codendi specifc
        fwrite($fp, "$default_block_start");
        fwrite($fp, $defaultBlock);
        fwrite($fp, "$default_block_end");

        // Custom permissions
        if ($custom_perms) {
            fwrite($fp, $custom_perms);
        }
        fclose($fp);

        // Backup existing file and install new one if they are different
        $this->installNewFileVersion($svnaccess_file_new, $svnaccess_file, $svnaccess_file_old);

        // set group ownership, admin user as owner so that
        // PHP scripts can write to it directly
        $this->chown($svnaccess_file, $this->getHTTPUser());
        $this->chgrp($svnaccess_file, $unix_group_name);
        chmod("$svnaccess_file", 0775);
        
        return true;
    }

    /**
     * Rewrite the .SVNAccessFile if removed
     *
     * @return void
     */
    public function checkSVNAccessPresence($group_id) {
        $project = $this->getProjectManager()->getProject($group_id);
        if (!$project) {
            return false;
        }
        $unix_group_name = $project->getUnixName(false); // May contain upper-case letters
        $svn_dir = $GLOBALS['svn_prefix']."/".$unix_group_name;
        if (!is_dir($svn_dir)) {
            $this->log("Can't update SVN Access file: project SVN repo is missing: $svn_dir", Backend::LOG_ERROR);
            return false;
        }
        
        $svnaccess_file = $svn_dir."/.SVNAccessFile";
        
        if (!is_file($svnaccess_file)) {
            return $this->updateSVNAccess($group_id);
        }
        return true;
    }

    /**
     * SVNAccessFile groups definitions
     *
     * @param Project $project
     * @return String
     */
    function getSVNAccessGroups($project) {
        $conf = "[groups]\n";
        $conf .= $this->getSVNAccessProjectMembers($project);
        $conf .= $this->getSVNAccessUserGroupMembers($project);
        $conf .= "\n";
        return $conf;
    }

    /**
     * SVNAccessFile project members group definition
     *
     * User names must be in lowercase
     *
     * @param Project $project
     *
     * @return String
     */
    function getSVNAccessProjectMembers($project) {
        $list  = "";
        $first = true;
        foreach ($project->getMembersUserNames() as $member) {
            if (!$first) {
                $list .= ', ';
            }
            $first = false;
            $list .= strtolower($member['user_name']);
        }
        return "members = ".$list."\n";
    }

    /**
     * SVNAccessFile ugroups definitions
     *
     * @param Project $project
     *
     * @return String
     */
    function getSVNAccessUserGroupMembers($project) {
        $conf = "";
        $ugroup_dao = $this->getUGroupDao();
        $dar = $ugroup_dao->searchByGroupId($project->getId());
        foreach ($dar as $row) {
            $ugroup = $this->getUGroupFromRow($row);
            // User names must be in lowercase
            $members_list = strtolower(implode(", ", $ugroup->getMembersUserName()));
            if ($ugroup->getName() && $members_list) {
                $conf .= $ugroup->getName()." = ".$members_list."\n";
            }
        }
        $conf .= "\n";
        return $conf;
    }

    /**
     * SVNAccessFile definition for repository root
     *
     * @param Project $project
     *
     * @return String
     */
    function getSVNAccessRootPathDef($project) {
        $conf = "[/]\n";
        if (!$project->isPublic() || $project->isSVNPrivate()) {
            $conf .= "* = \n";
        } else {
            $conf .= "* = r\n";
        }
        $conf .= "@members = rw\n";
        return $conf;
    }

    /**
     * Update SVN access files into all projects that a given user belongs to
     * 
     * It includes:
     * + projects the user is member of 
     * + projects that have user groups that contains the user
     * 
     * @param PFUser $user
     * 
     * @return Boolean
     */
    public function updateSVNAccessForGivenMember($user) {
        $projects = $user->getAllProjects(); 
        if (isset($projects)) {
            foreach ($projects as $groupId) {
                $project = $this->getProjectManager()->getProject($groupId);
                $this->updateProjectSVNAccessFile($project);
            }
        }
        return true;
    }

    /**
     * Update SVNAccessFile of a project
     * 
     * @param Project $project The project to update
     * 
     * @return Boolean
     */
    public function updateProjectSVNAccessFile(Project $project) {
        if ($this->repositoryExists($project)) {
            return $this->updateSVNAccess($project->getID());
        }
        return true;
    }

    /**
     * Force apache conf update
     *
     * @return void
     */
    public function setSVNApacheConfNeedUpdate() {
        $this->SVNApacheConfNeedUpdate = true;
    }

    /**
     * Say if apache conf need update
     * 
     * @return boolean
     */
    public function getSVNApacheConfNeedUpdate() {
        return $this->SVNApacheConfNeedUpdate;
    }


    /**
     * Add Subversion DAV definition for all projects in a dedicated Apache 
     * configuration file
     * 
     * @return boolean true on success or false on failure
     */
    public function generateSVNApacheConf() {
        $svn_root_file = $GLOBALS['svn_root_file'];
        $svn_root_file_old = $svn_root_file.".old";
        $svn_root_file_new = $svn_root_file.".new";
        
        $conf = $this->getApacheConf();
        if (file_put_contents($svn_root_file_new, $conf) !== strlen($conf)) {
            $this->log("Error while writing to $svn_root_file_new", Backend::LOG_ERROR);
            return false;
        }

        $this->chown("$svn_root_file_new", $this->getHTTPUser());
        $this->chgrp("$svn_root_file_new", $this->getHTTPUser());
        chmod("$svn_root_file_new", 0640);

        // Backup existing file and install new one
        return $this->installNewFileVersion($svn_root_file_new, $svn_root_file, $svn_root_file_old, true);
    }

    function getApacheConf() {
        $projects = $this->_getServiceDao()->searchActiveUnixGroupByUsedService('svn');
        $factory  = $this->getSVNApacheAuthFactory();
        $conf = new SVN_Apache_SvnrootConf($factory, $projects);
        return $conf->getFullConf();
    }
    
    protected function getSVNApacheAuthFactory() {
        return new SVN_Apache_Auth_Factory();
    }
    
    /**
     * Archive SVN repository: stores a tgz in temp dir, and remove the directory
     *
     * @param int $group_id The id of the project to work on
     * 
     * @return boolean true on success or false on failure
     */
    public function archiveProjectSVN($group_id) {
        $project=$this->getProjectManager()->getProject($group_id);
        if (!$project) {
            return false;
        }
        $mydir=$GLOBALS['svn_prefix']."/".$project->getUnixName(false);
        $backupfile=$GLOBALS['tmp_dir']."/".$project->getUnixName(false)."-svn.tgz";

        if (is_dir($mydir)) {
            system("cd ".$GLOBALS['svn_prefix']."; tar cfz $backupfile ".$project->getUnixName(false));
            chmod($backupfile, 0600);
            $this->recurseDeleteInDir($mydir);
            rmdir($mydir);
        }
        return true;
    }
    
    /**
     * Make the cvs repository of the project private or public
     * 
     * @param Project $project    The project to work on
     * @param boolean $is_private true if the repository is private
     * 
     * @return boolean true if success
     */
    public function setSVNPrivacy($project, $is_private) {
        $perms = $is_private ? 0770 : 0775;
        $svnroot = $GLOBALS['svn_prefix'] . '/' . $project->getUnixName(false);
        return is_dir($svnroot) && $this->chmod($svnroot, $perms);
    }


    /** 
     * Check ownership/mode/privacy of repository 
     * 
     * @param Project $project The project to work on
     * 
     * @return boolean true if success
     */
    public function checkSVNMode($project) {
        $unix_group_name =  $project->getUnixName(false);
        $svnroot = $GLOBALS['svn_prefix'] . '/' . $unix_group_name;
        $is_private = !$project->isPublic() || $project->isSVNPrivate();
        if ($is_private) {
            $perms = fileperms($svnroot);
            // 'others' should have no right on the repository
            if (($perms & 0x0004) || ($perms & 0x0002) || ($perms & 0x0001) || ($perms & 0x0200)) {
                $this->log("Restoring privacy on SVN dir: $svnroot", Backend::LOG_WARNING);
               $this->setSVNPrivacy($project, $is_private);
            }
        } 
        // Sometimes, there might be a bad ownership on file (e.g. chmod failed, maintenance done as root...)
        $files_to_check=array('db/current', 'hooks/pre-commit', 'hooks/post-commit', 'db/rep-cache.db');
        $need_owner_update = false;
        foreach ($files_to_check as $file) {
            // Get file stat 
            if (file_exists("$svnroot/$file")) {
                $stat = stat("$svnroot/$file");
                if ( ($stat['uid'] != $this->getHTTPUserUID())
                     || ($stat['gid'] != $project->getUnixGID()) ) {
                    $need_owner_update = true;
                }
            }
        }
        if ($need_owner_update) {
            $this->log("Restoring ownership on SVN dir: $svnroot", Backend::LOG_INFO);
            $this->recurseChownChgrp($svnroot, $this->getHTTPUser(), $unix_group_name);
            $this->chown($svnroot, $this->getHTTPUser());
            $this->chgrp($svnroot, $unix_group_name);
            system("chmod g+rw $svnroot");
        }

        return true;
    }
    /**
     * Check if given name is not used by a repository or a file or a link
     * 
     * @param String $name
     * 
     * @return false if repository or file  or link already exists, true otherwise
     */
    function isNameAvailable($name) {
        $path = $GLOBALS['svn_prefix']."/".$name;
        return (!$this->fileExists($path));
    }
    
    /**
     * Rename svn repository (following project unix_name change)
     * 
     * @param Project $project
     * @param String  $newName
     * 
     * @return Boolean
     */
    public function renameSVNRepository($project, $newName) {
        return rename($GLOBALS['svn_prefix'].'/'.$project->getUnixName(false), $GLOBALS['svn_prefix'].'/'.$newName);
    }

    private function enableCommitMessageUpdate($unix_group_name) {
        symlink($GLOBALS['codendi_dir'].'/src/utils/pre-revprop-change', $this->getHookPath($unix_group_name, 'pre-revprop-change'));
        symlink($GLOBALS['codendi_dir'].'/src/utils/tuleap_svn_propset.php', $this->getHookPath($unix_group_name, 'post-revprop-change'));
    }

    private function disableCommitMessageUpdate($unix_group_name) {
        $this->deleteHook($unix_group_name, 'pre-revprop-change');
        $this->deleteHook($unix_group_name, 'post-revprop-change');
    }

    private function deleteHook($unix_group_name, $hook_name) {
        $path = $this->getHookPath($unix_group_name, $hook_name);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function getHookPath($unix_group_name, $hook_name) {
        return $GLOBALS['svn_prefix'].'/'.$unix_group_name.'/hooks/'.$hook_name;
    }
}

?>
