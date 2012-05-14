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

require_once 'SVN_PermissionsManager.class.php';
require_once 'common/project/ProjectManager.class.php';

/**
 * Wrapper for subversion related SOAP methods
 */
class SVN_SOAPServer {
    /**
     * @var ProjectManager 
     */
    private $project_manager;
    
    /**
     * @var SVN_PermissionsManager
     */
    private $svn_permissions_manager;
    
    public function __construct(ProjectManager $project_manager,
                                SVN_PermissionsManager $svn_permissions_manager) {
        $this->project_manager         = $project_manager;
        $this->svn_permissions_manager = $svn_permissions_manager;
    }
    
    protected function getDirectoryListing($repository_path, $svn_path) {
        $cmd    = '/usr/bin/svnlook tree --non-recursive --full-paths'.escapeshellarg($repository_path).' '.escapeshellarg($svn_path);
        $output = array();
        exec($cmd, $output);
        return $output;
    }
    
    public function getSvnPath($session_key, $group_id, $path) {
        try {
            $project = $this->project_manager->getGroupByIdForSoap($group_id, 'getSVNPath');
            return $this->getSVNPathListing($project, $path);
        } catch (Exception $e) {
            return new SoapFault('0', $e->getMessage());
        }
    }
    
    public function getSVNPathListing(Project $project, $svn_path) {
        $paths            = array();
        $repository_path  = $GLOBALS['svn_prefix'].'/'.$project->getUnixName();
        $content          = $this->getDirectoryListing($repository_path, $svn_path);
        foreach ($content as $line) {
            $paths[]= $this->extractDirectoryContent($line, $svn_path);
        }
        return array_filter($paths);
    }
    
    private function extractDirectoryContent($line, $svn_path) {
        $match_path_regex = "%^$svn_path/%";
        if (preg_match($match_path_regex, $line)) {
            return preg_replace($match_path_regex, '', $line);
        }
        return '';
    }
    
    
    /**
     *
     * @see session_continue
     * 
     * @param String $session_key
     * 
     * @return User
     */
    protected function continueSession($session_key) {
        $user = $this->userManager->getCurrentUser($session_key);
        if ($user->isLoggedIn()) {
            return $user;
        }
        throw new SoapFault('3001', 'Invalid session');
    }
}

?>
