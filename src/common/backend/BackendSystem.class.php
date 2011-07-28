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
require_once('common/dao/UserDao.class.php');
require_once('common/wiki/lib/WikiAttachment.class.php');


class BackendSystem extends Backend {


    protected $needRefreshUserCache=false;
    protected $needRefreshGroupCache=false;


    /**
     * Warn system that the user lists has been updated (new user)
     * This is required so that nscd (name service caching daemon) knows that it needs
     * to update its user data.
     *
     * NOTE: Don't need to update cache on deleted user since shadow information are not cached, 
     * so even if the cache is not refreshed, a deleted user won't be able to login
     * @return true on success, false otherwise
     */
    public function refreshUserCache() {
        return (system("/usr/sbin/nscd --invalidate=passwd") !== false);
    }


    public function setNeedRefreshUserCache() {
        $this->needRefreshUserCache=true;
    }

    public function getNeedRefreshUserCache() {
        return $this->needRefreshUserCache;
    }

    /**
     * Warn system that the group lists has been updated (new group, new member to group or memeber removed from group)
     * This is required so that nscd (name service caching daemon) knows that it needs
     * to update its group data.
     *
     * NOTE: Currently, we don't update group cache on deleted group, new user and deleted user
     * @return true on success, false otherwise
     */
    public function refreshGroupCache() {
        return (system("/usr/sbin/nscd --invalidate=group") !== false);
    }

    public function setNeedRefreshGroupCache() {
        $this->needRefreshGroupCache=true;
    }

    public function getNeedRefreshGroupCache() {
        return $this->needRefreshGroupCache;
    }

    /**
     * Create user home directory
     * Also copy files from the skel directory to the new home directory.
     * If the directory already exists, nothing is done.
     * @return true if directory is successfully created, false otherwise
     */
    public function createUserHome($user_id) {
        $user=$this->getUserManager()->getUserById($user_id);
        if (!$user) return false;
        $homedir=$GLOBALS['homedir_prefix']."/".$user->getUserName();

        //echo "Creating $homedir\n";

        if (!is_dir($homedir)) {
            if (is_dir(strtolower($homedir))) {
                // Codendi 3.6 to 4.0 migration
                if (!rename(strtolower($homedir),$homedir)) {
                    $this->log("Can't rename user home ".strtolower($homedir)." to $homedir", Backend::LOG_ERROR);
                    return false;
                }
            } else if (mkdir($homedir,0751)) {
                // copy the contents of the $codendi_shell_skel dir into homedir
                if (is_dir($GLOBALS['codendi_shell_skel'])) {
                    system("cd ".$GLOBALS['codendi_shell_skel']."; tar cf - . | (cd  $homedir ; tar xf - )");
                }
                touch($homedir);
                $this->recurseChownChgrp($homedir,$user->getUserName(),$user->getUserName());

                return true;
            } else {
                $this->log("Can't create user home: $homedir", Backend::LOG_ERROR);
            }
        }
        return false;
    }

