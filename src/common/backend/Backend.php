<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Backend\FileExtensionFilterIterator;

/**
 * Base class to work on Codendi backend
 * Change file perms, write Codendi blocks, ...
 */
class Backend
{
    public const LOG_INFO    = \Psr\Log\LogLevel::INFO;
    public const LOG_WARNING = \Psr\Log\LogLevel::WARNING;
    public const LOG_ERROR   = \Psr\Log\LogLevel::ERROR;
    public const LOG_DEBUG   = \Psr\Log\LogLevel::DEBUG;

    public const SVN     = 'SVN';
    public const BACKEND = 'Backend';
    public const SYSTEM  = 'System';
    public const ALIASES = 'Aliases';

    public $block_marker_start = "# !!! Codendi Specific !!! DO NOT REMOVE (NEEDED CODENDI MARKER)\n";
    public $block_marker_end   = "# END OF NEEDED CODENDI BLOCK\n";

    // Name of apache user (codendiadm).
    protected $httpUser;
    // UID of apache user (codendiadm).
    protected $httpUserUID;
    // GID of apache user
    protected $httpUserGID;

    /**
     * Constructor
     */
    protected function __construct()
    {
        /* Make sure umask is properly positioned for the
         entire session. Root has umask 022 by default
         causing all the mkdir xxx, 775 to actually
         create dir with permission 755 !!
         So set umask to 002 for the entire script session
        */
        // Problem: "Avoid using this function in multithreaded webservers" http://us2.php.net/manual/en/function.umask.php
        //umask(002);
    }

    private static $backend_instances = [];
    /**
     * Return a Backend instance
     *
     * Let plugins propose their own backend. If none provided, use the default one.
     *
     * @param string $type  SVN | System | Backend | Aliases | plugin_git | ... Default is 'Backend' (itself)
     * @param string $base  The name of the base backend class. Useless for core backends, mandatory for plugin ones
     * @param array  $setup The parameters to setUp the plugin, if needed. Null if no parameters
     *
     * @throw Exception if $setup is filled and the backend doesn't have a setUp() method
     *
     * @return Backend
     */
    public static function instance($type = self::BACKEND, $base = null, $setup = null)
    {
        if (! isset(self::$backend_instances[$type])) {
            $backend = null;

            //determine the base class of the plugin if it is not define at the call
            if (! $base) {
                $base = 'Backend';
                if ($type !== self::BACKEND) {
                    //BackendSVN, BackendSystem, ...
                    $base .= $type;
                }
            }
            $wanted_base = $base;

            //Ask to the whole world if someone wants to provide its own backend
            //for example plugin ldap will override BackendSVN
            $params = [
                'base'  => &$base,
                'setup' => &$setup,
            ];
            $event  = Event::BACKEND_FACTORY_GET_PREFIX . strtolower($type);
            EventManager::instance()->processEvent($event, $params);

            //make sure that there is no problem between the keyboard and the chair
            if (! class_exists($base)) {
                throw new RuntimeException("Class '$base' not found");
            }
            //Create a new instance of the wanted backend
            $backend = new $base();

            //check that all is ok
            if (! $backend instanceof $wanted_base) {
                throw new Exception('Backend should inherit from ' . $wanted_base . '. Received: "' . $backend::class . '"');
            }

            assert($backend instanceof Backend);

            //SetUp if needed
            if (is_array($setup)) {
                if (method_exists($backend, 'setUp')) {
                    call_user_func_array([$backend, 'setUp'], $setup);
                } else {
                    throw new Exception($base . ' does not have setUp.');
                }
            }

            //cache the instance to save ticks
            self::$backend_instances[$type] = $backend;
        }
        return self::$backend_instances[$type];
    }

    /**
     * Helper for static analysis
     */
    final public static function instanceSVN(): BackendSVN
    {
        $instance = self::instance(self::SVN);
        assert($instance instanceof BackendSVN);
        return $instance;
    }

    /**
     * Clear the cache of instances.
     * Main goal is for unit tests. Useless in prod.
     *
     * @return void
     */
    public static function clearInstances()
    {
        self::$backend_instances = [];
    }

    public static function setInstance($type, $instance)
    {
        self::$backend_instances[$type] = $instance;
    }

    /**
     * Get an instance of UserManager. Mainly used for mock
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * Get an instance of UserManager. Mainly used for mock
     *
     * @return ProjectManager
     */
    protected function getProjectManager()
    {
        return ProjectManager::instance();
    }

    protected function getUnixGroupNameForProject(Project $project)
    {
        return $this->getHTTPUser();
    }

