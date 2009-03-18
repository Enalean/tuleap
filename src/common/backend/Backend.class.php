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


    function _getAliases() {
        if (!$this->aliases) {
            $this->aliases = new MailAliases();
        }
        return  $this->aliases;
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

    function addBlock($filename,$command) {
        
        if (!$handle = fopen($filename, 'a')) {
            $this->log("Can't open file for writing: $filename");
            return false;
        }
        fwrite($handle,$this->block_marker_start."\n");
        fwrite($handle,$command."\n");
        fwrite($handle,$this->block_marker_end."\n");
        return fclose($handle);
    }

    function removeBlock($filename) {
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
        return $this->writeArrayToFile($new_file_array,$filename);
    }


    /**
     * Write an array to a file
     * WARNING: the function does not add newlines at the end of each row
     */
    function writeArrayToFile($file_array, $filename) {

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

    /**
     *  Install new version of file
     *
     * Precisely: move 'file_new' to 'file' if they are different or if 'file' does not exist.
     * Also, move 'file' to 'file_old' and remove previous 'file_old'
     */
    function installNewFileVersion($file_new,$file,$file_old) {
        // Backup existing file and install new one if they are different
        if (is_file($file)) {
            $current_string=serialize(file($file));
            $new_string=serialize(file($file_new));
            if ($current_string!==$new_string) {
                    if (is_file($file_old)) {
                        unlink($file_old);
                    }

                    if (!rename($file,$file_old)) {
                        $this->log("Can't move file $file to $file_old");
                        return false;
                    }
                    if (!rename($file_new,$file)) {
                        $this->log("Can't move file $file_new to $file");
                        return false;
                    }
            } // Else do nothing: the configuration has not changed
        } else { 
            // No existing file
            if (!rename($file_new,$file)) {
                $this->log("Can't move file $file_new to $file (no existing file)");
                return false;
            }
        }
        return true;
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
