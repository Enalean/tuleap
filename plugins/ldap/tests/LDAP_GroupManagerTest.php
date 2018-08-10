<?php
/**
 * Copyright © STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\LDAP\GroupSyncNotificationsManager;

require_once dirname(__FILE__).'/../include/bootstrap.php';

class LDAP_GroupManagerUmbrella extends LDAP_GroupManager {
    function addUserToGroup($id, $userId) { }
    function removeUserfromGroup($id, $userId) { }
    function getDbGroupMembersIds($id)
    {
        return array(101,102);
    }
    function getDao()
    {
        $dao = mock('LDAP_ProjectGroupDao');
        stub($dao)->searchByGroupId()->returns(array());
        return $dao;
    }
}

Mock::generatePartial('LDAP', 'LDAP4GroupManager', array('searchGroupMembers', 'searchDn', 'getLdapParam'));
Mock::generate('LDAPResultIterator');
Mock::generate('LDAPResult');

class LDAP_GroupManagerTest extends TuleapTestCase {
    protected function getLdapResult($method, $result) {
        $ldapRes = new MockLDAPResult($this);
        $ldapRes->setReturnValue($method, $result);
        $ldapResIter = new MockLDAPResultIterator($this); 
        $ldapResIter->setReturnValue('count', 1);
        $ldapResIter->setReturnValue('current', $ldapRes);
        return $ldapResIter; 
    }

    public function testDoesBuildANotificationOnUpdate()
    {
        $ldapResIter = $this->getLdapResult('getGroupMembers', array(
            'eduid=edA,ou=people,dc=codendi,dc=com',
            'eduid=edE,ou=people,dc=codendi,dc=com'
        ));

        $ldap = new LDAP4GroupManager($this);
        $ldap->setReturnValue('searchGroupMembers', $ldapResIter);

        $ldap->setReturnValue('getLdapParam', 'ou=groups,dc=codendi,dc=com', array('grp_dn'));

        $ldap_user_manager = \Mockery::spy(LDAP_UserManager::class);
        $ldap_user_manager->shouldReceive('getUserIdsForLdapUser')->andReturn(array(101,102));
        $ldap_user_manager->shouldReceive('getUserFromLdap')->andReturn(aUser()->withRealname("J. Doe")->withUserName("jdoe")->build());

        $notm = \Mockery::spy(\Tuleap\LDAP\GroupSyncAdminEmailNotificationsManager::class);
        $notm->shouldReceive('sendNotifications')->once();

        $prjm = \Mockery::spy(\ProjectManager::class);
        $prjm->shouldReceive('getProject')->andReturn(\Mockery::spy(\Project::class));

        $grpManager = new LDAP_GroupManagerUmbrella($ldap, $ldap_user_manager, $prjm, $notm);
        $grpManager->setGroupDn('cn=whatever,ou=groups,dc=codendi,dc=com');
        $grpManager->setId(42);

        $toAdd = $grpManager->getUsersToBeAdded(LDAP_GroupManager::BIND_OPTION);
        $this->assertIdentical(count($toAdd), 2);
        $grpManager->bindWithLdap();
    }

    public function testLdapGroupContainsOtherLdapGroups()
    {
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
        
        $ldap->setReturnValueAt(0, 'searchDn', $ldapResIterUserA);
        $ldap->setReturnValueAt(1, 'searchDn', $ldapResIterUserE);

        $prjm = \Mockery::spy(\ProjectManager::class);
        $prjm->shouldReceive('getProject')->andReturn(\Mockery::spy(\Project::class));

        $ldap_user_manager = mock('LDAP_UserManager');
        $grpManager        = new LDAP_GroupManagerUmbrella(
            $ldap,
            $ldap_user_manager,
            $prjm,
            \Mockery::spy(\Tuleap\LDAP\GroupSyncAdminEmailNotificationsManager::class)
        );

        $members = $grpManager->getLdapGroupMembers('cn=ABCDEF,ou=groups,dc=codendi,dc=com');
        
        $this->assertTrue((count($members) === 2));
        $this->assertIsA($members['edA'], 'MockLDAPResult');
        $this->assertIdentical($members['edA']->getEdUid(), 'edA');
        $this->assertIsA($members['edE'], 'MockLDAPResult');
        $this->assertIdentical($members['edE']->getEdUid(), 'edE');
    }
}
