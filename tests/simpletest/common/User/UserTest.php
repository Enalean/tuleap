<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

Mock::generate('PFUser');
Mock::generatePartial(
    'PFUser',
    'UserTestVersion',
    array('getStatus', 'getUnixStatus', 'getPreferencesDao', 'getId', 'isAnonymous')
);
Mock::generatePartial(
    'PFUser',
    'UserTestVersion2',
    array('getUserGroupData', 'getPermissionManager')
);

Mock::generate('UserPreferencesDao');
Mock::generate('DataAccessResult');
Mock::generate('UGroupDao');

Mock::generate('BaseLanguageFactory');

// {{{ Setup stuff for "recent" things management
abstract class FakeRecent implements Recent_Element_Interface
{
}
Mock::generate('FakeRecent');

class UserTestVersion_MockPreferences extends UserTestVersion
{
    protected $UserTestVersion_MockPreferences_hash = array();

    public function getPreference($key)
    {
        if (isset($this->UserTestVersion_MockPreferences_hash[$key])) {
            return $this->UserTestVersion_MockPreferences_hash[$key];
        }
        return false;
    }

    public function setPreference($key, $value)
    {
        $this->UserTestVersion_MockPreferences_hash[$key] = $value;
    }

    public function delPreference($key)
    {
        if (isset($this->UserTestVersion_MockPreferences_hash[$key])) {
            unset($this->UserTestVersion_MockPreferences_hash[$key]);
        }
    }
}
// }}}

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * Tests the class User
 */
class UserTest extends TuleapTestCase
{

    function testStatus()
    {
        $u1 = new UserTestVersion($this);
        $u1->setReturnValue('getStatus', 'A');
        $u2 = new UserTestVersion($this);
        $u2->setReturnValue('getStatus', 'S');
        $u3 = new UserTestVersion($this);
        $u3->setReturnValue('getStatus', 'D');
        $u4 = new UserTestVersion($this);
        $u4->setReturnValue('isAnonymous', false);
        $u4->setReturnValue('getStatus', 'R');
        $u4 = new UserTestVersion($this);
        $u4->setReturnValue('isAnonymous', true);
        $u4->setReturnValue('getStatus', 'R');

        $this->assertTrue($u1->isActive());
        $this->assertFalse($u1->isSuspended());
        $this->assertFalse($u1->isDeleted());
        $this->assertFalse($u1->isRestricted());

        $this->assertFalse($u2->isActive());
        $this->assertTrue($u2->isSuspended());
        $this->assertFalse($u2->isDeleted());
        $this->assertFalse($u2->isRestricted());

        $this->assertFalse($u3->isActive());
        $this->assertFalse($u3->isSuspended());
        $this->assertTrue($u3->isDeleted());
        $this->assertFalse($u3->isRestricted());

        $this->assertFalse($u4->isActive());
        $this->assertFalse($u4->isSuspended());
        $this->assertFalse($u4->isDeleted());
        $this->assertFalse($u4->isRestricted());
    }

    function testUnixStatus()
    {
        $u1 = new UserTestVersion($this);
        $u1->setReturnValue('getUnixStatus', 'A');
        $u2 = new UserTestVersion($this);
        $u2->setReturnValue('getUnixStatus', 'S');
        $u3 = new UserTestVersion($this);
        $u3->setReturnValue('getUnixStatus', 'D');
        $u4 = new UserTestVersion($this);
        $u4->setReturnValue('getUnixStatus', 'N');

        $this->assertTrue($u1->hasActiveUnixAccount());
        $this->assertFalse($u1->hasSuspendedUnixAccount());
        $this->assertFalse($u1->hasDeletedUnixAccount());
        $this->assertFalse($u1->hasNoUnixAccount());

        $this->assertFalse($u2->hasActiveUnixAccount());
        $this->assertTrue($u2->hasSuspendedUnixAccount());
        $this->assertFalse($u2->hasDeletedUnixAccount());
        $this->assertFalse($u2->hasNoUnixAccount());

        $this->assertFalse($u3->hasActiveUnixAccount());
        $this->assertFalse($u3->hasSuspendedUnixAccount());
        $this->assertTrue($u3->hasDeletedUnixAccount());
        $this->assertFalse($u3->hasNoUnixAccount());

        $this->assertFalse($u4->hasActiveUnixAccount());
        $this->assertFalse($u4->hasSuspendedUnixAccount());
        $this->assertFalse($u4->hasDeletedUnixAccount());
        $this->assertTrue($u4->hasNoUnixAccount());
    }

