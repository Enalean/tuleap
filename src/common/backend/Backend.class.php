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

/**
 * Base class to work on Codendi backend
 * Change file perms, write Codendi blocks, ...
 */
class Backend {


    public $block_marker_start = "# !!! CodeX Specific !!! DO NOT REMOVE (NEEDED CODEX MARKER)";
    public $block_marker_end   = "# END OF NEEDED CODEX BLOCK";


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
    protected static $instance;
    
    /**
     * Backends are singletons
     *
     * @return Backend
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    /**
     * Get an instance of UserManager. Mainly used for mock
     * 
     * @return UserManager
     */
    protected function getUserManager() {
        return UserManager::instance();
    }

    /**
     * Get an instance of UserManager. Mainly used for mock
     * 
     * @return ProjectManager
     */
    protected function getProjectManager() {
        return ProjectManager::instance();
    }

    /** 
     * Create chown function to allow mocking in unit tests 
     * Attempts to change the owner of the file filename  to user user . 
     * Only the superuser may change the owner of a file. 
     * 
     * @param string $path Path to the file. 
     * @param mixed  $uid  A user name or number.
     * 
     * @return boolean true on success or false on failure
     */
    protected function chown($path, $uid) {
        return chown($path, $uid);
    }

    /** 
     * Create chgrp function to allow mocking in unit tests 
     * Attempts to change the group of the file filename  to group .
     *
     * Only the superuser may change the group of a file arbitrarily; 
     * other users may change the group of a file to any group of which 
     * that user is a member. 
     * 
     * @param string $path Path to the file. 
     * @param mixed  $uid  A group name or number.
     * 
     * @return boolean true on success or false on failure
     */
    protected function chgrp($path, $uid) {
        return chgrp($path, $uid);
    }

    /** 
     * Create chmod function to allow mocking in unit tests 
     * Attempts to change the mode of the specified $file to that given in $mode . 
     * 
     * @param string $file Path to the file. 
     * @param number $mode The mode parameter consists of three octal number 
     *                     components specifying access restrictions for the 
     *                     owner, the user group in which the owner is in, and 
     *                     to everybody else in this order.
     * 
     * @return boolean true on success or false on failure
     */
    protected function chmod($file, $mode) {
        return chmod($file, $mode);
    }

    /** 
     * Create system function to allow mocking in unit tests 
     *
     * @param string $cmd The command that will be executed
     *
     * @return mixed Returns the last line of the command output on success, and false 
     * on failure.
     */
    protected function system($cmd) {
        return system($cmd);
    }

    /**
     * Log message in codendi_syslog
     *
     * @param string $message The error message that should be logged.
     * 
     * @return boolean true on success or false on failure
     */
    public function log($message) {
        return error_log($message."\n", 3, $GLOBALS['codendi_log']."/codendi_syslog");
    }

    /**
     * Recursive chown/chgrp function.
     * From comment at http://us2.php.net/manual/en/function.chown.php#40159
     * 
     * @param string $mypath Path to the file (or directory)
     * @param mixed  $uid    A user name or number.
     * @param mixed  $gid    A group name or number.
     *
     * @return void
     */
    public function recurseChownChgrp($mypath, $uid, $gid) {
        $this->chown($mypath, $uid);
        $this->chgrp($mypath, $gid);
        $d = opendir($mypath);
        while (($file = readdir($d)) !== false) {
            if ($file != "." && $file != "..") {
                
                $typepath = $mypath . "/" . $file ;

                //print $typepath. " : " . filetype ($typepath). "\n" ;
                if (filetype($typepath) == 'dir') {
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
     * 
     * @param string $mypath Path to the directory
     *
     * @return void
     */
    public function recurseDeleteInDir($mypath) {
        $mypath = rtrim($mypath, '/');
        $d      = opendir($mypath);
        while (($file = readdir($d)) !== false) {
            if ($file != "." && $file != "..") {
                
                $typepath = $mypath . "/" . $file ;

                if ( is_dir($typepath) ) {
                    $this->recurseDeleteInDir($typepath);
                    rmdir($typepath);
                } else {
                    unlink($typepath);
                }
            }
        }
        closedir($d);
    }

    /**
     * Add Codendi block in a file
     * 
     * @param string $filename Path to the file
     * @param string $command  content of the block
     *
     * @return boolean true on success or false on failure.
     */
    public function addBlock($filename, $command) {
        
        if (!$handle = fopen($filename, 'a')) {
            $this->log("Can't open file for writing: $filename");
            return false;
        }
        fwrite($handle, $this->block_marker_start."\n");
        fwrite($handle, $command."\n");
        fwrite($handle, $this->block_marker_end."\n");
        return fclose($handle);
    }

    /**
     * Remove Codendi block in a file
     * 
     * @param string $filename Path to the file
     *
     * @return boolean true on success or false on failure.
     */
    public function removeBlock($filename) {
        $file_array     = file($filename);
        $new_file_array = array();
        $inblock        = false;
        while ($line = array_shift($file_array)) {
            if (strcmp($line, $this->block_marker_start) == 0) { 
                $inblock = true; 
            }
            if (! $inblock) {
                array_push($new_file_array, $line);
            }
            if (strcmp($line, $this->block_marker_end) == 0) { 
                $inblock = false; 
            }
        }
        return $this->writeArrayToFile($new_file_array, $filename);
    }


    /**
     * Write an array to a file
     * WARNING: the function does not add newlines at the end of each row
     * 
     * @param array  $file_array Content to write to file
     * @param string $filename   Path to the file
     *
     * @return boolean true on success or false on failure.
     */
    public function writeArrayToFile($file_array, $filename) {

        if (!$handle = fopen($filename, 'w')) {
            $this->log("Can't open file for writing: $filename");
            return false;
        }
        foreach ($file_array as $line ) {
            if (fwrite($handle, $line) === false) {
                $this->log("Can't write to file: $filename");
                return false;
            }
        }
        return fclose($handle);

    }

    /**
     * Install new version of file
     *
     * Precisely: move 'file_new' to 'file' if they are different or if 'file' does not exist.
     * Also, move 'file' to 'file_old' and remove previous 'file_old'
     * 
     * @param string $file_new Path to the new file.
     * @param string $file     Path to the current file.
     * @param string $file_old Path to the old file.
     * @param string $force    Force install. Default is false.
     *
     * @return boolean true on success or false on failure.
     */
    public function installNewFileVersion($file_new, $file, $file_old, $force=false) {
        // Backup existing file and install new one if they are different
        if (is_file($file)) {
            if (! $force) {
                // Read file contents 
                $current_string = serialize(file($file));
                $new_string     = serialize(file($file_new));
            }
            if ($force || ($current_string !== $new_string)) {
                if (is_file($file_old)) {
                    unlink($file_old);
                }

                if (!rename($file, $file_old)) {
                    $this->log("Can't move file $file to $file_old");
                    return false;
                }
                if (!rename($file_new, $file)) {
                    $this->log("Can't move file $file_new to $file");
                    return false;
                }
            } // Else do nothing: the configuration has not changed
        } else { 
            // No existing file
            if (!rename($file_new, $file)) {
                $this->log("Can't move file $file_new to $file (no existing file)");
                return false;
            }
        }
        return true;
    }

}

?>
