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

        if (file_put_contents($prjConfDir.'/'.$project->getUnixName().'.conf', $conf)) {
            return $this->updateMainConfIncludes($project);
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
            $newConf = '';
            $dir = new DirectoryIterator($this->adminPath.'/conf/projects');
            foreach ($dir as $file) {
                if (!$file->isDot()) {
                    $newConf .= 'include "projects/'.basename($file->getFilename()).'"'.PHP_EOL;
                }
            }
            return file_put_contents($this->confFilePath, $newConf);
        }
    }
}

?>