    public function userHomeExists($username) {
    	return (is_dir($GLOBALS['homedir_prefix']."/".$username));
    }
    /**
     * Create project home directory
     * If the directory already exists, nothing is done.
     * @return true if directory is successfully created, false otherwise
     */
    public function createProjectHome($group_id) {
        $project=$this->getProjectManager()->getProject($group_id);
        if (!$project) return false;

        $unix_group_name=$project->getUnixName(false); // May contain upper-case letters
        $projdir=$GLOBALS['grpdir_prefix']."/".$unix_group_name;
        $ht_dir=$projdir."/htdocs";
        $private_dir = $projdir .'/private';
        $ftp_anon_dir=$GLOBALS['ftp_anon_dir_prefix']."/".$unix_group_name;
        $ftp_frs_dir=$GLOBALS['ftp_frs_dir_prefix']."/".$unix_group_name;

        if (!is_dir($projdir)) {
        	// Lets create the group's homedir.
	        // (put the SGID sticky bit on all dir so that all files
	        // in there are owned by the project group and not
	        // the user own group
            // Moreover, we need to chmod after mkdir because the umask may not allow the precised mode
            if (mkdir($projdir,0775)) {
                $this->chown($projdir, "dummy");
                $this->chgrp($projdir, $unix_group_name);
                $this->chmod($projdir, 02775);
            } else {
                $this->log("Can't create project home: $projdir", Backend::LOG_ERROR);
                return false;
            }
        } else {
            // Get directory stat 
            $stat = stat("$projdir");
            if ($stat) {
                if ($stat['gid'] != $project->getUnixGID()) {
                    $this->log("Restoring ownership on project dir: $projdir", Backend::LOG_WARNING);
                    $this->recurseChgrp($projdir,$unix_group_name);
                }
            }
        }

        // /!\ Be careful not to lowercase the whole path as it will also modify
        // parents case and will lead to errors. Eg:
        // /Toto/home/groups/TestProj -> /toto/home/groups/testproj
        // instead of
        // /Toto/home/groups/TestProj -> /Toto/home/groups/testproj
        $lcProjectDir = $GLOBALS['grpdir_prefix']."/".$project->getUnixName(true);
        if ($projdir != $lcProjectDir) {
            $lcprojlnk = $lcProjectDir;
            if (!is_link($lcprojlnk)) {
                if (!symlink($projdir,$lcprojlnk)) {
                    $this->log("Can't create project link: $lcprojlnk", Backend::LOG_ERROR);
                }
            }
        }
                
        if (!is_dir($ht_dir)) {
            // Project web site directory
            if (mkdir($ht_dir,0775)) {
                $this->chown($ht_dir, "dummy");
                $this->chgrp($ht_dir, $unix_group_name);
                chmod($ht_dir, 02775);

                // Copy custom homepage template for project web site if any
                $custom_homepage = $GLOBALS['sys_custom_incdir']."/en_US/others/default_page.php";
                $default_homepage = $GLOBALS['sys_incdir']."/en_US/others/default_page.php";
                $dest_homepage = $ht_dir."/index.php";
                if (is_file($custom_homepage)) {
                    copy($custom_homepage,$dest_homepage);
                } else if (is_file($default_homepage)) {
                    copy($default_homepage,$dest_homepage);
                }
                if (is_file($dest_homepage)) {
                    $this->chown($dest_homepage, "dummy");
                    $this->chgrp($dest_homepage, $unix_group_name);
                    chmod($dest_homepage,0644);
                }

            } else {
                $this->log("Can't create project web root: $ht_dir", Backend::LOG_ERROR);
                return false;
            }
        }

        if (!is_dir($ftp_anon_dir)) {
            // Now lets create the group's ftp homedir for anonymous ftp space
            // This one must be owned by the project gid so that all project
            // admins can work on it (upload, delete, etc...)
            if (mkdir($ftp_anon_dir,02775)) {
                $this->chown($ftp_anon_dir, "dummy");
                $this->chgrp($ftp_anon_dir, $unix_group_name);
                chmod($ftp_anon_dir, 02775);
            } else {
                $this->log("Can't create project public ftp dir: $ftp_anon_dir", Backend::LOG_ERROR);
                return false;
            }
        }
        
        if (!is_dir($ftp_frs_dir)) {
            // Now lets create the group's ftp homedir for anonymous ftp space
            // This one must be owned by the project gid so that all project
            // admins can work on it (upload, delete, etc...)
            if (mkdir($ftp_frs_dir,0771)) {
                chmod($ftp_frs_dir, 0771);
                $this->chown($ftp_frs_dir, "dummy");
                $this->chgrp($ftp_frs_dir, $unix_group_name);
            } else {
                $this->log("Can't create project file release dir: $ftp_frs_dir", Backend::LOG_ERROR);
                return false;
            }
        }
        
        if (!is_dir($private_dir)) {
            if (mkdir($private_dir,0770)) {
                $this->chmod($private_dir, 02770);
                $this->chown($private_dir, "dummy");
                $this->chgrp($private_dir, $unix_group_name);
            } else {
                $this->log("Can't create project private dir: $private_dir", Backend::LOG_ERROR);
                return false;
            }
        } else {
            // Check that perms are OK
            $perms = fileperms($private_dir);
            // 'others' should have no right on the repository
            if (($perms & 0x0004) || ($perms & 0x0002) || ($perms & 0x0001) || ($perms & 0x0200)) {
            	$this->chmod($private_dir, 02770);		
            }
            // Get directory stat 
            $stat = stat("$private_dir");
            if ($stat) {
                $dummy_user=posix_getpwnam('dummy');
                if ( ($stat['uid'] != $dummy_user['uid'])
                     || ($stat['gid'] != $project->getUnixGID()) ) {
                    $this->log("Restoring privacy on private dir: $private_dir", Backend::LOG_WARNING);
                    $this->chown($private_dir, "dummy");
                    $this->chgrp($private_dir, $unix_group_name);
                }
            }
        }
        return true;
    }

