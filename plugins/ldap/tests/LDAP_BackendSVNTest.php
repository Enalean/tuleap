<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/LDAP_BackendSVN.class.php';

require_once 'common/dao/ServiceDao.class.php';
Mock::generate('ServiceDao');

Mock::generate('LDAP');
Mock::generate('LDAP_ProjectManager');

class LDAP_BackendSVNTestEventManager extends EventManager {
    public function processEvent($event_name, $params) {
        $ldap = mock('LDAP');

        $params['svn_apache_auth'] = new LDAP_SVN_Apache($ldap, $params['project_info']);
    }
}

class LDAP_BackendSVNTest extends TuleapTestCase {

    public function setUp() {
        $GLOBALS['svn_prefix'] = '/svnroot';
        $GLOBALS['sys_name']   = 'Platform';
    }

    public function tearDown() {
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['sys_name']);
    }

    private function GivenAFullApacheConf() {
        $backend  = TestHelper::getPartialMock(
            'LDAP_BackendSVN',
            array(
                'getLdap',
                'getLDAPProjectManager',
                'getSVNApacheAuthFactory',
                'getSvnDao'
            )
        );

        $project_array_01 = array(
            'repository_name' => 'gpig',
            'group_name'      => 'Guinea Pig',
            'public_path'     => '/svnroot/gpig',
            'system_path'     => '/svnroot/gpig',
            'group_id'        => 101,
            'auth_mod'        => 'modmysql'
        );

        $project_array_02 = array(
            'repository_name' => 'garden',
            'public_path'     => '/svnroot/garden',
            'system_path'     => '/svnroot/garden',
            'group_name'      => 'The Garden Project',
            'group_id'        => 102,
            'auth_mod'        => 'modmysql'
        );

        $svn_dao = mock('SVN_DAO');
        stub($svn_dao)->searchSvnRepositories()->returnsDar($project_array_01, $project_array_02);
        stub($backend)->getsvnDao()->returns($svn_dao);

        $ldap = mock('LDAP');
        stub($ldap)->getLDAPParam('server')->returns('ldap://ldap.tuleap.com');
        stub($ldap)->getLDAPParam('dn')->returns('dc=tuleap,dc=com');

        $project_manager  = mock('ProjectManager');
        $event_manager    = new LDAP_BackendSVNTestEventManager();
        $token_manager    = mock('SVN_TokenUsageManager');
        $cache_parameters = mock('Tuleap\SvnCore\Cache\Parameters');

        $factory = new SVN_Apache_Auth_Factory($project_manager, $event_manager, $token_manager, $cache_parameters);

        $backend->setReturnValue('getSVNApacheAuthFactory', $factory);

        return $backend->getApacheConf();
    }

    public function testFullConfShouldWrapEveryThing() {
        $conf = $this->GivenAFullApacheConf();
        //echo '<pre>'.htmlentities($conf).'</pre>';

        $this->assertNoPattern('/AuthMYSQLEnable/', $conf);
        $this->assertPattern('/AuthLDAPUrl/', $conf);
        $this->ThenThereAreTwoLocationDefinedGpigAndGarden($conf);
    }

    private function ThenThereAreTwoLocationDefinedGpigAndGarden($conf) {
        $matches = array();
        preg_match_all('%<Location /svnroot/([^>]*)>%', $conf, $matches);
        $this->assertEqual($matches[1][0], 'gpig');
        $this->assertEqual($matches[1][1], 'garden');
    }
}