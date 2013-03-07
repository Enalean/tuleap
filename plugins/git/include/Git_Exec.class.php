<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once 'exceptions/Git_Command_Exception.class.php';

/**
 * Wrap access to git commands
 */
class Git_Exec {
    private $path;

    /**
     * @param String $path The git repository path where we should operate
     */
    public function __construct($path) {
        $this->path = $path;
    }

    /**
     * git help mv
     *
     * @param string $from
     * @param string $to
     *
     * @return boolean
     * @throw Git_Command_Exception
     */
    public function mv($from, $to) {
        $cmd = 'git mv '.escapeshellarg($from) .' '. escapeshellarg($to);
        return $this->gitCmd($cmd);
    }

    /**
     * git help add
     *
     * @param string $file
     *
     * @return boolean
     * @throw Git_Command_Exception
     */
    public function add($file) {
        $cmd = 'git add '.escapeshellarg($file);
        return $this->gitCmd($cmd);
    }

    /**
     * git help rm
     *
     * @param string $file
     *
     * @return boolean
     * @throw Git_Command_Exception
     */
    public function rm($file) {
        if ($this->canRemove($file)) {
            $cmd = 'git rm '.escapeshellarg($file);
            return $this->gitCmd($cmd);
        }
        return true;
    }

    private function canRemove($file) {
        $output = array();
        $this->execInPath('git status --porcelain '.escapeshellarg($file), $output);
        return count($output) == 0;
    }

    /**
     * git help commit
     *
     * Commit only if there is something to commit
     *
     * @param string $message
     *
     * @return boolean
     * @throw Git_Command_Exception
     */
    public function commit($message) {
        if ($this->isThereAnythingToCommit()) {
            $cmd = 'git commit -m '.escapeshellarg($message);
            return $this->gitCmd($cmd);
        }
        return true;
    }

    /**
     * git help push
     *
     * @return boolean
     * @throw Git_Command_Exception
     */
    public function push() {
        $cmd = 'git push origin master';
        return $this->gitCmd($cmd);
    }

    /**
     * Return true if working directory is clean (nothing to commit)
     *
     * @return boolean
     * @throw Git_Command_Exception
     */
    public function isThereAnythingToCommit() {
        $output = array();
        $this->execInPath('git status --porcelain', $output);
        foreach ($output as $status_line) {
            if (preg_match('/^[ADMR]/', $status_line)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @return string The git repository path where we operate
     */
    public function getPath() {
        var_dump($this->path);
        return $this->path;
    }

    protected function gitCmd($cmd) {
        $output = array();
        return $this->execInPath($cmd, $output);
    }

    protected function execInPath($cmd, &$output) {
        $cwd = getcwd();
        chdir($this->path);
        exec("$cmd 2>&1", $output, $retVal);
        chdir($cwd);
        if ($retVal == 0) {
            return true;
        } else {
            throw new Git_Command_Exception($cmd, $output, $retVal);
        }
    }
}

?>
