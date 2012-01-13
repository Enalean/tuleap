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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/backend/SVN_Apache_ModPerl.class.php';


class SVN_Apache_ModPerlTest extends UnitTestCase {
    
    function setUp() {
        $GLOBALS['svn_prefix'] = '/svnroot';
    }
    
    function tearDown() {
        unset($GLOBALS['svn_prefix']);
    }
    
    private function GivenAnApacheAuthenticationConfForGuineaPigProject() {
        $project_db_row = array('unix_group_name' => 'gpig',
                                'group_name'      => 'Guinea Pig',
                                'group_id'        => 101);
        $apacheConf = new SVN_Apache_ModPerl(array($project_db_row));
        return $apacheConf->getFullConf();
    }
    
    function testGetSVNApacheConfHeadersShouldInsertModPerl() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertPattern('/PerlLoadModule Apache::Codendi/', $conf);
    }
    
    function testGetApacheAuthShouldContainsDefaultValues() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertPattern('/Require valid-user/', $conf);
        $this->assertPattern('/AuthType Basic/', $conf);
        $this->assertPattern('/AuthName "Subversion Authorization \(Guinea Pig\)"/', $conf);
    }
    
    function testGetApacheAuthShouldSetupPerlAccess() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertPattern('/PerlAccessHandler/', $conf);
        $this->assertPattern('/CodendiDSN/', $conf);
    }
    
    function testGetApacheAuthShouldNotReferenceAuthMysql() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
    }
    
    private function GivenAFullApacheConf() {
        $projects = array(array('unix_group_name' => 'gpig',
                                'group_name'      => 'Guinea Pig',
                                'group_id'        => 101),
                          array('unix_group_name' => 'garden',
                                'group_name'      => 'The Garden Project',
                                 'group_id'        => 102));
        $apacheConf = new SVN_Apache_ModPerl($projects);
        return $apacheConf->getFullConf();
    }
    
    function testFullConfShouldWrapEveryThing() {
        $conf = $this->GivenAFullApacheConf();
        //echo '<pre>'.htmlentities($conf).'</pre>';
        
        $this->assertPattern('/PerlLoadModule Apache::Codendi/', $conf);
        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
        $this->ThenThereAreTwoLocationDefinedGpigAndGarden($conf);
    }
    
    private function ThenThereAreTwoLocationDefinedGpigAndGarden($conf) {
        $matches = array();
        preg_match_all('%<Location /svnroot/([^>]*)>%', $conf, $matches);
        $this->assertEqual($matches[1][0], 'gpig');
        $this->assertEqual($matches[1][1], 'garden');
    }
}

?>