    /**
     * Archive the user home directory
     * @return true if directory is successfully archived, false otherwise
     */
    public function archiveUserHome($user_id) {
        $user=$this->getUserManager()->getUserById($user_id);
        if (!$user) return false;
        $homedir=$GLOBALS['homedir_prefix']."/".$user->getUserName();
        $backupfile=$GLOBALS['tmp_dir']."/".$user->getUserName().".tgz";

        if (is_dir($homedir)) {
            system("cd ".$GLOBALS['homedir_prefix']."; tar cfz $backupfile ".$user->getUserName());
            chmod($backupfile,0600);
            $this->recurseDeleteInDir($homedir);
            rmdir($homedir);
        }
        return true;

    }


    /**
     * Archive the project directory
     * @return true if directory is successfully archived, false otherwise
     */
    public function archiveProjectHome($group_id) {
        $project=$this->getProjectManager()->getProject($group_id);
        if (!$project) return false;
        $mydir=$GLOBALS['grpdir_prefix']."/".$project->getUnixName(false);
        $backupfile=$GLOBALS['tmp_dir']."/".$project->getUnixName(false).".tgz";

        if (is_dir($mydir)) {
            system("cd ".$GLOBALS['grpdir_prefix']."; tar cfz $backupfile ".$project->getUnixName(false));
            chmod($backupfile,0600);
            $this->recurseDeleteInDir($mydir);
            rmdir($mydir);


            // Remove lower-case symlink if it exists
            if ($project->getUnixName(true) != $project->getUnixName(false)) {
                if (is_link($GLOBALS['grpdir_prefix']."/".$project->getUnixName(true))) {
                    unlink($GLOBALS['grpdir_prefix']."/".$project->getUnixName(true));
                }
            }
        }
        return true;
    }

    /**
     * Remove deleted releases and released files
     */
    public function cleanupFRS() {
        // Purge all deleted files older than 3 days old
        if (!isset($GLOBALS['sys_file_deletion_delay'])) {
            $delay = 3;
        } else {
            $delay = intval($GLOBALS['sys_file_deletion_delay']);
        }
        $time = $_SERVER['REQUEST_TIME'] - (3600*24*$delay);
        
        $frs = $this->getFRSFileFactory();
        $status =  $frs->moveFiles($time, $this);

        // {{{ /!\ WARNING HACK /!\
        // We keep the good old purge mecanism for at least one release to clean
        // the previously deleted files
        // Delete all files under DELETE that are older than 10 days
        //$delete_dir = $GLOBALS['ftp_frs_dir_prefix']."/DELETED";
        //system("find $delete_dir -type f -mtime +10 -exec rm {} \\;");
        //system("find $delete_dir -mindepth 1 -type d -empty -exec rm -R {} \\;");
        // }}} /!\ WARNING HACK /!\

        //Manage the purge of wiki attachments
        $wiki = $this->getWikiAttachment();
        $status = $status & $wiki->purgeAttachments($time);

        $em = EventManager::instance();
        $em->processEvent('backend_system_purge_files', array('time' => $time));

        return ($status);
    }

