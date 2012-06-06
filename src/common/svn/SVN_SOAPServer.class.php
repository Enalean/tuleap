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
require_once 'SVN_LogFactory.class.php';
require_once 'common/soap/SOAP_RequestValidator.class.php';
require_once 'common/date/TimeInterval.class.php';

/**
 * Wrapper for subversion related SOAP methods
 */
class SVN_SOAPServer {
    /**
     * @var SOAP_RequestValidator 
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
     * Error codes:
     * * 3001, Invalid session (wrong $sessionKey)
     * * 3002, User do not have access to the project
     *
     * @param String  $sessionKey Session key of the desired project admin
     * @param Integer $group_id    ID of the project the subversion repository belongs to
     * @param String  $path        Path to the directory to list (eg. '/tags')
     * 
     * @return ArrayOfString The list of directories
     */
    public function getSvnPath($sessionKey, $group_id, $path) {
        try {
            $current_user = $this->soap_request_validator->continueSession($sessionKey);
            $project      = $this->soap_request_validator->getProjectById($group_id, 'getSVNPath');
            $this->soap_request_validator->assertUserCanAccessProject($current_user, $project);
            
            return $this->svn_repository_listing->getSvnPath($current_user, $project, $path);
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    /**
     * Retrieves the SVN revisions of the project visible by the requesting user.
     *  
     *  Returned format:
     *  <code>
     *  array(
     *      array(
     *          "revision"  => Revision number,
     *          "author"    => User id,
     *          "date"      => timestamp,
     *          "message"   => commit message,
     *      )
     *  )
     *  </code>
     *  
     *  Example:
     *  <code>
     *  array(
     *      array(
     *          "revision"  => 12214,
     *          "author"    => 123,
     *          "date"      => 1337788549,
     *          "message"   => "Fix bug #456",
     *      )
     *      array(
     *          "revision"  => 12213,
     *          "author"    => 123,
     *          "date"      => 1337788530,
     *          "message"   => "Fix bug #789",
     *      )
     *  )
     *  </code>
     *
     *
     * Error codes:
     * * 3001, Invalid session (wrong $sessionKey)
     * * 3002, User do not have access to the project
     * * 3005, Invalid user id
     *
     * @param String  $sessionKey  Session key of the requesting user
     * @param Integer $group_id    ID of the project the subversion repository belongs to
     * @param Integer $limit       Maximum revisions returned
     * @param Integer $author_id   Id of commit author
     *
     * @return ArrayOfRevision The list of revisions
     */
    public function getSvnLog($sessionKey, $group_id, $limit, $author_id) {
        try {
            $current_user = $this->soap_request_validator->continueSession($sessionKey);
            $project      = $this->soap_request_validator->getProjectById($group_id, 'getSvnLog');
            $this->soap_request_validator->assertUserCanAccessProject($current_user, $project);
            
            $author    = $this->getUser($author_id);
            $svn_log   = new SVN_LogFactory($project);
            $revisions = $svn_log->getRevisions($limit, $author);

            return $revisions;
            
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }
    
    /**
     * Returns the list of active users (commiters) between start_date and end_date
     * 
     * @param String  $sessionKey Session key of the requesting user
     * @param Integer $group_id    ID of the project the subversion repository belongs to
     * @param Integer $start_date  Start of period (unix timestamp)
     * @param Integer $end_date    End of period   (unix timestamp)
     * 
     * @return ArrayOfCommiter
     */
    public function getSvnStatsUsers($sessionKey, $group_id, $start_date, $end_date) {
        try {
            $current_user = $this->soap_request_validator->continueSession($sessionKey);
            $project      = $this->soap_request_validator->getProjectById($group_id, 'getSvnStatsUser');
            $this->soap_request_validator->assertUserCanAccessProject($current_user, $project);
            
            $svn_log   = new SVN_LogFactory($project);
            $revisions = $svn_log->getCommiters(TimeInterval::fromUnixTimestamps($start_date, $end_date));

            return $revisions;
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }
    
    /**
     * Return top most modified files during the given period
     * 
     * @param String  $sessionKey Session key of the requesting user
     * @param Integer $group_id    ID of the project the subversion repository belongs to
     * @param Integer $start_date  Start of period (unix timestamp)
     * @param Integer $end_date    End of period   (unix timestamp)
     * @param Integer $limit       Max number of files to return
     * 
     * @return ArrayOfSvnPathInfo
     */
    public function getSvnStatsFiles($sessionKey, $group_id, $start_date, $end_date, $limit) {
        try {
            $current_user = $this->soap_request_validator->continueSession($sessionKey);
            $project      = $this->soap_request_validator->getProjectById($group_id, 'getSvnStatsFiles');
            $this->soap_request_validator->assertUserCanAccessProject($current_user, $project);
            
            $svn_log = new SVN_LogFactory($project);
            $files   = $svn_log->getTopModifiedFiles($current_user, TimeInterval::fromUnixTimestamps($start_date, $end_date), $limit);

            return $files;
        } catch (Exception $e) {
            return new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    private function getUser($author_id) {
        if (! $author_id) {
            $no_user_in_particular = new User(array('user_name' => ''));
            return $no_user_in_particular;
        } 
        
        $user = UserManager::instance()->getUserById($author_id);
        if ($user) {
            return $user;
        } else {
            throw new Exception("Invalid user id", '3005');
        }
    }
}



?>
