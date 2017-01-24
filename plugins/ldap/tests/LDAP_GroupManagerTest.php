<?php
/**
 * Copyright © STMicroelectronics, 2009. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2009.
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

require_once dirname(__FILE__).'/../include/LDAP_GroupManager.class.php';

//Mock::generatePartial('LDAP_GroupManager', 'LDAP_GroupManagerUmbrella', array('getLdap', 'addUserToGroup', 'removeUserfromGroup', 'getDbGroupMembersIds', 'getDao'));
class LDAP_GroupManagerUmbrella extends LDAP_GroupManager { 
    function addUserToGroup($id, $userId) { }
    function removeUserfromGroup($id, $userId) { }
    function getDbGroupMembersIds($id) { }
    function getDao() { }
}
Mock::generatePartial('LDAP', 'LDAP4GroupManager', array('searchGroupMembers', 'searchDn', 'getLdapParam'));
Mock::generate('LDAPResultIterator');
Mock::generate('LDAPResult');

class LDAP_GroupManagerTest extends TuleapTestCase {
    function __construct($name = 'LDAP_GroupManagerTest') {
        parent::__construct($name);
    }
    
    protected function getLdapResult($method, $result) {
        $ldapRes = new MockLDAPResult($this);
        $ldapRes->setReturnValue($method, $result);
        $ldapResIter = new MockLDAPResultIterator($this); 
        $ldapResIter->setReturnValue('count', 1);
        $ldapResIter->setReturnValue('current', $ldapRes);
        return $ldapResIter; 
    }
    
    function testLdapGroupContainsOtherLdapGroups() {
        // Search for umbrella group
        $ldapResIterABCDEF = $this->getLdapResult('getGroupMembers', array('cn=ABC,ou=groups,dc=codendi,dc=com', 'cn=DEF,ou=groups,dc=codendi,dc=com'));
        // Search for first sub-group
        $ldapResIterABC = $this->getLdapResult('getGroupMembers', array('eduid=edA,ou=people,dc=codendi,dc=com'));
        // Search for second sub-group
        $ldapResIterDEF = $this->getLdapResult('getGroupMembers', array('eduid=edE,ou=people,dc=codendi,dc=com'));
        // Search for first user
        $ldapResIterUserA = $this->getLdapResult('getEdUid', 'edA');
        // Search for second user
        $ldapResIterUserE = $this->getLdapResult('getEdUid', 'edE');
        
        $ldap = new LDAP4GroupManager($this);
        $ldap->setReturnValueAt(0, 'searchGroupMembers', $ldapResIterABCDEF);
        $ldap->setReturnValueAt(1, 'searchGroupMembers', $ldapResIterABC);
        $ldap->setReturnValueAt(2, 'searchGroupMembers', $ldapResIterDEF);
        
        $ldap->setReturnValue('getLdapParam', 'ou=groups,dc=codendi,dc=com', array('grp_dn'));
        $ldap->setReturnValue('getLdapParam', 'eduid', array('eduid'));
        $ldap->setReturnValue('getLdapParam', 'cn', array('cn'));
        $ldap->setReturnValue('getLdapParam', 'uid', array('uid'));
        $ldap->setReturnValue('getLdapParam', 'mail', array('mail'));
        
        $attrs = array('eduid', 'cn', 'uid', 'mail');
        $ldap->setReturnValueAt(0, 'searchDn', $ldapResIterUserA);
        $ldap->setReturnValueAt(1, 'searchDn', $ldapResIterUserE);
        
        $grpManager = new LDAP_GroupManagerUmbrella($ldap);
        $members = $grpManager->getLdapGroupMembers('cn=ABCDEF,ou=groups,dc=codendi,dc=com');
        
        $this->assertTrue((count($members) === 2));
        $this->assertIsA($members['edA'], 'MockLDAPResult');
        $this->assertIdentical($members['edA']->getEdUid(), 'edA');
        $this->assertIsA($members['edE'], 'MockLDAPResult');
        $this->assertIdentical($members['edE']->getEdUid(), 'edE');
        
    }
    
}

?>