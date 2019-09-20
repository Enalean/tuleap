<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

Mock::generate('UserDao');

Mock::generatePartial(
    'UserDao',
    'UserDaoTestValidity',
    array(
        'suspendAccount',
        'returnNotProjectMembers',
        'delayForBeingNotProjectMembers',
        'delayForBeingSubscribed',
        'getLogger'
    )
);

Mock::generatePartial(
    'UserManager',
    'UserManager4AccountValidity',
    array('_getEventManager',
                            'getDao',
                      )
);

Mock::generate('DataAccessResult');

class UserAccountValidityTest extends TuleapTestCase
{

    function __construct($name = 'User Account Validity test')
    {
        parent::__construct($name);
    }

    function setUp()
    {
        $GLOBALS['sys_suspend_inactive_accounts_delay']  = 60;
        $GLOBALS['sys_suspend_non_project_member_delay'] = 15;
    }

    function tearDown()
    {
        unset($GLOBALS['sys_suspend_inactive_accounts_delay']);
        unset($GLOBALS['sys_suspend_non_project_member_delay']);
    }

    function testSuspendAccountDao()
    {
        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);

        $da = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $da->expectOnce('query', array('UPDATE user SET status = "S", unix_status = "S" WHERE status != "D" AND (toto)', '*'));
        $da->setReturnValue('query', $dar);