    function testPreferences()
    {
        $dao = new MockUserPreferencesDao($this);
        $dar = new MockDataAccessResult($this);

        $empty_dar = new MockDataAccessResult($this);
        $empty_dar->setReturnValue('getRow', false);
        $dar->setReturnValueAt(0, 'getRow', array('preference_value' => '123'));
        $dar->setReturnValueAt(1, 'getRow', false);

        $dao->setReturnReference('search', $empty_dar, array(666, 'unexisting_preference'));
        $dao->setReturnReference('search', $dar, array(666, 'existing_preference'));
        $dao->expectCallCount('search', 2);
        $dao->setReturnValue('set', true, array(666, 'existing_preference', '456'));
        $dao->expectOnce('set');
        $dao->setReturnValue('delete', true, array(666, 'existing_preference'));
        $dao->expectOnce('delete');

        $user = new UserTestVersion($this);
        $user->setReturnReference('getPreferencesDao', $dao);
        $user->setReturnValue('getId', 666);

        $this->assertFalse($user->getPreference('unexisting_preference'), 'Unexisting preference, should return false');
        $this->assertEqual('123', $user->getPreference('existing_preference'), 'Existing preference should return 123');
        $this->assertEqual('123', $user->getPreference('existing_preference'), 'Existing preference should return 123, should be cached');
        $this->assertTrue($user->setPreference('existing_preference', '456'), 'Updating preference should return true. %s');
        $this->assertEqual('456', $user->getPreference('existing_preference'), 'Existing preference has been updated, should now return 456. No call to dao since cached during update');
        $this->assertTrue($user->delPreference('existing_preference'), 'Deletion of preference should return true');
        $this->assertFalse($user->getPreference('existing_preference'), 'Preferences has been deleted. No call to dao since cached during delete');
    }

    function testNone()
    {
        $user_none = new UserTestVersion($this);
        $user_none->setReturnValue('getId', 100);
        $this->assertTrue($user_none->isNone());

        $user = new UserTestVersion($this);
        $user->setReturnValue('getId', 666);
        $this->assertFalse($user->isNone());
    }

    function testIsMemberSiteAdmin()
    {
        $siteadmin = new UserTestVersion2($this);
        $ug_siteadmin = array(
            '1' => array(
                    'user_group_id' => '1',
                    'user_id' => '101',
                    'group_id' => '1',
                    'admin_flags' => 'A',
                    'bug_flags' => '2',
                    'forum_flags' => '2',
                    'project_flags' => '2',
                    'patch_flags' => '2',
                    'support_flags' => '2',
                    'file_flags' => '2',
                    'wiki_flags' => '2',
                    'svn_flags' => '2',
                    'news_flags' => '2'
                  ));
        $siteadmin->setReturnValue('getUserGroupData', $ug_siteadmin);

        $this->assertTrue($siteadmin->isMember(1));
        $this->assertTrue($siteadmin->isMember(1, 'A'));
        // Site admin is member and admin of any project
        $this->assertTrue($siteadmin->isMember(123));
        $this->assertTrue($siteadmin->isMember(123, 'A'));
    }

    public function testIsMemberProjectAdmin()
    {
        $projectadmin     = new UserTestVersion2($this);
        $ug_project_admin = array(
            '123' => array(
                'user_group_id' => '1',
                'user_id'       => '101',
                'group_id'      => '123',
                'admin_flags'   => 'A',
                'bug_flags'     => '2',
                'forum_flags'   => '2',
                'project_flags' => '2',
                'patch_flags'   => '2',
                'support_flags' => '2',
                'file_flags'    => '2',
                'wiki_flags'    => '2',
                'svn_flags'     => '2',
                'news_flags'    => '2'
            )
        );
        $projectadmin->setReturnValue('getUserGroupData', $ug_project_admin);
        $permission_manager = mock('User_ForgeUserGroupPermissionsManager');
        stub($projectadmin)->getPermissionManager()->returns($permission_manager);

        // Project admin is member and admin of only her projects
        $this->assertTrue($projectadmin->isMember(123));
        $this->assertTrue($projectadmin->isMember(123, 'A'));
        $this->assertFalse($projectadmin->isMember(456));
        $this->assertFalse($projectadmin->isMember(456, 'A'));
        $this->assertFalse($projectadmin->isMember(1));
        $this->assertFalse($projectadmin->isMember(1, 'A'));
    }

