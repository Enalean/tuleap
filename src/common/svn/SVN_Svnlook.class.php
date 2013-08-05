<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'SVN_SvnlookException.class.php';

class SVN_Svnlook {
    private $svnlook = '/usr/bin/svnlook';
    private $svn_prefix;

    public function __construct($svn_prefix) {
        $this->svn_prefix = $svn_prefix;
    }

    public function getDirectoryListing(Project $project, $svn_path) {
        $command = 'tree --non-recursive --full-paths '.escapeshellarg($this->getRepositoryPath($project)).' '.escapeshellarg($svn_path);
        return $this->execute($command);
    }

    private function getRepositoryPath(Project $project) {
        return $this->svn_prefix. DIRECTORY_SEPARATOR . $project->getUnixName();
    }

    private function execute($command) {
        $output  = array();
        $ret_val = 1;
        exec("$this->svnlook $command 2>&1", $output, $ret_val);
        if ($ret_val == 0) {
            return $output;
        } else {
            throw new SVN_SvnlookException($command, $output, $ret_val);
        }
    }
}

?>
