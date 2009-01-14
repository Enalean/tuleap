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

    /**
     * Constructor
     */
    function Backend() {

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
     * Backend is a singleton
     */
    function &instance() {
        static $_backend_instance;
        if (!$_backend_instance) {
            $_backend_instance = new Backend();
        }
        return $_backend_instance;
    }


     function _getUserManager() {
        return UserManager::instance();
    }

   /**
     * Recursive chown/chgrp function.
     * From comment at http://us2.php.net/manual/en/function.chown.php#40159
     */
    static function recurseChownChgrp($mypath, $uid, $gid) {
        chown($mypath, $uid);
        chgrp($mypath, $gid);
        $d = opendir($mypath);
        while(($file = readdir($d)) !== false) {
            if ($file != "." && $file != "..") {
                
                $typepath = $mypath . "/" . $file ;

                //print $typepath. " : " . filetype ($typepath). "\n" ;
                if (filetype ($typepath) == 'dir') {
                    Backend::recurseChownChgrp ($typepath, $uid, $gid);
                } else {
                    chown($typepath, $uid);
                    chgrp($typepath, $gid);
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
    static function recurseDeleteInDir($mypath) {
        $mypath= rtrim($mypath, '/');
        $d = opendir($mypath);
        while(($file = readdir($d)) !== false) {
            if ($file != "." && $file != "..") {
                
                $typepath = $mypath . "/" . $file ;

                if( is_dir($typepath) ) {
                    Backend::recurseDeleteInDir($typepath);
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
                Backend::recurseChownChgrp($homedir,$user->getUserName(),$user->getUserName());

                return true;
            }
        }
        return false;
    }


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

}

?>