    /**
     * dumps SSH authorized_keys into all users homedirs
     */
    public function dumpSSHKeys() {
        $userdao = new UserDao(CodendiDataAccess::instance());
        foreach($userdao->searchSSHKeys() as $row) {
            $this->writeSSHKeys($row['user_name'], $row['authorized_keys']);
        }
        EventManager::instance()->processEvent(Event::DUMP_SSH_KEYS, null);
        return true;
    }
    
    /**
     * dumps SSH authorized_keys for a user in its homedir
     * @param User $user
     */
    public function dumpSSHKeysForUser($user) {
        return $this->writeSSHKeys($user->getUserName(), $user->getAuthorizedKeys());
    }
    
    /**
     * Write SSH authorized_keys into a user homedir
     * @param string $ssh_keys from the db
     * @param string $username
     */
    protected function writeSSHKeys($username, $ssh_keys) {
        $ssh_keys = str_replace('###', "\n", $ssh_keys);
        $username = strtolower($username);
        $ssh_dir  = $GLOBALS['homedir_prefix'] ."/$username/.ssh";

        #execute the command as the user's key uid
        $user     = posix_getpwnam($username);
        if ( empty($user['uid']) || empty($user['gid']) ) {
            return false;
        }
        if ( !(posix_setegid($user['gid']) && posix_seteuid($user['uid'])) ) {
            return false;
        }
        if (!is_dir($ssh_dir)) {
            mkdir($ssh_dir);
            $this->chmod($ssh_dir, 0755);
            $this->chown($ssh_dir, $username);
            $this->chgrp($ssh_dir, $username);
        }        
        if ( file_put_contents("$ssh_dir/authorized_keys_new", $ssh_keys) === false) {
            posix_seteuid(0);
            $this->log("Enable to write authorized_keys_new file for $username", Backend::LOG_ERROR);
            return false;
        }
        if ( rename("$ssh_dir/authorized_keys_new", "$ssh_dir/authorized_keys") === false ) {
            posix_seteuid(0);
            $this->log("Enable to rename authorized_keys_new file for $username", Backend::LOG_ERROR);
            return false;
        }
        #set effective id to root's
        $this->chmod("$ssh_dir/authorized_keys", 0644);
        $this->chown("$ssh_dir/authorized_keys", $username);
        $this->chgrp("$ssh_dir/authorized_keys", $username);
        posix_seteuid(0);
        $this->log("Authorized_keys for $username written.", Backend::LOG_INFO);
        return true;
    }
     /**
     * Check if repository of given project exists
     * @param Project
     * @return true is repository already exists, false otherwise
     */
    function projectHomeExists($project) {
        $unix_group_name=$project->getUnixName(false); // May contain upper-case letters
        $home_dir=$GLOBALS['grpdir_prefix']."/".$unix_group_name;
        if (is_dir($home_dir)) {
            return true;
        } else return false; 
    }

    /**
     * Check if given name is not used by a repository or a file or a link under project directories
     * 
     * @param String $name
     * 
     * @return false if repository or file  or link already exists:
     **  with the same name under the grp_dir
     **  with its lower case name under the grp_dir 
     **  under FRS
     **  under ftp anon 
     * true otherwise
     */
    function isProjectNameAvailable($name) {
        $dir = $GLOBALS['grpdir_prefix']."/".$name;
        $frs = $GLOBALS['ftp_frs_dir_prefix']."/".$name;
        $ftp = $GLOBALS['ftp_anon_dir_prefix']."/".$name;
        
        if ($this->fileExists($dir)) {
            return false;
        } else if ($name != strtolower ($name)) {
            $link = $GLOBALS['grpdir_prefix']."/".strtolower($name);
            if ($this->fileExists($link)) {
                return false;
            }
        }
        if ($this->fileExists($frs)) {
            return false;
        } else if ($this->fileExists($ftp)) {
            return false;
        }
        return true;
    }
    
