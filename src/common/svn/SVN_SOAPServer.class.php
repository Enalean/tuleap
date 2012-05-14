<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Wrapper for subversion related SOAP methods
 */
class SVN_SOAPServer {
    protected function getDirectoryContent($repository_path, $svn_path) {
        //svnlook tree --non-recursive --full-paths /svnroot/gpig /tags    
    }
    
    public function getSVNPaths($project_name, $svn_path) {
        $content = $this->getDirectoryContent($GLOBALS['svn_prefix'].'/'.$project_name, $svn_path);
        $content = preg_replace("%^$svn_path%", '', $content);
        return array_filter(explode("\n", $content));
    }
}

?>