    /**
     * This test reproduce bug #20456 on codex.xerox.com
     */
    public function testIsMemberProjectMember()
    {
        $projectmember     = new UserTestVersion2($this);
        $ug_project_member = array(
            '789' => array(
                'user_group_id' => '1',
                'user_id'       => '101',
                'group_id'      => '789',
                'admin_flags'   => '',
                'bug_flags'     => '2',
                'forum_flags'   => '2',
                'project_flags' => '2',
                'patch_flags'   => '2',
                'support_flags' => '2',
                'file_flags'    => '2',
                'wiki_flags'    => '2',
                'svn_flags'     => '2',
                'news_flags'    => '2'
            )
        );
        $projectmember->setReturnValue('getUserGroupData', $ug_project_member);
        $permission_manager = mock('User_ForgeUserGroupPermissionsManager');
        stub($projectmember)->getPermissionManager()->returns($permission_manager);

        // Project member is member of only her project
        $this->assertTrue($projectmember->isMember(789));
        $this->assertFalse($projectmember->isMember(789, 'A'));
        $this->assertFalse($projectmember->isMember(456));
        $this->assertFalse($projectmember->isMember(456, 'A'));
        $this->assertFalse($projectmember->isMember(1));
        $this->assertFalse($projectmember->isMember(1, 'A'));
    }

