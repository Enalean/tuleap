<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class UserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testStatus(): void
    {
        $u1 = \Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build();
        $u2 = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withStatus('S')->build();
        $u3 = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withStatus('D')->build();
        $u4 = \Tuleap\Test\Builders\UserTestBuilder::aRestrictedUser()->build();

        self::assertTrue($u1->isActive());
        self::assertFalse($u1->isSuspended());
        self::assertFalse($u1->isDeleted());
        self::assertFalse($u1->isRestricted());

        self::assertFalse($u2->isActive());
        self::assertTrue($u2->isSuspended());
        self::assertFalse($u2->isDeleted());
        self::assertFalse($u2->isRestricted());

        self::assertFalse($u3->isActive());
        self::assertFalse($u3->isSuspended());
        self::assertTrue($u3->isDeleted());
        self::assertFalse($u3->isRestricted());

        self::assertFalse($u4->isActive());
        self::assertFalse($u4->isSuspended());
        self::assertFalse($u4->isDeleted());
        self::assertTrue($u4->isRestricted());
    }

    public function testPreferences(): void
    {
        $dao = $this->createMock(\UserPreferencesDao::class);

        $empty_dar = [];
        $dar       = ['preference_value' => '123'];

        $dao->method('search')->willReturnMap([
            [666, 'unexisting_preference', $empty_dar],
            [666, 'existing_preference', $dar],
        ]);
        $dao->expects(self::once())->method('set')->with(666, 'existing_preference', '456');
        $dao->expects(self::once())->method('delete')->with(666, 'existing_preference');

        $user = $this->getMockBuilder(PFUser::class)->disableOriginalConstructor()->onlyMethods(['getPreferencesDao', 'getId'])->getMock();
        $user->method('getPreferencesDao')->willReturn($dao);
        $user->method('getId')->willReturn(666);

        self::assertFalse($user->getPreference('unexisting_preference'), 'Unexisting preference, should return false');
        self::assertEquals('123', $user->getPreference('existing_preference'), 'Existing preference should return 123');
        self::assertEquals('123', $user->getPreference('existing_preference'), 'Existing preference should return 123, should be cached');
        $user->setPreference('existing_preference', '456');
        self::assertEquals('456', $user->getPreference('existing_preference'), 'Existing preference has been updated, should now return 456. No call to dao since cached during update');
        $user->delPreference('existing_preference');
        self::assertFalse($user->getPreference('existing_preference'), 'Preferences has been deleted. No call to dao since cached during delete');
    }

    public function testNone(): void
    {
        $user_none = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withId(100)->build();
        self::assertTrue($user_none->isNone());

        $user = \Tuleap\Test\Builders\UserTestBuilder::aUser()->withId(666)->build();
        self::assertFalse($user->isNone());
    }

    public function testIsMemberSiteAdmin(): void
    {
        $siteadmin = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserGroupData'])
            ->getMock();

        $ug_siteadmin = [
            '1' => [
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
                'news_flags' => '2',
            ],
        ];
        $siteadmin->method('getUserGroupData')->willReturn($ug_siteadmin);

        self::assertTrue($siteadmin->isMember(1));
        self::assertTrue($siteadmin->isMember(1, 'A'));
        // Site admin is member and admin of any project
        self::assertTrue($siteadmin->isMember(123));
        self::assertTrue($siteadmin->isMember(123, 'A'));
    }

    public function testIsMemberProjectAdmin(): void
    {
        $projectadmin = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserGroupData', 'getPermissionManager', 'doesUserHaveSuperUserPermissionDelegation'])
            ->getMock();

        $ug_project_admin = [
            '123' => [
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
                'news_flags'    => '2',
            ],
        ];
        $projectadmin->method('getUserGroupData')->willReturn($ug_project_admin);
        $permission_manager = $this->createMock(\User_ForgeUserGroupPermissionsManager::class);
        $projectadmin->method('getPermissionManager')->willReturn($permission_manager);
        $projectadmin->method('doesUserHaveSuperUserPermissionDelegation')->willReturn(false);

        // Project admin is member and admin of only her projects
        self::assertTrue($projectadmin->isMember(123));
        self::assertTrue($projectadmin->isMember(123, 'A'));
        self::assertFalse($projectadmin->isMember(456));
        self::assertFalse($projectadmin->isMember(456, 'A'));
        self::assertFalse($projectadmin->isMember(1));
        self::assertFalse($projectadmin->isMember(1, 'A'));
    }

    /**
     * This test reproduce bug #20456 on codex.xerox.com
     */
    public function testIsMemberProjectMember(): void
    {
        $projectmember = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserGroupData', 'getPermissionManager', 'doesUserHaveSuperUserPermissionDelegation'])
            ->getMock();

        $ug_project_member = [
            '789' => [
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
                'news_flags'    => '2',
            ],
        ];
        $projectmember->method('getUserGroupData')->willReturn($ug_project_member);
        $permission_manager = $this->createMock(\User_ForgeUserGroupPermissionsManager::class);
        $projectmember->method('getPermissionManager')->willReturn($permission_manager);
        $projectmember->method('doesUserHaveSuperUserPermissionDelegation')->willReturn(false);

        // Project member is member of only her project
        self::assertTrue($projectmember->isMember(789));
        self::assertFalse($projectmember->isMember(789, 'A'));
        self::assertFalse($projectmember->isMember(456));
        self::assertFalse($projectmember->isMember(456, 'A'));
        self::assertFalse($projectmember->isMember(1));
        self::assertFalse($projectmember->isMember(1, 'A'));
    }

    public function testGetAuthorizedKeysSplitedWith1Key(): void
    {
        $k1   = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAtfKHvNobjjB+cYGue/c/SXUL9Htay'
            . 'lfQJWnLiV3AuqnbrWm6l9WGnv6+44/6e38Jwk0ywuvCdM5xi9gtWPN9Cw2S8qLbhVr'
            . 'qH9DAhwVR3LRYwr8jMm6enqUEh8pjHuIpcqkTJQJ9pY5D/GCqeOsO3tVF2M+RJuX9Z'
            . 'yT7c1FysnHJtiy70W/100LdwJJWYCZNqgh5y02ThiDcbRIPwB8B/vD9n5AIZiyiuHn'
            . 'QQp4PLi4+NzCne3C/kOMpI5UVxHlgoJmtx0jr1RpvdfX4cTzCSud0J1F+6g7MWg3YL'
            . 'Rp2IZyp88CdZBoUYeW0MNbYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $user = new PFUser(['language_id'     => 'en_US',
            'authorized_keys' => $k1,
        ]);
        self::assertEquals($user->getAuthorizedKeysRaw(), $k1);
        $res = $user->getAuthorizedKeysArray();
        self::assertEquals($res[0], $k1);
    }

    public function testGetAuthorizedKeysSplitedWith2Keys(): void
    {
        $k1   = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAtfKHvNobjjB+cYGue/c/SXUL9Htay'
            . 'lfQJWnLiV3AuqnbrWm6l9WGnv6+44/6e38Jwk0ywuvCdM5xi9gtWPN9Cw2S8qLbhVr'
            . 'qH9DAhwVR3LRYwr8jMm6enqUEh8pjHuIpcqkTJQJ9pY5D/GCqeOsO3tVF2M+RJuX9Z'
            . 'yT7c1FysnHJtiy70W/100LdwJJWYCZNqgh5y02ThiDcbRIPwB8B/vD9n5AIZiyiuHn'
            . 'QQp4PLi4+NzCne3C/kOMpI5UVxHlgoJmtx0jr1RpvdfX4cTzCSud0J1F+6g7MWg3YL'
            . 'Rp2IZyp88CdZBoUYeW0MNbYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $k2   = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA00qxJHrLEbrVTEtvC9c7xaeNIV81v'
            . 'xns7T89tGmyocFlPeD2N+uUQ8J90bcv7+aQDo229EWWI7oV6uGqsFXAuWSHHSvl7Am'
            . '+2/lzVwSkvrVYAKl26Kz505a+W9xMbMKn8B+LFuOg3sjUKeVuz0WiUuKnHhhJUEBW+'
            . 'mJtuHrow49+6mOuL5v+M+0FlwGthagQt1zjWvo6g8GC4x97Wt3FVu8cfQJVu7S5KBX'
            . 'iz2VjRAwKTovt+M4+PlqO00vWbaaviFirwJPXjHoGVKONa/ahrXYiTICSgWUR6Cjlq'
            . 'Hs15cMSFOfkmDimu9KJiaOvfMNDPDGW/HeNUYB7HqYZIRcznQ== marcel@shanon.net';
        $ssh  = $k1 . PFUser::SSH_KEY_SEPARATOR . $k2;
        $user = new PFUser(['language_id'     => 'en_US',
            'authorized_keys' => $ssh,
        ]);
        self::assertEquals($user->getAuthorizedKeysRaw(), $ssh);
        $res = $user->getAuthorizedKeysArray();
        self::assertEquals($k1, $res[0]);
        self::assertEquals($k2, $res[1]);
    }

    public function testGetAuthorizedKeysSplitedWithEmptyKey(): void
    {
        $k1   = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAtfKHvNobjjB+cYGue/c/SXUL9Htay'
            . 'lfQJWnLiV3AuqnbrWm6l9WGnv6+44/6e38Jwk0ywuvCdM5xi9gtWPN9Cw2S8qLbhVr'
            . 'qH9DAhwVR3LRYwr8jMm6enqUEh8pjHuIpcqkTJQJ9pY5D/GCqeOsO3tVF2M+RJuX9Z'
            . 'yT7c1FysnHJtiy70W/100LdwJJWYCZNqgh5y02ThiDcbRIPwB8B/vD9n5AIZiyiuHn'
            . 'QQp4PLi4+NzCne3C/kOMpI5UVxHlgoJmtx0jr1RpvdfX4cTzCSud0J1F+6g7MWg3YL'
            . 'Rp2IZyp88CdZBoUYeW0MNbYZi1ju3FeZu6EKKltZ0uftOfj6w== marcel@labobine.net';
        $k2   = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA00qxJHrLEbrVTEtvC9c7xaeNIV81v'
            . 'xns7T89tGmyocFlPeD2N+uUQ8J90bcv7+aQDo229EWWI7oV6uGqsFXAuWSHHSvl7Am'
            . '+2/lzVwSkvrVYAKl26Kz505a+W9xMbMKn8B+LFuOg3sjUKeVuz0WiUuKnHhhJUEBW+'
            . 'mJtuHrow49+6mOuL5v+M+0FlwGthagQt1zjWvo6g8GC4x97Wt3FVu8cfQJVu7S5KBX'
            . 'iz2VjRAwKTovt+M4+PlqO00vWbaaviFirwJPXjHoGVKONa/ahrXYiTICSgWUR6Cjlq'
            . 'Hs15cMSFOfkmDimu9KJiaOvfMNDPDGW/HeNUYB7HqYZIRcznQ== marcel@shanon.net';
        $user = new PFUser(['language_id'     => 'en_US',
            'authorized_keys' => $k1 . PFUser::SSH_KEY_SEPARATOR . PFUser::SSH_KEY_SEPARATOR . $k2,
        ]);
        $res  = $user->getAuthorizedKeysArray();
        self::assertEquals($k1, $res[0]);
        self::assertFalse(isset($res[1]));
        self::assertEquals($k2, $res[2]);
    }

    public function testGetAuthorizedKeysSplitedWithoutKey(): void
    {
        $user = new PFUser(['language_id'     => 'en_US',
            'authorized_keys' => '',
        ]);
        $res  = $user->getAuthorizedKeysArray();
        self::assertCount(0, $res);
    }

    public function testGetAllProjectShouldListOnlyOneOccurenceOfEachProject(): void
    {
        $user = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProjects', 'getUGroupDao'])
            ->getMock();

        $user->method('getProjects')->willReturn([101, 103]);

        $dar = TestHelper::arrayToDar(['group_id' => 102], ['group_id' => 103], ['group_id' => 104]);
        $dao = $this->createMock(\UGroupDao::class);
        $dao->method('searchGroupByUserId')->willReturn($dar);
        $user->method('getUGroupDao')->willReturn($dao);

        self::assertEquals([102, 103, 104, 101], $user->getAllProjects());
    }

    public function testGetLanguageShouldUserLanguageFactoryIfNotDefined(): void
    {
        $langFactory = $this->createMock(\BaseLanguageFactory::class);
        $langFactory->expects(self::once())->method('getBaseLanguage')->with('fr_BE');

        $user = new PFUser(['language_id' => 'fr_BE']);
        $user->setLanguageFactory($langFactory);
        $user->getLanguage();
    }

    public function testItStringifiesTheUser(): void
    {
        $user = new PFUser(['user_id' => 123, 'language_id' => 'en_US']);
        self::assertEquals("User #123", (string) $user);
    }

    public function testItReturnsTrueWhenUserIsAdminOfProjectAdministration(): void
    {
        $user = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserGroupData', 'doesUserHaveSuperUserPermissionDelegation'])
            ->getMock();
        $user->method('getUserGroupData')->willReturn([1 => ['admin_flags' => 'A']]);
        $user->method('doesUserHaveSuperUserPermissionDelegation')->willReturn(false);

        self::assertTrue($user->isSuperUser());
    }

    public function testItReturnsTrueWhenUserHasSiteAdministrationPermissionDelegation(): void
    {
        $user = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserGroupData', 'doesUserHaveSuperUserPermissionDelegation'])
            ->getMock();
        $user->method('getUserGroupData')->willReturn([]);
        $user->method('doesUserHaveSuperUserPermissionDelegation')->willReturn(true);

        self::assertTrue($user->isSuperUser());
    }

    public function testItReturnsFalseWhenUserIsNotSiteAdministrator(): void
    {
        $user = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUserGroupData', 'doesUserHaveSuperUserPermissionDelegation'])
            ->getMock();
        $user->method('getUserGroupData')->willReturn([]);
        $user->method('doesUserHaveSuperUserPermissionDelegation')->willReturn(false);

        self::assertFalse($user->isSuperUser());
    }

    public function testItSetTheAlternateValueWhenPreferenceIsTheDefaultOne(): void
    {
        $user_id = 101;
        $user    = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPreferencesDao'])
            ->getMock();
        $dao     = $this->createMock(\UserPreferencesDao::class);
        $user->method('getPreferencesDao')->willReturn($dao);

        $this->expectNotToPerformAssertions();

        $dao->method('search')->with($user_id, 'pref_name')->willReturn([
            'user_id'          => $user_id,
            'preference_name'  => 'pref_name',
            'preference_value' => 'default_value',
        ]);

        $user->togglePreference('pref_name', 'default_value', 'alternate_value');
    }

    public function testItSetTheDefaultValueWhenPreferenceIsTheAlternateOne(): void
    {
        $user_id = 101;
        $user    = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPreferencesDao'])
            ->getMock();
        $dao     = $this->createMock(\UserPreferencesDao::class);
        $user->method('getPreferencesDao')->willReturn($dao);

        $this->expectNotToPerformAssertions();

        $dao->method('search')->with($user_id, 'pref_name')->willReturn([
            'user_id'          => $user_id,
            'preference_name'  => 'pref_name',
            'preference_value' => 'alt_value',
        ]);

        $user->togglePreference('pref_name', 'default_value', 'alt_value');
    }

    public function testItSetTheDefaultValueWhenNoPreference(): void
    {
        $user = $this->getMockBuilder(PFUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPreferencesDao'])
            ->getMock();
        $dao  = $this->createMock(\UserPreferencesDao::class);
        $user->method('getPreferencesDao')->willReturn($dao);

        $this->expectNotToPerformAssertions();

        $dao->method('search')->with(101, 'pref_name')->willReturn([]);

        $user->togglePreference('pref_name', 'default_value', 'alt_value');
    }
}