    /**
     * Check if given name is not used by a repository or a file or a link under user directory
     * 
     * @param String $name
     * 
     * @return false if repository or file  or link already exists, true otherwise
     */
    function isUserNameAvailable($name) {
        $path = $GLOBALS['homedir_prefix']."/".$name;
        return (!$this->fileExists($path));
    }
    
    
    /**
     * Rename project home directory (following project unix_name change)
     * 
     * @param Project $project
     * @param String  $newName
     * 
     * @return Boolean
     */
    public function renameProjectHomeDirectory($project, $newName) {
        if (is_link($GLOBALS['grpdir_prefix'].'/'.$newName)) {
            unlink($GLOBALS['grpdir_prefix'].'/'.$newName);
            return rename($GLOBALS['grpdir_prefix'].'/'.$project->getUnixName(false), $GLOBALS['grpdir_prefix'].'/'.$newName);
        } else {
            $renamed = rename($GLOBALS['grpdir_prefix'].'/'.$project->getUnixName(false), $GLOBALS['grpdir_prefix'].'/'.$newName);
            if ($renamed) {
                if (is_link($GLOBALS['grpdir_prefix'].'/'.$project->getUnixName(true))) {
                    unlink($GLOBALS['grpdir_prefix'].'/'.$project->getUnixName(true));
                }
                if (strtolower($newName) != $newName) {
                    return symlink($GLOBALS['grpdir_prefix'].'/'.$newName,$GLOBALS['grpdir_prefix'].'/'.strtolower($newName));
                } else {
                    return true;
                }
            }
            return $renamed;
        }
    }
    
    /**
     * Rename Directory where the released files are located (following project unix_name change)
     * 
     * @param Project $project
     * @param String  $newName
     * 
     * @return Boolean
     */
    public function renameFileReleasedDirectory($project, $newName) {
        if (is_dir($GLOBALS['ftp_frs_dir_prefix'].'/'.$project->getUnixName(false))) {
            return rename($GLOBALS['ftp_frs_dir_prefix'].'/'.$project->getUnixName(false), $GLOBALS['ftp_frs_dir_prefix'].'/'.$newName);
        } else {
            return true;
        }
    }
    
    /**
     * Rename anon ftp project homedir (following project unix_name change)
     * 
     * @param Project $project
     * @param String  $newName
     * 
     * @return Boolean
     */
    public function renameAnonFtpDirectory($project, $newName) {
        if (is_dir($GLOBALS['ftp_anon_dir_prefix'].'/'.$project->getUnixName(false))){
            return rename($GLOBALS['ftp_anon_dir_prefix'].'/'.$project->getUnixName(false), $GLOBALS['ftp_anon_dir_prefix'].'/'.$newName);
        } else {
            return true;
        }
    }
    
    /**
     * Rename User home directory 
     * 
     * @param User $user
     * @param String  $newName
     * 
     * @return Boolean
     */
    public function renameUserHomeDirectory($user, $newName) {
        return rename($GLOBALS['homedir_prefix'].'/'.$user->getUserName(), $GLOBALS['homedir_prefix'].'/'.$newName);
    }
    
    /**
     * Wrapper for getFRSFileFactory
     * 
     * @return FRSFileFactory
     */
    protected function getFRSFileFactory() {
        return new FRSFileFactory();
    }

    /**
     * Wrapper for getWikiAttachment
     * 
     * @return WikiAttachment
     */
    protected function getWikiAttachment() {
        return new WikiAttachment();
    }
    

}

?>
