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


class BackendSystem extends Backend {


    protected $needRefreshUserCache=false;
    protected $needRefreshGroupCache=false;


    /**
     * Hold an instance of the class
     */
    protected static $_instance;
    
    /**
     * Backends are singletons
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

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
            if (mkdir($homedir,0751)) {
                // copy the contents of the $codendi_shell_skel dir into homedir
                if (is_dir($GLOBALS['codendi_shell_skel'])) {
                    system("cd ".$GLOBALS['codendi_shell_skel']."; tar cf - . | (cd  $homedir ; tar xf - )");
                }
                $this->recurseChownChgrp($homedir,$user->getUserName(),$user->getUserName());

                return true;
            } else {
                $this->log("Can't create user home: $homedir");
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
                $this->log("Can't create project home: $projdir");
                return false;
            }
        }
        if ($projdir != strtolower($projdir)) {
            $lcprojlnk=strtolower($projdir);
            if (!is_link($lcprojlnk)) {
                if (!symlink($projdir,$lcprojlnk)) {
                    $this->log("Can't create project link: $lcprojlnk");
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
                $this->log("Can't create project web root: $ht_dir");
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
                $this->log("Can't create project public ftp dir: $ftp_anon_dir");
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
                $this->log("Can't create project file release dir: $ftp_frs_dir");
                return false;
            }
        }
        
        if (!is_dir($private_dir)) {
            if (mkdir($private_dir,0770)) {
                $this->chmod($private_dir, 02770);
                $this->chown($private_dir, "dummy");
                $this->chgrp($private_dir, $unix_group_name);
            } else {
                $this->log("Can't create project private dir: $private_dir");
                return false;
            }
        } else {
            // Check that perms are OK
            $perms = fileperms($private_dir);
            // 'others' should have no right on the repository
            if (($perms & 0x0004) || ($perms & 0x0002) || ($perms & 0x0001) || ($perms & 0x0200)) {
              $this->chmod($private_dir, 02770);		
            }
            // TODO: check owner/group
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
            return true;
        } else {
            return false;
        }
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
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove deleted releases and released files
     */
    public function cleanupFRS() {
        // location of the download/upload directories
        $delete_dir = $GLOBALS['ftp_frs_dir_prefix']."/DELETED";

        // list of files to be deleted
        $deleting_files = $GLOBALS['ftp_incoming_dir'] ."/.delete_files";
        $deleting_files_work = $GLOBALS['ftp_incoming_dir'] ."/.delete_files.work";

        // move the list of files to delete to a temp work file
        rename($deleting_files, $deleting_files_work);
        touch($deleting_files);
        $this->chown($deleting_files, $GLOBALS['sys_http_user']);
        
        //move all files in the .delete_files
        $waiting_files = file($deleting_files_work);
        if ($waiting_files === false) {
            $this->log("Cannot open $deleting_files_work");
            return false;
        } else {
            foreach($waiting_files as $line) {
                list($file, $project, $time) = explode('::', $line);
                $filename = $GLOBALS['ftp_frs_dir_prefix'] ."/$project/$file";
                if (!file_exists($filename)) {
                    $this->log("$filename doesn't exist");
                } else {
                    $subdirs = dirname($file);
                    mkdir("$delete_dir/$project/$subdirs", 0770, true);
                    
                    //make sure that since the deletion of the file nobody has submitted a new file with 
                    //the same filename
                    if (filemtime($filename) >= $time || fileatime($filename) >= $time || filectime($filename) >= $time) {
                        $this->log("Don't delete file $project/$file (modified since deletion)");
                    } else {
                        $this->log("Deleting file $project/$file");
                        rename($filename, "$delete_dir/$project/$file-$time");
                    }
                }
            }
            //delete all files under DELETE that are older than 7 days
            system("find $delete_dir -type f -mtime +7 -exec rm {} \\;");
            system("find $delete_dir -mindepth 1 -type d -empty -exec rm -R {} \\;");
        }
        return true;
    }
    
    /**
     * dumps SSH authorized_keys into all users homedirs
     */
    public function dumpSSHKeys() {
        $userdao = new UserDao(CodendiDataAccess::instance());
        foreach($userdao->searchSSHKeys() as $row) {
            $this->writeSSHKeys($row['user_name'], $row['authorized_keys']);
        }
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
        echo $username, $ssh_keys;
        $ssh_keys = str_replace('###', "\n", $ssh_keys);
        $username = strtolower($username);
        $ssh_dir = $GLOBALS['homedir_prefix'] ."/$username/.ssh";
        
        if (!is_dir($ssh_dir)) {
            mkdir($ssh_dir);
            $this->chmod($ssh_dir, 0755);
            $this->chown($ssh_dir, $username);
            $this->chgrp($ssh_dir, $username);
        }
        
        file_put_contents("$ssh_dir/authorized_keys_new", $ssh_keys);
        rename("$ssh_dir/authorized_keys_new", "$ssh_dir/authorized_keys");
        $this->chmod("$ssh_dir/authorized_keys", 0644);
        $this->chown("$ssh_dir/authorized_keys", $username);
        $this->chgrp("$ssh_dir/authorized_keys", $username);
        
        $this->log("Authorized_keys for $username written.");
    }

}

?>