        $dao = new UserDao($da);
        $d2 = $dao->suspendAccount('toto');
    }

    function testSuspendInactiveAccounts()
    {
        $currentDate = 1257757729;
        // 60 days in the past
        $lastValidAccess = 1252573729;

        $dao = new MockUserDao($this);
        $dao->expectOnce('suspendInactiveAccounts', array($lastValidAccess));

        $um = new UserManager4AccountValidity($this);
        $um->setReturnReference('getDao', $dao);

        $um->suspendInactiveAccounts($currentDate);
    }

    function testSuspendExpiredAccountsDao()
    {
        $da = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $da->setReturnValue('escapeInt', 1257671329, array(1257671329));

        $dao = new UserDaoTestValidity($this);
        $dao->da = $da;
        $dao->expectOnce('suspendAccount', array('expiry_date != 0 AND expiry_date < 1257671329'));
        $dao->suspendExpiredAccounts(1257671329);
    }

    function testSuspendUserNotProjectMembers()
    {
        $currentDate = 1257757729;
        // 15 days in the past
        $lastValidAccess = 1256461729;

        $dao = new MockUserDao($this);
        $dao->expectOnce('suspendUserNotProjectMembers', array($lastValidAccess));

        $um = new UserManager4AccountValidity($this);
        $um->setReturnReference('getDao', $dao);

        $um->suspendUserNotProjectMembers($currentDate);
    }

    function testSuspendUserRemovedFromAllProjects()
    {
        $darUser = new MockDataAccessResult($this);
        //$darUser->setReturnValue('rewind', true);
        $darUser->setReturnValueAt(0, 'valid', true);
        $darUser->setReturnValueAt(1, 'valid', false);
        $darUser->setReturnValue('current', array('user_id' => 112));

        $dao = new UserDaoTestValidity($this);

        $dao->expectOnce('returnNotProjectMembers');
        $dao->setReturnReference('returnNotProjectMembers', $darUser);
        $dao->setReturnValue('getLogger', mock('BackendLogger'));

        $darRemovedDate = new MockDataAccessResult($this);
        $darRemovedDate->setReturnValueAt(0, 'valid', true);
        $darRemovedDate->setReturnValueAt(1, 'valid', false);
        $darRemovedDate->setReturnValue('isError', false);
        $darRemovedDate->setReturnValue('rowCount', 1);
        $darRemovedDate->setReturnValue('current', array('date' => 1258107747));

        $dao->expectOnce('delayForBeingNotProjectMembers', array(112));
        $dao->setReturnReference('delayForBeingNotProjectMembers', $darRemovedDate, array(112));

        $da = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $da->setReturnValue('escapeInt', 112, array(112));
        $dao->da = $da;

        $dao->expectOnce('suspendAccount', array('user.user_id = 112'));
        $dao->suspendUserNotProjectMembers(1258307747);
    }

    function testRemovedFromAllProjectsDelayNotExpired()
    {
        $darUser = new MockDataAccessResult($this);
        $darUser->setReturnValueAt(0, 'valid', true);
        $darUser->setReturnValueAt(1, 'valid', false);
        $darUser->setReturnValue('current', array('user_id' => 112));

        $dao = new UserDaoTestValidity($this);
        $dao->expectOnce('returnNotProjectMembers');
        $dao->setReturnReference('returnNotProjectMembers', $darUser);
        $dao->setReturnValue('getLogger', mock('BackendLogger'));

        $darRemovedDate = new MockDataAccessResult($this);
        $darRemovedDate->setReturnValueAt(0, 'valid', true);
        $darRemovedDate->setReturnValueAt(1, 'valid', false);
        $darRemovedDate->setReturnValue('isError', false);
        $darRemovedDate->setReturnValue('rowCount', 1);
        $darRemovedDate->setReturnValue('current', array('date' => 1258407747));

        $dao->expectOnce('delayForBeingNotProjectMembers', array(112));
        $dao->setReturnReference('delayForBeingNotProjectMembers', $darRemovedDate, array(112));

        $dao->expectNever('suspendAccount');
        $dao->suspendUserNotProjectMembers(1258307747);
    }

    function testSuspendUserNotAddedToAnyProject()
    {
        $darUser = new MockDataAccessResult($this);
        $darUser->setReturnValueAt(0, 'valid', true);
        $darUser->setReturnValueAt(1, 'valid', false);
        $darUser->setReturnValue('current', array('user_id' => 112));

        $dao = new UserDaoTestValidity($this);
        $dao->expectOnce('returnNotProjectMembers');
        $dao->setReturnReference('returnNotProjectMembers', $darUser);
        $dao->setReturnValue('getLogger', mock('BackendLogger'));

        $darNewMember = new MockDataAccessResult($this);
        $darNewMember->setReturnValueAt(0, 'valid', true);
        $darNewMember->setReturnValueAt(1, 'valid', false);
        $darNewMember->setReturnValue('isError', false);
        $darNewMember->setReturnValue('rowCount', 0);

        $dao->expectOnce('delayForBeingNotProjectMembers', array(112));
        $dao->setReturnReference('delayForBeingNotProjectMembers', $darNewMember, array(112));

        $darAddDate = new MockDataAccessResult($this);
        $darAddDate->setReturnValueAt(0, 'valid', true);
        $darAddDate->setReturnValueAt(1, 'valid', false);
        $darAddDate->setReturnValue('isError', false);
        $darAddDate->setReturnValue('rowCount', 1);

        $dao->expectOnce('delayForBeingSubscribed', array(112,1258307747));
        $dao->setReturnReference('delayForBeingSubscribed', $darAddDate, array(112,1258307747));

        $da = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $da->setReturnValue('escapeInt', 112, array(112));
        $dao->da = $da;

        $dao->expectOnce('suspendAccount', array('user.user_id = 112'));
        $dao->suspendUserNotProjectMembers(1258307747);
    }

    function testNotAddedToAnyProjectDelayNotExpired()
    {
        $darUser = new MockDataAccessResult($this);
        $darUser->setReturnValueAt(0, 'valid', true);
        $darUser->setReturnValueAt(1, 'valid', false);
        $darUser->setReturnValue('current', array('user_id' => 112));

        $dao = new UserDaoTestValidity($this);
        $dao->expectOnce('returnNotProjectMembers');
        $dao->setReturnReference('returnNotProjectMembers', $darUser);
        $dao->setReturnValue('getLogger', mock('BackendLogger'));

        $darNewMember = new MockDataAccessResult($this);
        $darNewMember->setReturnValueAt(0, 'valid', true);
        $darNewMember->setReturnValueAt(1, 'valid', false);
        $darNewMember->setReturnValue('isError', false);
        $darNewMember->setReturnValue('rowCount', 0);

        $dao->expectOnce('delayForBeingNotProjectMembers', array(112));
        $dao->setReturnReference('delayForBeingNotProjectMembers', $darNewMember, array(112));

        $darAddDate = new MockDataAccessResult($this);
        $darAddDate->setReturnValueAt(0, 'valid', true);
        $darAddDate->setReturnValueAt(1, 'valid', false);
        $darAddDate->setReturnValue('isError', false);
        $darAddDate->setReturnValue('rowCount', 0);

        $dao->expectOnce('delayForBeingSubscribed', array(112,1258307747));
        $dao->setReturnReference('delayForBeingSubscribed', $darAddDate, array(112,1258307747));

        $dao->expectNever('suspendAccount');
        $dao->suspendUserNotProjectMembers(1258307747);
    }
}
