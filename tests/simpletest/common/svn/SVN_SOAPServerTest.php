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
require_once 'common/svn/SVN_SOAPServer.class.php';

class SVN_SOAPServerTest extends TuleapTestCase {
        
    public function itCheckUserSessionAngGroupValidity() {
        $session_key = 'whatever';
        $group_id    = 123;
        $svn_path    = '/tags';

        $soap_request_valid     = mock('SOAP_RequestValidator');
        $svn_repository_listing = mock('SVN_RepositoryListing');

        $project = mock('Project');
        stub($soap_request_valid)->getProjectById($group_id, '*')->returns($project);

        $user = mock('User');
        stub($soap_request_valid)->continueSession($session_key)->returns($user);
        
        $svn_soap = new SVN_SOAPServer($soap_request_valid, $svn_repository_listing);
        
        $svn_repository_listing->expectOnce('getSvnPath', array($user, $project, $svn_path));
        
        $svn_soap->getSvnPath($session_key, $group_id, $svn_path);
    }
    
}

//class SVN_SOAPServer_LogTest extends TuleapTestCase {
//    
//    public function itDelegatesValidationOfSessionKeyAndGroupId() {
//        $session_hash = 'lksjfkljasdklfj';
//        $group_id     = 8598;
//        
//        $soap_request_valid = mock('SOAP_RequestValidator');
//        $soap_request_valid->expectOnce('continueSession', array($session_hash));
//        $soap_request_valid->expectOnce('getProjectById', array($group_id, 'getSvnLog'));
//        
//        $svn_log = mock('SVN_LogFactory');
//        $server  = new SVN_SOAPServer($soap_request_valid, $svn_log);
//        
//        $server->getSvnLog($session_hash, $group_id);
//    }
//    
//    public function itReturnsTheCommitsUsingUserAndProject() {
//        $session_hash = 'lksjfkljasdklfj';
//        $group_id    = 8598;
//        $user = mock('User');
//        $project = mock('Project');
//        $expected_commits = mock('stdClass');
//        $soap_request_valid = mock('SOAP_RequestValidator');
//        
//        stub($soap_request_valid)->continueSession($session_hash)->returns($user);
//        stub($soap_request_valid)->getProjectById($group_id)->returns($project);
//        $svn_repository_listing = stub('SVN_RepositoryListing')->getCommits($user, $project)->returns($expected_commits);
//        
//        $server = new SVN_SOAPServer($soap_request_valid, $svn_repository_listing);
//        $actual_commits = $server->getSvnLog($session_hash, $group_id);
//        $this->assertEqual($actual_commits, $expected_commits);
//    }
//}

?>
