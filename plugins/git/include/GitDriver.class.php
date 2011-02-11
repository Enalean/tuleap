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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Codendi. If not, see <http://www.gnu.org/licenses/
  */

/**
 * Description of GitDriverclass
 * @TODO Create Class exception to thro GIT messages
 * @TODO Make this driver compliant with Apache ??
 * @TODO Make sure directories tree to manage forks and repo is a good choice
 * @author gstorchi
 */
 $DIR = dirname(__FILE__);
 require_once('GitBackend.class.php');
 require_once($DIR.'/../DVCS/DVCSDriver.class.php');
 require_once('exceptions/GitDriverException.class.php');
 require_once('exceptions/GitDriverErrorException.class.php');
 require_once('exceptions/GitDriverSourceNotFoundException.class.php');
 require_once('exceptions/GitDriverDestinationNotEmptyException.class.php');

class GitDriver implements DVCSDriver {

    public function __construct() {       
    }

    /**
     * Make a clone of a source repository
     * @param <String> $source      source directory
     * @param <String> $destination destination directory
     * @return <boolean>
     */
    public function fork($source, $destination) {
        if ( !file_exists($source) ) {
            throw new GitDriverSourceNotFoundException($source);
        }

        //WARNING : never use --shared/--reference options
        $cmd = 'git clone --bare --local --no-hardlinks '.escapeshellarg($source).' '.escapeshellarg($destination);
        $out = array();
        $ret = -1;
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            throw new GitDriverErrorException('Git fork failed on '.$cmd.PHP_EOL.implode(PHP_EOL, $out));
        }

        chdir($destination);
        $cmd = 'git-update-server-info';
        $out = array();
        $ret = -1;
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            throw new GitDriverErrorException('Git fork failed on '.$cmd.PHP_EOL.implode(PHP_EOL, $out));
        }

        if (!$this->setDescription(getcwd(), 'Default description for this project'.PHP_EOL)) {
            throw new GitDriverErrorException('Git init failed on description update');
        }

        $this->setPermissions($destination);
        return true;
    }

    /**
     * Initialize a repository    
     * @param Boolean $bare is a bare a repository
     * @return Boolean
     */
    public function init($bare=false) {
        if ( $bare === false ) {
            $cmd = 'git init';
            $out = array();
            $ret = -1;
            exec($cmd, $out, $ret);
            if ($ret !== 0) {
                throw new GitDriverErrorException('Git init failed on '.$cmd.PHP_EOL.implode(PHP_EOL, $out));
            }
            return true;
        }

        $cmd = 'git --bare init --shared=group';
        $out = array();
        $ret = -1;
        exec($cmd, $out, $ret);
        if ( $ret !== 0 ) {
            throw new GitDriverErrorException('Git init failed on '.$cmd.PHP_EOL.implode(PHP_EOL, $out));
        }

        $cmd = 'git-update-server-info';
        $out = array();
        $ret = -1;
        exec($cmd, $out, $ret);
        if ( $ret !== 0 ) {
            throw new GitDriverErrorException('Git init failed on '.$cmd.PHP_EOL.implode(PHP_EOL, $out));
        }
        
        if (!$this->setDescription(getcwd(), 'Default description for this project'.PHP_EOL)) {
            throw new GitDriverErrorException('Git init failed on description update');
        }

        $this->setPermissions( getcwd() );

        return true;
    }

    public function delete($path) {
        if ( empty($path) || !is_writable($path) ) {
           throw new GitDriverErrorException('Empty path or permission denied '.$path);
        }
        $rcode = 0;
        $output = system('rm -fr '.$path, $rcode);
        if ( $rcode != 0 ) {
           throw new GitDriverErrorException('Unable to delete path '.$path);
        }
        return true;
    }    

    public function activateHook($hookName, $repoPath, $uid=false, $gid=false) {
        //newer version of git
        $hook = $repoPath.'/hooks/'.$hookName;
        if ( file_exists($hook.'.sample') ) {
            //old git versions do not need this move
            rename($hook.'.sample', $hook);
        }

        //older versions only requires +x for hook activation
        if (!chmod($hook, 0755)) {
            throw new GitDriverErrorException('Unable to make '.$hook.' executable');
        }

        if ($uid !== false) {
            if (!chown($hook, $uid)) {
                 throw new GitDriverErrorException('Unable to change '.$hook.' owner to '.$uid);
            }
        }
        if ($gid !== false) {
            if (!chgrp($hook, $gid)) {
                 throw new GitDriverErrorException('Unable to change '.$hook.' group to '.$gid);
            }
        }
        return true;
    }

    public function masterExists($repoPath) {
        if ( file_exists($repoPath.'/refs/heads/master') ) {
            return true;
        }
        return false;
    }

    //TODO control access types
    public function setRepositoryAccess($repoPath, $access) {
        
        if ( $access == GitRepository::PUBLIC_ACCESS ) {
            $cmd      = ' find '.$repoPath.' -type d ! -path "*hooks*" | xargs chmod o+rx ';
        } else {
            $cmd      = ' find '.$repoPath.' -type d ! -path "*hooks*" | xargs chmod o-rwx ';
        }
        $rcode    = 0;
        $output   = system( $cmd, $rcode );
        if ( $rcode != 0 ) {
            throw new GitBackendException($cmd.' -> '.$output);
        }
        if ( $access == GitRepository::PUBLIC_ACCESS ) {
            $cmd     = ' find '.$repoPath.' -type f ! -path "*hooks*" | xargs chmod o+r ';
        } else {
            $cmd     = ' find '.$repoPath.' -type f ! -path "*hooks*" | xargs chmod o-rwx ';
        }
        $rcode   = 0;
        $output   = system( $cmd, $rcode );
        if ( $rcode != 0 ) {
            throw new GitBackendException($cmd.' -> '.$output);
        }
        return true;

    }

    public function setDescription($repoPath, $description) {
        if( ! file_put_contents($repoPath.'/description', $description) ) {
            throw new GitDriverErrorException('Unable to set description');
        }
        return true;
    }

    public function getDescription($repoPath) {
        return file_get_contents($repoPath.'/description');
    }

    public function setConfig($repoPath, $key, $value) {
        $configFile = $repoPath.'/config';
        $cmd = 'git config --file '.$configFile.' --replace-all '.escapeshellarg($key).' '.escapeshellarg($value).' 2>&1';
        $ret = -1;
        $out = array();
        exec($cmd, $out, $ret);
        if ($ret !== 0) {
            throw new GitDriverErrorException('Unable to set config for repository '.$repoPath.':'.PHP_EOL.implode(PHP_EOL, $out));
        }
    }

    //TODO check path 
    protected function setPermissions($path) {
        $rcode  = 0;
        $cmd    = 'find '.$path.' -type d | xargs chmod u+rwx,g+rwxs,o-rwx '.$path;
        $output = system($cmd, $rcode);
        if ( $rcode != 0 ) {
            throw new GitDriverErrorException($cmd.' -> '.$output);
        }
        $rcode  = 0;
        $cmd    = 'chmod g+w '.$path.'/HEAD';
        $output = system($cmd, $rcode);
        if ( $rcode != 0 ) {
            throw new GitDriverErrorException($cmd.' -> '.$output);
        }
        return true;
    }
}

?>
