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
    
    public function setUp() {
        parent::setUp();
        $GLOBALS['svn_prefix'] = '/data/svnroot';
    }
    
    public function tearDown() {
        parent::tearDown();
        unset($GLOBALS['svn_prefix']);
    }
    
    public function itShowsOnlyTheDirectoryContents() {
        $svn_soap = TestHelper::getPartialMock('SVN_SOAPServer', array('getDirectoryListing'));
        
        $project_manager = mock('ProjectManager');
        $svn_perms_mgr = stub('SVN_PermissionsManager')->userCanRead()->returns(true);
        $svn_soap->__construct($project_manager, $svn_perms_mgr);

        $content = array("/my/Project/tags",
                         "/my/Project/tags/1.0",
                         "/my/Project/tags/2.0");
        stub($svn_soap)->getDirectoryListing('/data/svnroot/gpig', '/my/Project/tags')->returns($content);

        $project = stub('Project')->getUnixName()->returns('gpig');
        
        $tags = $svn_soap->getSVNPathListing($project, '/my/Project/tags');
        $this->assertEqual(array_values($tags), array('1.0', '2.0'));
    }
    
    public function itCheckUserSessionAngGroupValidity() {
        $session_key = 'whatever';
        $group_id    = 123;
        $svn_path    = '/tags';

        $project         = mock('Project');
        $project_manager = stub('ProjectManager')->getGroupByIdForSoap($group_id, '*')->returns($project);
        $svn_perms_mgr   = mock('SVN_PermissionsManager');
        
        $svn_soap = TestHelper::getPartialMock('SVN_SOAPServer', array('getSVNPathListing', 'continueSession'));
        $svn_soap->__construct($project_manager, $svn_perms_mgr);
        
        $svn_soap->expectOnce('continueSession', array($session_key));
        $svn_soap->expectOnce('getSVNPathListing', array($project, $svn_path));
        
        $svn_soap->getSvnPath($session_key, $group_id, $svn_path);
    }
}

?>
