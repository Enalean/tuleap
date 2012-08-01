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
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    public function mv($from, $to) {
        $cmd = 'git mv '.escapeshellarg($from) .' '. escapeshellarg($to);
        return $this->gitCmd($cmd);
    }

    public function add($file) {
        $cmd = 'git add '.escapeshellarg($file);
        return $this->gitCmd($cmd);
    }

    public function rm($file) {
        $cmd = 'git rm '.escapeshellarg($file);
        return $this->gitCmd($cmd);
    }

    /**
     * Commit stuff to repository
     * 
     * Always force commit, even when there no changes it's mandatory with
     * dump ssh keys event, otherwise the commit is empty and it raises errors.
     * TODO: find a better way to manage that!
     *
     * @param String $message
     */
    public function commit($message) {
        $cmd = 'git commit --allow-empty -m '.escapeshellarg($message);
        return $this->gitCmd($cmd);
    }
    
    public function push() {
        $cmd = 'git push origin master';
        return $this->gitCmd($cmd);
    }
    
    private function gitCmd($cmd) {
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
