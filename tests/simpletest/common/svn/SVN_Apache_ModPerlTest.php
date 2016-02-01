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
        $GLOBALS['sys_dbhost'] = 'db_server';
        $GLOBALS['sys_dbname'] = 'db';
        $GLOBALS['svn_prefix'] = '/svnroot';
        $GLOBALS['sys_dbauth_user']   = 'dbauth_user';
        $GLOBALS['sys_dbauth_passwd'] = 'dbauth_passwd';
    }
    
    function tearDown() {
        unset($GLOBALS['sys_dbname']);
        unset($GLOBALS['sys_dbhost']);
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['sys_dbauth_user']);
        unset($GLOBALS['sys_dbauth_passwd']);
    }

    private function setConfForGuineaPigProject() {
        return array('unix_group_name' => 'gpig',
                     'public_path'     => '/svnroot/gpig',
                     'system_path'     => '/svnroot/gpig',
                     'group_name'      => 'Guinea Pig',
                     'group_id'        => 101);
    }

    /**
     * @return SVN_Apache_ModPerl
     */
    private function GivenAnApacheAuthenticationConfForGuineaPigProject() {
        return new SVN_Apache_ModPerl($this->setConfForGuineaPigProject());
    }

    function testGetSVNApacheConfHeadersShouldInsertModPerl() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();

        $this->assertPattern('/PerlLoadModule Apache::Tuleap/', $conf->getHeaders());
    }
    
    function testGetApacheAuthShouldContainsDefaultValues() {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);

        $this->assertPattern('/Require valid-user/', $conf);
        $this->assertPattern('/AuthType Basic/', $conf);
        $this->assertPattern('/AuthName "Subversion Authorization \(Guinea Pig\)"/', $conf);
    }
    
    function testGetApacheAuthShouldSetupPerlAccess() {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);
        
        $this->assertPattern('/PerlAccessHandler/', $conf);
        $this->assertPattern('/TuleapDSN/', $conf);
    }
    
    function testGetApacheAuthShouldNotReferenceAuthMysql() {
        $mod  = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();
        $project_db_row = $this->setConfForGuineaPigProject();
        $conf = $mod->getConf($project_db_row["public_path"], $project_db_row["system_path"]);
        
        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
    }
}

?>
