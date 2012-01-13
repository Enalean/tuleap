<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * 
 * This file is a part of Codendi.
 * 
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../include/LDAP_BackendSVN.class.php';

require_once 'common/dao/ServiceDao.class.php';
Mock::generate('ServiceDao');

Mock::generate('LDAP');
Mock::generate('LDAP_ProjectManager');

class LDAP_BackendSVNTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['svn_prefix'] = '/svnroot';
    }
    
    function tearDown() {
        unset($GLOBALS['svn_prefix']);
    }
    
    private function GivenAFullApacheConf() {
        $backend  = TestHelper::getPartialMock('LDAP_BackendSVN', array('_getServiceDao', 'getLdap', 'getLDAPProjectManager'));
        $dar      = TestHelper::arrayToDar(array('unix_group_name' => 'gpig',
                                                 'group_name'      => 'Guinea Pig',
                                                 'group_id'        => 101),
                                           array('unix_group_name' => 'garden',
                                                 'group_name'      => 'The Garden Project',
                                                 'group_id'        => 102));
        
        $dao = new MockServiceDao();
        $dao->setReturnValue('searchActiveUnixGroupByUsedService', $dar);
        $backend->setReturnValue('_getServiceDao', $dao);
        
        $ldap = new MockLDAP();
        $ldap->setReturnValue('getLDAPParam', 'ldap://ldap.tuleap.com', array('server'));
        $ldap->setReturnValue('getLDAPParam', 'dc=tuleap,dc=com', array('dn'));
        $backend->setReturnValue('getLdap', $ldap);
        
        $ldapPm = new MockLDAP_ProjectManager();
        $ldapPm->setReturnValue('hasSVNLDAPAuth', true);
        $backend->setReturnValue('getLDAPProjectManager', $ldapPm);
        
        return $backend->getApacheConf();
    }
    
    function testFullConfShouldWrapEveryThing() {
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
?>