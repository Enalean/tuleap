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

require_once 'common/svn/SVN_Apache_ModPerl.class.php';


class SVN_Apache_ModPerlTest extends UnitTestCase {
    
    function setUp() {
        $GLOBALS['svn_prefix'] = '/svnroot';
    }
    
    function tearDown() {
        unset($GLOBALS['svn_prefix']);
    }
    
    /**
     * @return SVN_Apache_ModPerl
     */
    private function GivenAnApacheAuthenticationConfForGuineaPigProject() {
        $project_db_row = array('unix_group_name' => 'gpig',
                                'group_name'      => 'Guinea Pig',
                                'group_id'        => 101);
        return new SVN_Apache_ModPerl($project_db_row);
    }
    
    function testGetSVNApacheConfHeadersShouldInsertModPerl() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        
        $this->assertPattern('/PerlLoadModule Apache::Tuleap/', $conf->getHeaders());
    }
    
    function testGetApacheAuthShouldContainsDefaultValues() {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $conf = $mod->getConf();
        
        $this->assertPattern('/Require valid-user/', $conf);
        $this->assertPattern('/AuthType Basic/', $conf);
        $this->assertPattern('/AuthName "Subversion Authorization \(Guinea Pig\)"/', $conf);
    }
    
    function testGetApacheAuthShouldSetupPerlAccess() {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $conf = $mod->getConf();
        
        $this->assertPattern('/PerlAccessHandler/', $conf);
        $this->assertPattern('/TuleapDSN/', $conf);
    }
    
    function testGetApacheAuthShouldNotReferenceAuthMysql() {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $conf = $mod->getConf();
        
        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
    }
}

?>
