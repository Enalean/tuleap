<?php
/**
 * Copyright (c) Enalean, 2012-2015. All Rights Reserved.
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

require_once 'common/svn/SVN_Apache_ModMysql.class.php';


class SVN_Apache_ModMysqlTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('sys_dbhost', 'db_server');
        ForgeConfig::set('sys_dbname', 'db');
        ForgeConfig::set('svn_prefix', '/svnroot');
        ForgeConfig::set('sys_dbauth_user', 'dbauth_user');
        ForgeConfig::set('sys_dbauth_passwd', 'dbauth_passwd');
    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    private function GivenAnApacheAuthenticationConfForGuineaPigProject() {
        $project_db_row = array('unix_group_name' => 'GPig',
            "public_path"     => "/svnroot/GPig",
            "system_path"     => "/svnroot/GPig",
            'group_name' => 'Guinea Pig',
            'group_id' => 101);
        $apacheConf = new SVN_Apache_ModMysql($project_db_row);
        return $apacheConf->getConf($project_db_row["public_path"], $project_db_row["system_path"]);
    }

    public function testGetApacheAuthShouldContainsDefaultValues() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();

        $this->assertPattern('/Location \/svnroot\/GPig>/', $conf);
        $this->assertPattern('/Require valid-user/', $conf);
        $this->assertPattern('/AuthType Basic/', $conf);
        $this->assertPattern('/AuthName "Subversion Authorization \(Guinea Pig\)"/', $conf);
    }

    public function testGetApacheAuthShouldSetupMysqlAccess() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();

        $this->assertPattern('/AuthMYSQLEnable/', $conf);
        $this->assertPattern('/AuthMySQLUser/', $conf);
        $this->assertPattern('/AuthMySQLPassword/', $conf);
        $this->assertPattern('/AuthMySQLHost/', $conf);
        $this->assertPattern('/AuthMySQLDB/', $conf);
        $this->assertPattern('/AuthMySQLUserTable/', $conf);
        $this->assertPattern('/AuthMySQLNameField/', $conf);
        $this->assertPattern('/AuthMySQLPasswordField/', $conf);
        $this->assertPattern('/AuthMySQLUserCondition/', $conf);
    }

    public function testGetApacheAuthShouldNotReferenceAuthPerl() {
        $conf = $this->GivenAnApacheAuthenticationConfForGuineaPigProject();

        $this->assertNoPattern('/PerlAccessHandler/', $conf);
    }
}
