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

require_once 'SVN_RepositoryListing.class.php';
require_once 'common/soap/SOAP_RequestValidator.class.php';

/**
 * Wrapper for subversion related SOAP methods
 */
class SVN_SOAPServer {
    /**
     * @var ProjectManager 
     */
    private $soap_request_validator;
    
    /**
     * @var SVN_RepositoryListing
     */
    private $svn_repository_listing;
    
    public function __construct(SOAP_RequestValidator $soap_request_validator,
                                SVN_RepositoryListing $svn_repository_listing) {
        $this->soap_request_validator = $soap_request_validator;
        $this->svn_repository_listing = $svn_repository_listing;
    }
    
    /**
     * Get stuff
     * 
     * @param String  $session_key Session key of the desired project admin
     * @param Integer $group_id    ID of the project the subversion repository belongs to
     * @param String  $path        Path to the directory to list (eg. '/tags')
     * 
     * @return String The list of directories
     */
    public function getSvnPath($session_key, $group_id, $path) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $project      = $this->soap_request_validator->getProjectById($group_id, 'getSVNPath');
            return implode("\n", $this->svn_repository_listing->getSvnPath($current_user, $project, $path));
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }
    
    public function getSvnLog($sessionHash, $groupId) {
        $user    = $this->soap_request_validator->continueSession($sessionHash);
        $project = $this->soap_request_validator->getProjectById($groupId);
        return $this->svn_repository_listing->getCommits($user, $project);
    }
}

?>