    public function testGetAuthorizedKeysSplitedWith1Key()
    {
        $k1 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAtfKHvNobjjB+cYGue/c/SXUL9Htay'
            .'lfQJWnLiV3AuqnbrWm6l9WGnv6+44/6e38Jwk0ywuvCdM5xi9gtWPN9Cw2S8qLbhVr'
            .'qH9DAhwVR3LRYwr8jMm6enqUEh8pjHuIpcqkTJQJ9pY5D/GCqeOsO3tVF2M+RJuX9Z'
            .'yT7c1FysnHJtiy70W/100LdwJJWYCZNqgh5y02ThiDcbRIPwB8B/vD9n5AIZiyiuHn'
            .'QQp4PLi4+NzCne3C/kOMpI5UVxHlgoJmtx0jr1RpvdfX4cTzCSud0J1F+6g7MWg3YL'
            .'Rp2IZyp88CdZBoUYeW0MNbYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $user = new PFUser(array('language_id'     => 'en_US',
                               'authorized_keys' => $k1));
        $this->assertEqual($user->getAuthorizedKeys(), $k1);
        $res = $user->getAuthorizedKeys(true);
        $this->assertEqual($res[0], $k1);
    }

    function testGetAuthorizedKeysSplitedWith2Keys()
    {
        $k1 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAtfKHvNobjjB+cYGue/c/SXUL9Htay'
            .'lfQJWnLiV3AuqnbrWm6l9WGnv6+44/6e38Jwk0ywuvCdM5xi9gtWPN9Cw2S8qLbhVr'
            .'qH9DAhwVR3LRYwr8jMm6enqUEh8pjHuIpcqkTJQJ9pY5D/GCqeOsO3tVF2M+RJuX9Z'
            .'yT7c1FysnHJtiy70W/100LdwJJWYCZNqgh5y02ThiDcbRIPwB8B/vD9n5AIZiyiuHn'
            .'QQp4PLi4+NzCne3C/kOMpI5UVxHlgoJmtx0jr1RpvdfX4cTzCSud0J1F+6g7MWg3YL'
            .'Rp2IZyp88CdZBoUYeW0MNbYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $k2 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA00qxJHrLEbrVTEtvC9c7xaeNIV81v'
            .'xns7T89tGmyocFlPeD2N+uUQ8J90bcv7+aQDo229EWWI7oV6uGqsFXAuWSHHSvl7Am'
            .'+2/lzVwSkvrVYAKl26Kz505a+W9xMbMKn8B+LFuOg3sjUKeVuz0WiUuKnHhhJUEBW+'
            .'mJtuHrow49+6mOuL5v+M+0FlwGthagQt1zjWvo6g8GC4x97Wt3FVu8cfQJVu7S5KBX'
            .'iz2VjRAwKTovt+M4+PlqO00vWbaaviFirwJPXjHoGVKONa/ahrXYiTICSgWUR6Cjlq'
            .'Hs15cMSFOfkmDimu9KJiaOvfMNDPDGW/HeNUYB7HqYZIRcznQ== marcel@shanon.net';
        $ssh = $k1.PFUser::SSH_KEY_SEPARATOR.$k2;
        $user = new PFUser(array('language_id'     => 'en_US',
                               'authorized_keys' => $ssh));
        $this->assertEqual($user->getAuthorizedKeys(), $ssh);
        $res = $user->getAuthorizedKeys(true);
        $this->assertEqual($res[0], $k1);
        $this->assertEqual($res[1], $k2);
    }

    function testGetAuthorizedKeysSplitedWithEmptyKey()
    {
        $k1 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAtfKHvNobjjB+cYGue/c/SXUL9Htay'
            .'lfQJWnLiV3AuqnbrWm6l9WGnv6+44/6e38Jwk0ywuvCdM5xi9gtWPN9Cw2S8qLbhVr'
            .'qH9DAhwVR3LRYwr8jMm6enqUEh8pjHuIpcqkTJQJ9pY5D/GCqeOsO3tVF2M+RJuX9Z'
            .'yT7c1FysnHJtiy70W/100LdwJJWYCZNqgh5y02ThiDcbRIPwB8B/vD9n5AIZiyiuHn'
            .'QQp4PLi4+NzCne3C/kOMpI5UVxHlgoJmtx0jr1RpvdfX4cTzCSud0J1F+6g7MWg3YL'
            .'Rp2IZyp88CdZBoUYeW0MNbYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $k2 = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA00qxJHrLEbrVTEtvC9c7xaeNIV81v'
            .'xns7T89tGmyocFlPeD2N+uUQ8J90bcv7+aQDo229EWWI7oV6uGqsFXAuWSHHSvl7Am'
            .'+2/lzVwSkvrVYAKl26Kz505a+W9xMbMKn8B+LFuOg3sjUKeVuz0WiUuKnHhhJUEBW+'
            .'mJtuHrow49+6mOuL5v+M+0FlwGthagQt1zjWvo6g8GC4x97Wt3FVu8cfQJVu7S5KBX'
            .'iz2VjRAwKTovt+M4+PlqO00vWbaaviFirwJPXjHoGVKONa/ahrXYiTICSgWUR6Cjlq'
            .'Hs15cMSFOfkmDimu9KJiaOvfMNDPDGW/HeNUYB7HqYZIRcznQ== marcel@shanon.net';
        $user = new PFUser(array('language_id'     => 'en_US',
                               'authorized_keys' => $k1.PFUser::SSH_KEY_SEPARATOR.PFUser::SSH_KEY_SEPARATOR.$k2));
        $res = $user->getAuthorizedKeys(true);
        $this->assertEqual($res[0], $k1);
        $this->assertFalse(isset($res[1]));
        $this->assertEqual($res[2], $k2);
    }

    function testActiveUserCanSeePeopleNotInHisProjects()
    {
        $activeUser = new UserTestVersion2($this);
        $activeUser->setId(123);
        $activeUser->setReturnValue('getUserGroupData', array(101 => array(),
                                                              102 => array()));
        $activeUser->setStatus(PFUser::STATUS_ACTIVE);

        $notProjectMember = new UserTestVersion2($this);
        $notProjectMember->setReturnValue('getUserGroupData', array(103 => array()));

        $this->assertTrue($activeUser->canSee($notProjectMember));
    }

    function testRestrictedUserCanSeePeopleInHisProjects()
    {
        $restrictedUser = new UserTestVersion2($this);
        $restrictedUser->setId(123);
        $restrictedUser->setReturnValue('getUserGroupData', array(101 => array(),
                                                                  102 => array()));
        $restrictedUser->setStatus(PFUser::STATUS_RESTRICTED);

        $otherProjectMember = new UserTestVersion2($this);
        $otherProjectMember->setReturnValue('getUserGroupData', array(102 => array()));

        $this->assertTrue($restrictedUser->canSee($otherProjectMember));
    }

    function testRestrictedUserCannotSeePeopleNotInHisProjects()
    {
        $restrictedUser = new UserTestVersion2($this);
        $restrictedUser->setId(123);
        $restrictedUser->setReturnValue('getUserGroupData', array(101 => array(),
                                                                  102 => array()));
        $restrictedUser->setStatus(PFUser::STATUS_RESTRICTED);

        $notProjectMember = new UserTestVersion2($this);
        $notProjectMember->setReturnValue('getUserGroupData', array(103 => array()));

        $this->assertFalse($restrictedUser->canSee($notProjectMember));
    }

    function testGetAuthorizedKeysSplitedWithoutKey()
    {
        $user = new PFUser(array('language_id'     => 'en_US',
                               'authorized_keys' => ''));
        $res = $user->getAuthorizedKeys(true);
        $this->assertEqual(count($res), 0);
    }

    function testGetAllProjectShouldListOnlyOneOccurenceOfEachProject()
    {
        $user = partial_mock('PFUser', array('getProjects', 'getUGroupDao'));

        $user->setReturnValue('getProjects', array(101, 103));

        $dar = TestHelper::arrayToDar(array('group_id' => 102), array('group_id' => 103), array('group_id' => 104));
        $dao = new MockUGroupDao();
        $dao->setReturnValue('searchGroupByUserId', $dar);
        $user->setReturnValue('getUGroupDao', $dao);

        $this->assertEqual(array(102, 103, 104, 101), $user->getAllProjects());
    }

    function testGetLanguageShouldUserLanguageFactoryIfNotDefined()
    {
        $langFactory = new MockBaseLanguageFactory();
        $langFactory->expectOnce('getBaseLanguage', array('fr_BE'));

        $user = new PFUser(array('language_id' => 'fr_BE'));
        $user->setLanguageFactory($langFactory);
        $user->getLanguage();
    }

    public function itStringifiesTheUser()
    {
        $this->assertEqual("User #123", aUser()->withId(123)->build()->__toString());
    }

    public function itReturnsTrueWhenUserIsAdminOfProjectAdministration()
    {
        $user = partial_mock('PFUser', array('getUserGroupData', 'doesUserHaveSuperUserPermissionDelegation'));
        stub($user)->getUserGroupData()->returns(array(1 => array('admin_flags' => 'A')));
        stub($user)->doesUserHaveSuperUserPermissionDelegation()->returns(false);

        $this->assertTrue($user->isSuperUser());
    }

    public function itReturnsTrueWhenUserHasSiteAdministrationPermissionDelegation()
    {
        $user = partial_mock('PFUser', array('getUserGroupData', 'doesUserHaveSuperUserPermissionDelegation'));
        stub($user)->getUserGroupData()->returns(array());
        stub($user)->doesUserHaveSuperUserPermissionDelegation()->returns(true);

        $this->assertTrue($user->isSuperUser());
    }

    public function itReturnsFalseWhenUserIsNotSiteAdministrator()
    {
        $user = partial_mock('PFUser', array('getUserGroupData', 'doesUserHaveSuperUserPermissionDelegation'));
        stub($user)->getUserGroupData()->returns(array());
        stub($user)->doesUserHaveSuperUserPermissionDelegation()->returns(false);

        $this->assertFalse($user->isSuperUser());
    }
}

