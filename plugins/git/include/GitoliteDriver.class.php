<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 */

require_once 'common/project/Project.class.php';
require_once 'common/user/User.class.php';

class Git_GitoliteDriver {
    protected $adminPath;

    public function __construct($adminPath) {
        $this->adminPath = $adminPath;
        $this->confFilePath = $adminPath.'/conf/gitolite.conf';
    }

    public function init($project, $repoPath) {
        $prjConfDir = $this->adminPath.'/conf/projects';
        if (!is_dir($prjConfDir)) {
            mkdir($prjConfDir);
        }
        $conf = 'repo '.$project->getUnixName().'/'.$repoPath.PHP_EOL;
        $conf .= "\tRW = @".$project->getUnixName().'_project_members'.PHP_EOL;

        $confFile = $prjConfDir.'/'.$project->getUnixName().'.conf';
        if (file_put_contents($confFile, $conf)) {
            if ($this->gitAdd($confFile)) {
                if ($this->updateMainConfIncludes($project)) {
                    return $this->gitCommit('New repo: '.$project->getUnixName().'/'.$repoPath);
                }
            }
        }
        return false;
    }

    public function updateMainConfIncludes($project) {
        if (is_file($this->confFilePath)) {
            $conf = file_get_contents($this->confFilePath);
        } else {
            $conf = '';
        }
        if (strpos($conf, 'include "projects/'.$project->getUnixName().'.conf"') === false) {
            $backend = Backend::instance();
            if ($conf) {
                $backend->removeBlock($this->confFilePath);
            }
            $newConf = '';
            $dir = new DirectoryIterator($this->adminPath.'/conf/projects');
            foreach ($dir as $file) {
                if (!$file->isDot()) {
                    $newConf .= 'include "projects/'.basename($file->getFilename()).'"'.PHP_EOL;
                }
            }
            if ($backend->addBlock($this->confFilePath, $newConf)) {
                return $this->gitAdd($this->confFilePath);
            }
            return false;
        }
        return true;
    }

    /**
     * @param User $user
     */
    public function initUserKeys($user) {
        $keydir = $this->adminPath.'/keydir';
        if (!is_dir($keydir)) {
            mkdir($keydir);
        }
        $keys = explode("\n", $user->getAuthorizedKeys());
        $i    = 0;
        foreach ($keys as $key) {
            if ($key) {
                $fileName = $user->getUserName().'@'.$i.'.pub';
                file_put_contents($keydir.'/'.$fileName, $key);
                $i++;
            }
        }
    }

    protected function gitAdd($file) {
        exec('git add '.escapeshellarg($file), $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            return false;
        }
    }

    protected function gitCommit($message) {
        exec('git commit -m '.escapeshellarg($message).' 2>&1 >/dev/null', $output, $retVal);
        if ($retVal == 0) {
            return true;
        } else {
            return false;
        }
    }
}

?>