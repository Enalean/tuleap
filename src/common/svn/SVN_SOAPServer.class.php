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
require_once 'SVN_Log.class.php';
require_once 'SVN_LogQuery.class.php';
require_once 'SVN_SoapRevisionDecorator.class.php';
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
     * Returns the content of a directory in Subversion according to user permissions
     * 
     * <ul>
     *   <li>If user cannot see the content it gets an empty array.</li>
     *   <li>The returned content is relative (/project/tags) gives array("1.0", "2.0").</li>
     * </ul>
     * 
     * @param String  $session_key Session key of the desired project admin
     * @param Integer $group_id    ID of the project the subversion repository belongs to
     * @param String  $path        Path to the directory to list (eg. '/tags')
     * 
     * @return ArrayOfString The list of directories
     */
    public function getSvnPath($session_key, $group_id, $path) {
        try {
            $current_user = $this->soap_request_validator->continueSession($session_key);
            $project      = $this->soap_request_validator->getProjectById($group_id, 'getSVNPath');
            return $this->svn_repository_listing->getSvnPath($current_user, $project, $path);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }
    
    /**
     * Retrieves the SVN revisions of the project visible by the requesting user.
     * 
     * @param String  $session_key Session key of the requesting user
     * @param Integer $group_id    ID of the project the subversion repository belongs to
     * @param Integer $limit       Optional - Maximum commits count (defaults to 50)
     * @param Integer $author_id   Optional - Commit author user id to filter with (defaults to no filter)
     * 
     * @return String The list of commits
     */
    public function getSvnLog($session_key, $group_id, $limit, $author_id) {
        try {
            $this->soap_request_validator->continueSession($session_key);
            
            $project   = $this->soap_request_validator->getProjectById($group_id, 'getSvnLog');
            $svn_log   = new SVN_Log($project);
            $query     = new SVN_LogQuery($limit, $author_id);
            $decorator = new SVN_SoapRevisionDecorator();
            $revisions   = $svn_log->getDecoratedRevisions($query, $decorator);

            return print_r($revisions, true);
            
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }
}

?>
