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
    }
    
    public function init($project, $repoPath) {
        $conf = 'repo '.$project->getUnixName().'/'.$repoPath.PHP_EOL;
        $conf .= "\tRW = @".$project->getUnixName().'_project_members'.PHP_EOL;
        return file_put_contents($this->adminPath.'/conf/gitolite.conf', $conf);
    }
}

?>