    /**
     * Create chown function to allow mocking in unit tests
     * Attempts to change the owner of the file filename  to user user .
     * Only the superuser may change the owner of a file.
     *
     * @param string $path Path to the file.
     * @param mixed  $uid  A user name or number.
     *
     * @return bool true on success or false on failure
     */
    public function chown($path, $uid)
    {
        if (is_link($path)) {
            return lchown($path, $uid);
        }

        return chown($path, $uid);
    }

    /**
     * Set file's owner, group and mode
     *
     * @param String  $file
     * @param String  $user
     * @param String  $group
     * @param int $mode
     *
     * @return void
     */
    public function changeOwnerGroupMode($file, $user, $group, $mode)
    {
        $this->chown($file, $user);
        $this->chgrp($file, $group);
        $this->chmod($file, $mode);
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
     * @return bool true on success or false on failure
     */
    public function chgrp($path, $uid)
    {
        if (is_link($path)) {
            return lchgrp($path, $uid);
        }

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
     * @return bool true on success or false on failure
     */
    public function chmod($file, $mode)
    {
        return chmod($file, $mode);
    }

    /**
     * Create system function to allow mocking in unit tests
     *
     * @param string $cmd The command that will be executed
     * @param int $rval command return value
     * @return mixed Returns the last line of the command output on success, and false
     * on failure.
     *
     * @psalm-taint-specialize
     */
    protected function system($cmd, &$rval = 0)
    {
        return system($cmd, $rval);
    }

    /**
     * Log message in codendi_syslog
     *
     * @param string $message The error message that should be logged.
     * @param string $level   The level of the message "info", "warn", ...
     */
    public function log($message, $level = 'info')
    {
        $logger = BackendLogger::getDefaultLogger();
        $logger->log($level, $message);
    }

    /**
     * Return username running the Apache server
     *
     * @return string unix user name
     */
    public function getHTTPUser()
    {
        if (! $this->httpUser) {
            $this->httpUser = ForgeConfig::getApplicationUserLogin();
        }
        return $this->httpUser;
    }

    /**
     * Return user ID running the Apache server
     *
     * @return int unix user ID
     */
    public function getHTTPUserUID()
    {
        $this->initHTTPUserInfo();
        return $this->httpUserUID;
    }

    protected function getHTTPUserGID()
    {
        $this->initHTTPUserInfo();
        return $this->httpUserGID;
    }

    private function initHTTPUserInfo()
    {
        if ($this->httpUserUID === null) {
            $userinfo          = posix_getpwnam($this->getHTTPUser());
            $this->httpUserUID = $userinfo['uid'];
            $this->httpUserGID = $userinfo['gid'];
        }
    }

    /**
     * Recursive chown/chgrp function.
     *
     * @param string $mypath Path to the file (or directory)
     * @param mixed  $uid    A user name or number.
     * @param mixed  $gid    A group name or number.
     */
    public function recurseChownChgrp($mypath, $uid, $gid, array $file_extension_filter)
    {
        if (! file_exists($mypath)) {
            return;
        }
        $this->chown($mypath, $uid);
        $this->chgrp($mypath, $gid);
        try {
            $iterator = $this->getRecurseDirectoryIterator($mypath, $file_extension_filter);
            foreach ($iterator as $filename => $file_information) {
                $this->chown($file_information->getPathname(), $uid);
                $this->chgrp($file_information->getPathname(), $gid);
            }
        } catch (Exception $ex) {
            $this->log($ex->getMessage() . 'in ' . $ex->getFile() . ':' . $ex->getLine(), self::LOG_DEBUG);
        }
    }

    /**
     * Recursive chgrp (only) function.
     *
     * @param string $mypath Path to the file (or directory)
     * @param mixed  $gid    A group name (or number??).
     */
    public function recurseChgrp($mypath, $gid, array $file_extension_filter)
    {
        $this->chgrp($mypath, $gid);
        try {
            $iterator = $this->getRecurseDirectoryIterator($mypath, $file_extension_filter);
            foreach ($iterator as $filename => $file_information) {
                $this->chgrp($file_information->getPathname(), $gid);
            }
        } catch (Exception $ex) {
            $this->log($ex->getMessage() . 'in ' . $ex->getFile() . ':' . $ex->getLine(), self::LOG_DEBUG);
        }
    }

    /**
     * Note: the function will empty everything in the given directory but won't remove the directory itself
     *
     * @param string $mypath Path to the directory
     */
    public function recurseDeleteInDir($mypath)
    {
        try {
            $no_filter_file_extension = [];
            $iterator                 = $this->getRecurseDirectoryIterator($mypath, $no_filter_file_extension);
            foreach ($iterator as $filename => $file_information) {
                if ($file_information->isDir()) {
                    rmdir($file_information->getPathname());
                } else {
                    unlink($file_information->getPathname());
                }
            }
        } catch (Exception $ex) {
            $this->log($ex->getMessage() . 'in ' . $ex->getFile() . ':' . $ex->getLine(), self::LOG_DEBUG);
        }
    }

    /**
     * @return RecursiveIteratorIterator
     */
    private function getRecurseDirectoryIterator($path, array $file_extension_filter)
    {
        return new \RecursiveIteratorIterator(
            new FileExtensionFilterIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \FilesystemIterator::SKIP_DOTS
                ),
                $file_extension_filter
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * Add Codendi block in a file
     *
     * @param string $filename Path to the file
     * @param string $command  content of the block
     *
     * @return bool true on success or false on failure.
     */
    public function addBlock($filename, $command)
    {
        if (! $handle = @fopen($filename, 'a')) {
            $this->log("Can't open file for writing: $filename", self::LOG_ERROR);
            return false;
        }
        fwrite($handle, $this->block_marker_start);
        fwrite($handle, $command . "\n");
        fwrite($handle, $this->block_marker_end);
        return fclose($handle);
    }

    /**
     * Remove Codendi block in a file
     *
     * @param string $filename Path to the file
     *
     * @return bool true on success or false on failure.
     */
    public function removeBlock($filename)
    {
        $file_array     = file($filename);
        $new_file_array = [];
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
     * @return bool true on success or false on failure.
     */
    public function writeArrayToFile($file_array, $filename)
    {
        if (! $handle = @fopen($filename, 'w')) {
            $this->log("Can't open file for writing: $filename", self::LOG_ERROR);
            return false;
        }

        foreach ($file_array as $line) {
            if (fwrite($handle, $line) === false) {
                $this->log("Can't write to file: $filename", self::LOG_ERROR);
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
     * Won't move file_new if it is empty.
     *
     * @param string $file_new Path to the new file.
     * @param string $file     Path to the current file.
     * @param string $file_old Path to the old file.
     * @param string $force    Force install even if the files are the same or file is empty. Default is false.
     *
     * @return bool true on success or false on failure.
     */
    public function installNewFileVersion($file_new, $file, $file_old, $force = false)
    {
        // Backup existing file and install new one if they are different
        if (is_file($file)) {
            if (! $force) {
                // Read file contents
                $current_string = serialize(file($file));
                $new_array      = file($file_new);
                if (empty($new_array)) {
                    // Do not replace existing file with empty file
                    // might be due to disk full
                    $this->log("Won't install empty file $file_new", self::LOG_WARNING);
                    return false;
                }
                $new_string = serialize($new_array);
            }
            if ($force || ($current_string !== $new_string)) {
                if (is_file($file_old)) {
                    unlink($file_old);
                }

                if (! rename($file, $file_old)) {
                    $this->log("Can't move file $file to $file_old", self::LOG_ERROR);
                    return false;
                }
                if (! rename($file_new, $file)) {
                    $this->log("Can't move file $file_new to $file", self::LOG_ERROR);
                    return false;
                }
            } // Else do nothing: the configuration has not changed
        } else {
            // No existing file
            if (! rename($file_new, $file)) {
                $this->log("Can't move file $file_new to $file (no existing file)", self::LOG_ERROR);
                return false;
            }
        }
        return true;
    }

    /**
     * Modifiy the acl for the specified file.
     *
     * @param String $entries
     * @param String $path
     */
    public function modifyacl($entries, $path)
    {
        $path = escapeshellarg($path);
        $this->setfacl("-m $entries $path");
    }

    /**
     * Remove all acl and default acl for specified path.
     *
     * @param String $path
     */
    public function resetacl($path)
    {
        $path = escapeshellarg($path);
        $this->setfacl("--remove-all --remove-default $path");
    }

    public function setfacl($command)
    {
        $this->exec("setfacl $command");
    }

    private function exec($command)
    {
        $output       = [];
        $return_value = 1;
        exec("$command 2>&1", $output, $return_value);
        if ($return_value == 0) {
            return $output;
        } else {
            throw new BackendCommandException($command, $output, $return_value);
        }
    }

    /**
     * Check if given path is a repository or a file or a link
     *
     * @param String $path
     *
     * @return bool true if repository or file  or link already exists, false otherwise
     */
    public static function fileExists($path)
    {
        return (is_dir($path) || is_file($path) || is_link($path));
    }
}