class UserTogglePreference_Test extends TuleapTestCase
{

    private $user_id         = 101;
    private $preference_name = 'cardwall';
    private $default_value   = 'display_avatars';
    private $alternate_value = 'display_usernames';

    public function setUp()
    {
        $this->user = partial_mock(
            'PFUser',
            array('getPreferencesDao'),
            array(
                array(
                    'user_id'     => $this->user_id,
                    'language_id' => 1
                )
            )
        );
        $this->dao = mock('UserPreferencesDao');
        stub($this->user)->getPreferencesDao()->returns($this->dao);
    }

    public function itSetTheAlternateValueWhenPreferenceIsTheDefaultOne()
    {
        stub($this->dao)->search($this->user_id, $this->preference_name)->returnsDar(array(
            'user_id'          => $this->user_id,
            'preference_name'  => $this->preference_name,
            'preference_value' => $this->default_value
        ));

        expect($this->dao)->set($this->user_id, $this->preference_name, $this->alternate_value)->once();

        $this->user->togglePreference($this->preference_name, $this->default_value, $this->alternate_value);
    }

    public function itSetTheDefaultValueWhenPreferenceIsTheAlternateOne()
    {
        stub($this->dao)->search($this->user_id, $this->preference_name)->returnsDar(array(
            'user_id'          => $this->user_id,
            'preference_name'  => $this->preference_name,
            'preference_value' => $this->alternate_value
        ));

        expect($this->dao)->set($this->user_id, $this->preference_name, $this->default_value)->once();

        $this->user->togglePreference($this->preference_name, $this->default_value, $this->alternate_value);
    }

    public function itSetTheDefaultValueWhenNoPreference()
    {
        stub($this->dao)->search($this->user_id, $this->preference_name)->returnsEmptyDar();

        expect($this->dao)->set($this->user_id, $this->preference_name, $this->default_value)->once();

        $this->user->togglePreference($this->preference_name, $this->default_value, $this->alternate_value);
    }
}
