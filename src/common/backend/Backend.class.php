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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once('common/backend/MailAliases.class.php');


class Backend {


    var $aliases;
    var $CVSRootListNeedUpdate;
    var $UseCVSNT;
    var $block_marker_start = "# !!! CodeX Specific !!! DO NOT REMOVE (NEEDED CODEX MARKER)";
    var $block_marker_end   = "# END OF NEEDED CODEX BLOCK";

    /**
     * Constructor
     */
    protected function Backend() {

        /* Make sure umask is properly positioned for the
         entire session. Root has umask 022 by default
         causing all the mkdir xxx, 775 to actually 
         create dir with permission 755 !!
         So set umask to 002 for the entire script session 
        */
        // Problem: "Avoid using this function in multithreaded webservers" http://us2.php.net/manual/en/function.umask.php
        //umask(002);
        
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;
    
    /**
     * Backend is a singleton
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }


    function _getUserManager() {
        return UserManager::instance();
    }

     function _getProjectManager() {
         return ProjectManager::instance();
     }
    

     /** Create chown function to allow mocking in unit tests */
     protected function chown($path, $uid) {
         return chown($path, $uid);
     }

     /** Create chgrp function to allow mocking in unit tests */
     protected function chgrp($path, $uid) {
         return chgrp($path, $uid);
     }


    public function log($message) {
        error_log($message."\n",3,$GLOBALS['codex_log']."/codendi_syslog");
    }

    /**
     * Return true if server uses CVS NT, or false if it uses GNU CVS
     */
     function useCVSNT() {
         if (isset($UseCVSNT)) return $UseCVSNT;
         if (is_file("/usr/bin/cvsnt")) {
             $UseCVSNT=true;
         } else {$UseCVSNT=false;}
         return $UseCVSNT;
     }

     
   /**
     * Recursive chown/chgrp function.
     * From comment at http://us2.php.net/manual/en/function.chown.php#40159
     */
    function recurseChownChgrp($mypath, $uid, $gid) {
        $this->chown($mypath, $uid);
        $this->chgrp($mypath, $gid);
        $d = opendir($mypath);
        while(($file = readdir($d)) !== false) {
            if ($file != "." && $file != "..") {
                
                $typepath = $mypath . "/" . $file ;

                //print $typepath. " : " . filetype ($typepath). "\n" ;
                if (filetype ($typepath) == 'dir') {
                    $this->recurseChownChgrp($typepath, $uid, $gid);
                } else {
                    $this->chown($typepath, $uid);
                    $this->chgrp($typepath, $gid);
                }
            }
        }
        closedir($d);
    }

    /**
     * Recursive rm function.
     * see: http://us2.php.net/manual/en/function.rmdir.php#87385
     * Note: the function will empty everything in the given directory but won't remove the directory itself
     */
    function recurseDeleteInDir($mypath) {
        $mypath= rtrim($mypath, '/');
        $d = opendir($mypath);
        while(($file = readdir($d)) !== false) {
            if ($file != "." && $file != "..") {
                
                $typepath = $mypath . "/" . $file ;

                if( is_dir($typepath) ) {
                    $this->recurseDeleteInDir($typepath);
                    rmdir($typepath);
                } else unlink($typepath);
            }
        }
        closedir($d);
    }

    /**
     * Create user home directory
     * Also copy files from the skel directory to the new home directory.
     * If the directory already exists, nothing is done.
     * @return true if directory is successfully created, false otherwise
     */
    function createUserHome($user_id) {
        $user=$this->_getUserManager()->getUserById($user_id);
        if (!$user) return false;
        $homedir=$GLOBALS['homedir_prefix']."/".$user->getUserName();

        //echo "Creating $homedir\n";

        if (!is_dir($homedir)) {
            if (mkdir($homedir,0751)) {
                // copy the contents of the $codex_shell_skel dir into homedir
                if (is_dir($GLOBALS['codex_shell_skel'])) {
                    system("cd ".$GLOBALS['codex_shell_skel']."; tar cf - . | (cd  $homedir ; tar xf - )");
                }
                $this->recurseChownChgrp($homedir,$user->getUserName(),$user->getUserName());

                return true;
            } else {
                $this->log("Can't create user home: $homedir");
            }
        }
        return false;
    }


    /**
     * Create project home directory
     * If the directory already exists, nothing is done.
     * @return true if directory is successfully created, false otherwise
     */
    function createProjectHome($group_id) {
        $project=$this->_getProjectManager()->getProject($group_id);
        if (!$project) return false;

        $unix_group_name=$project->getUnixName(false); // May contain upper-case letters
        $projdir=$GLOBALS['grpdir_prefix']."/".$unix_group_name;
        $ht_dir=$projdir."/htdocs";
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
                chmod($projdir, 02775);
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
        return true;
    }


    /**
     * Create project home directory
     * If the directory already exists, nothing is done.
     * @return true if directory is successfully created, false otherwise
     */
    function createProjectCVS($group_id) {
        $project=$this->_getProjectManager()->getProject($group_id);
        if (!$project) return false;

        $unix_group_name=$project->getUnixName(false); // May contain upper-case letters
        $cvs_dir=$GLOBALS['cvs_prefix']."/".$unix_group_name;
        if (!is_dir($cvs_dir)) {
            // Let's create a CVS repository for this group
            if (!mkdir($cvs_dir)) {
                $this->log("Can't create project CVS dir: $cvs_dir");
                return false;
            }

            if ($this->useCVSNT()) {
                // Tell cvsnt not to update /etc/cvsnt/PServer: this is done later by this the script.
                system($GLOBALS['cvs_cmd']." -d$cvs_dir init -n");
            } else {
                system($GLOBALS['cvs_cmd']." -d$cvs_dir init");
            }

            // Turn off pserver writers, on anonymous readers
            // See CVS writers update below. Just create an
            // empty writers file so that we can set up the appropriate
            // ownership right below. We will put names in writers
            // later in the script
            system("echo \"\" > $cvs_dir/CVSROOT/writers");
           
            if (!$this->useCVSNT()) {
                // But to allow checkout/update to registered users we
                // need to setup a world writable directory for CVS lock files
                $lockdir=$GLOBALS['cvslock_prefix']."/$unix_group_name";
                $filename= "$cvs_dir/CVSROOT/config";
                system("echo  >> $filename");
                system("echo '# !!! CodeX Specific !!! DO NOT REMOVE' >> $filename");
                system("echo '# Put all CVS lock files in a single directory world writable' >> $filename");
                system("echo '# directory so that any CodeX registered user can checkout/update' >> $filename");
                system("echo '# without having write permission on the entire cvs tree.' >> $filename");
                system("echo 'LockDir=$lockdir' >> $filename");
                // commit changes to config file (directly with RCS)
                $this->_RcsCommit($filename);
            }

            // setup loginfo to make group ownership every commit
            // commit changes to config file (directly with RCS)
            $filename= "$cvs_dir/CVSROOT/loginfo";
            if ($this->useCVSNT()) {
                // use DEFAULT because there is an issue with multiple 'ALL' lines with cvsnt.
                system("echo \"DEFAULT chgrp -f -R  $unix_group_name $cvs_dir\" > $filename");
            } else {
                system("echo \"ALL (cat;chgrp -R $unix_group_name $cvs_dir)>/dev/null 2>&1\" > $filename");
            }
            $this->_RcsCommit($filename);

            // put an empty line in in the valid tag cache (means no tag yet)
            // (this file is not under version control so don't check it in)
            system("echo \"\" > $cvs_dir/CVSROOT/val-tags");
            chmod("$cvs_dir/CVSROOT/val-tags",0664);

            // set group ownership, http user
            $this->recurseChownChgrp($cvs_dir,$GLOBALS['sys_http_user'],$unix_group_name);
            system("chmod g+rw $cvs_dir");
        }

        // Lockdir does not exist? (Re)create it.
        if (!$this->useCVSNT()) {
            $lockdir=$GLOBALS['cvslock_prefix']."/$unix_group_name";
            if (! is_dir($lockdir)) {
                if (!mkdir("$lockdir",0777)) {
                    $this->log("Can't create project CVS lock dir: $lockdir");
                    return false;
                }
                chmod("$lockdir",0777); // overwrite umask value
            }
        }

        // history was deleted (or not created)? Recreate it.
        if ($this->useCVSNT()) {
            // Create history file (not created by default by cvsnt)
            system("touch $cvs_dir/CVSROOT/history");
            // Must be writable
            chmod("$cvs_dir/CVSROOT/history",0666);
            $this->recurseChownChgrp($cvs_dir."/CVSROOT",$GLOBALS['sys_http_user'],$unix_group_name);
        }

/* NG: still TODO
 	    # LJ if the CVS repo has just been created or the user list
	    # in the group has been modified then update the CVS
	    # writer file

	    if ($group_modified) {
		# On CodeX writers go through pserver as well so put
		# group members in writers file. Do not write anything
		# in the CVS passwd file. The pserver protocol will fallback
		# on /etc/passwd for user authentication
		my $cvswriters_file = "$cvs_dir/CVSROOT/writers";
		open(WRITERS,"+>$cvswriters_file")
		    or croak "Can't open CVS writers file $cvswriters_file: $!";  
		print WRITERS join("\n",split(",",$userlist)),"\n";
		close(WRITERS);
	    }
*/
        if ($project->isCVSTracked()) {
            // hook for commit tracking in cvs loginfo file
            $filename = "$cvs_dir/CVSROOT/loginfo";
            $file_array=file($filename);
            if (!in_array($this->block_marker_start,$file_array)) {
                if ($this->useCVSNT()) {
                        $command = "ALL ".$GLOBALS['codex_bin_prefix']."/log_accum -T $unix_group_name -C $unix_group_name -s %{sVv}";
                } else {
                        $command = "ALL (".$GLOBALS['codex_bin_prefix']."/log_accum -T $unix_group_name -C $unix_group_name -s %{sVv})>/dev/null 2>&1";
                }
                $this->_addBlock($filename,$command);
                $this->_RcsCommit($filename);
                $this->recurseChownChgrp($cvs_dir."/CVSROOT",$GLOBALS['sys_http_user'],$unix_group_name);
            }

            
            // hook for commit tracking in cvs commitinfo file
            $filename = "$cvs_dir/CVSROOT/commitinfo";
            $file_array=file($filename);
            if (!in_array($this->block_marker_start,$file_array)) {
                $this->_addBlock($filename,"ALL ".$GLOBALS['codex_bin_prefix']."/commit_prep -T $unix_group_name -r");
                $this->_RcsCommit($filename);
                $this->recurseChownChgrp($cvs_dir."/CVSROOT",$GLOBALS['sys_http_user'],$unix_group_name);
            }
        }

        // Add notify command if cvs_watch_mode is on
        if ($project->getCVSWatchMode()){
            $filename = "$cvs_dir/CVSROOT/notify";
            $file_array=file($filename);
            if (!in_array($this->block_marker_start,$file_array)) {
                $this->_addBlock($filename,'ALL mail %s -s "CVS notification"');
                $this->_RcsCommit($filename);

                // Apply cvs watch on only if cvs_watch_mode changed to on 
                $this->CVSWatch($cvs_dir,$unix_group_name,1);
                $this->recurseChownChgrp($cvs_dir,$GLOBALS['sys_http_user'],$unix_group_name);
                system("chmod g+rw $cvs_dir");
            }
        }
      
        // Remove notify command if cvs_watch_mode is off.
        if (! $project->getCVSWatchMode()) {
            $filename = "$cvs_dir/CVSROOT/notify";
            $file_array=file($filename);
            if (in_array($this->block_marker_start,$file_array)) {
                // Switch to cvs watch off
                $this->_removeBlock($filename);
                $this->_RcsCommit($filename);
                $this->recurseChownChgrp($cvs_dir."/CVSROOT",$GLOBALS['sys_http_user'],$unix_group_name);
                $this->CVSWatch($cvs_dir,$unix_group_name,0);
            }
        }

        return true;
    }

    function _CVSWatch($cvs_dir, $unix_group_name, $watch_mode) {
        $sandbox_dir =  $GLOBALS['tmp_dir']."/".$unix_group_name.".cvs_watch_sandbox";
        if (is_dir($sandbox_dir)) {
            return false;
        } else {
            mkdir("$sandbox_dir",0700);
            chmod("$sandbox_dir",0700); // overwrite umask value
        }
        if ($watch_mode == 1) {
            system("cd $sandbox_dir;cvs -d$cvs_dir co . 2>/dev/null 1>&2;cvs -d$cvs_dir watch on 2>/dev/null 1>&2;");
        } else {
            system("cd $sandbox_dir;cvs -d$cvs_dir co . 2>/dev/null 1>&2;cvs -d$cvs_dir watch off 2>/dev/null 1>&2;");
        }
        system("rm -rf $sandbox_dir;");
        return true;
    }

        
    function _addBlock($filename,$command) {
        system("echo \"".$this->block_marker_start."\" >> $filename");
        system("echo ".escapeshellarg($command)." >> $filename");	 
        system("echo \"".$this->block_marker_end."\" >> $filename");
    }

    function _removeBlock($filename) {
        $file_array=file($filename);
        $new_file_array=array();
        $inblock=false;
        while($line=array_shift($file_array)) {
            if (strcmp($line,$this->block_marker_start) == 0) { $inblock=true; }
            if (! $inblock) {
                array_push($new_file_array,$line);
            }
            if (strcmp($line,$this->block_marker_end) == 0) { $inblock=false; }
        }
        return $this->_writeArrayToFile($new_file_array,$filename);
    }

    function _writeArrayToFile($file_array, $filename) {

        if (!$handle = fopen($filename, 'w')) {
            $this->log("Can't open file for writing: $filename");
           return false;
        }
        foreach($file_array as $line ) {
            if (fwrite($handle, $line) === FALSE) {
                $this->log("Can't write to file: $filename");
                return false;
            }
        }
        fclose($handle);

    }


    function _RcsCommit($file) {
        system("/usr/bin/rcs -q -l $file; ci -q -m\"Codendi modification\" $file; co -q $file");
    }


    /**
     * Archive the user home directory
     * @return true if directory is successfully archived, false otherwise
     */
    function archiveUserHome($user_id) {
        $user=$this->_getUserManager()->getUserById($user_id);
        if (!$user) return false;
        $homedir=$GLOBALS['homedir_prefix']."/".$user->getUserName();
        $backupfile=$GLOBALS['tmp_dir']."/".$user->getUserName().".tgz";

        if (is_dir($homedir)) {
            system("cd ".$GLOBALS['homedir_prefix']."; tar cfz $backupfile ".$user->getUserName());
            chmod($backupfile,0600);
            Backend::recurseDeleteInDir($homedir);
            rmdir($homedir);
            return true;
       } else return false;
    }


    /**
     * Archive the project directory
     * @return true if directory is successfully archived, false otherwise
     */
    function archiveProjectHome($group_id) {
        $project=$this->_getProjectManager()->getProject($group_id);
        if (!$project) return false;
        $mydir=$GLOBALS['grpdir_prefix']."/".$project->getUnixName(false);
        $backupfile=$GLOBALS['tmp_dir']."/".$project->getUnixName(false).".tgz";

        if (is_dir($mydir)) {
            system("cd ".$GLOBALS['grpdir_prefix']."; tar cfz $backupfile ".$project->getUnixName(false));
            chmod($backupfile,0600);
            Backend::recurseDeleteInDir($mydir);
            rmdir($mydir);


            // Remove lower-case symlink if it exists
            if ($project->getUnixName(true) != $project->getUnixName(false)) {
                if (is_link($GLOBALS['grpdir_prefix']."/".$project->getUnixName(true))) {
                    unlink($GLOBALS['grpdir_prefix']."/".$project->getUnixName(true));
                }
            }
            return true;
       } else return false;
     }

     function archiveProjectCVS($group_id) {
        $project=$this->_getProjectManager()->getProject($group_id);
        if (!$project) return false;
        $mydir=$GLOBALS['cvs_prefix']."/".$project->getUnixName(false);
        $backupfile=$GLOBALS['tmp_dir']."/".$project->getUnixName(false)."-cvs.tgz";

        if (is_dir($mydir)) {
            system("cd ".$GLOBALS['cvs_prefix']."; tar cfz $backupfile ".$project->getUnixName(false));
            chmod($backupfile,0600);
            Backend::recurseDeleteInDir($mydir);
            rmdir($mydir);
            return true;
       } else return false;
     }

     function archiveProjectSVN($group_id) {
        $project=$this->_getProjectManager()->getProject($group_id);
        if (!$project) return false;
        $mydir=$GLOBALS['svn_prefix']."/".$project->getUnixName(false);
        $backupfile=$GLOBALS['tmp_dir']."/".$project->getUnixName(false)."-svn.tgz";

        if (is_dir($mydir)) {
            system("cd ".$GLOBALS['svn_prefix']."; tar cfz $backupfile ".$project->getUnixName(false));
            chmod($backupfile,0600);
            Backend::recurseDeleteInDir($mydir);
            rmdir($mydir);
            return true;
       } else return false;
     }

    function _getAliases() {
        if (!$this->aliases) {
            $this->aliases = new MailAliases();
        }
        return  $this->aliases;
    }


    function setNeedUpdateMailAliases() {
        $this->_getAliases()->setNeedUpdate();
    }

    function aliasesNeedUpdate() {
        return  $this->_getAliases()->needUpdate();
    }


    function aliasesUpdate() {
        return  $this->_getAliases()->update();
    }

    function setNeedUpdateCVSRootList() {
        $this->CVSRootListNeedUpdate=true;
    }

    function CVSRootListneedUpdate() {
        return $this->CVSRootListNeedUpdate;
    }

    function CVSRootListUpdate() {
        // TODO
    }

}

?>
