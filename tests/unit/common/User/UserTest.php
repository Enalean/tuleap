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
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testStatus(): void
    {
        $u1 = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $u1->shouldReceive('getStatus')->andReturns('A');
        $u2 = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $u2->shouldReceive('getStatus')->andReturns('S');
        $u3 = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $u3->shouldReceive('getStatus')->andReturns('D');
        $u4 = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $u4->shouldReceive('isAnonymous')->andReturns(false);
        $u4->shouldReceive('getStatus')->andReturns('R');
        $u4 = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $u4->shouldReceive('isAnonymous')->andReturns(true);
        $u4->shouldReceive('getStatus')->andReturns('R');

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

    public function testPreferences(): void
    {
        $dao = \Mockery::spy(\UserPreferencesDao::class);

        $empty_dar = [];
        $dar       = ['preference_value' => '123'];

        $dao->shouldReceive('search')->with(666, 'unexisting_preference')->andReturns($empty_dar);
        $dao->shouldReceive('search')->with(666, 'existing_preference')->andReturns($dar);
        $dao->shouldReceive('set')->with(666, 'existing_preference', '456')->once()->andReturns(true);
        $dao->shouldReceive('delete')->with(666, 'existing_preference')->once()->andReturns(true);

        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getPreferencesDao')->andReturns($dao);
        $user->shouldReceive('getId')->andReturns(666);

        $this->assertFalse($user->getPreference('unexisting_preference'), 'Unexisting preference, should return false');
        $this->assertEquals('123', $user->getPreference('existing_preference'), 'Existing preference should return 123');
        $this->assertEquals('123', $user->getPreference('existing_preference'), 'Existing preference should return 123, should be cached');
        $user->setPreference('existing_preference', '456');
        $this->assertEquals('456', $user->getPreference('existing_preference'), 'Existing preference has been updated, should now return 456. No call to dao since cached during update');
        $user->delPreference('existing_preference');
        $this->assertFalse($user->getPreference('existing_preference'), 'Preferences has been deleted. No call to dao since cached during delete');
    }

    public function testNone(): void
    {
        $user_none = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user_none->shouldReceive('getId')->andReturns(100);
        $this->assertTrue($user_none->isNone());

        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getId')->andReturns(666);
        $this->assertFalse($user->isNone());
    }

    public function testIsMemberSiteAdmin(): void
    {
        $siteadmin    = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
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
        $siteadmin->shouldReceive('getUserGroupData')->andReturns($ug_siteadmin);

        $this->assertTrue($siteadmin->isMember(1));
        $this->assertTrue($siteadmin->isMember(1, 'A'));
        // Site admin is member and admin of any project
        $this->assertTrue($siteadmin->isMember(123));
        $this->assertTrue($siteadmin->isMember(123, 'A'));
    }

    public function testIsMemberProjectAdmin(): void
    {
        $projectadmin     = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
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
        $projectadmin->shouldReceive('getUserGroupData')->andReturns($ug_project_admin);
        $permission_manager = \Mockery::spy(\User_ForgeUserGroupPermissionsManager::class);
        $projectadmin->shouldReceive('getPermissionManager')->andReturns($permission_manager);

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
    public function testIsMemberProjectMember(): void
    {
        $projectmember     = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
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
        $projectmember->shouldReceive('getUserGroupData')->andReturns($ug_project_member);
        $permission_manager = \Mockery::spy(\User_ForgeUserGroupPermissionsManager::class);
        $projectmember->shouldReceive('getPermissionManager')->andReturns($permission_manager);

        // Project member is member of only her project
        $this->assertTrue($projectmember->isMember(789));
        $this->assertFalse($projectmember->isMember(789, 'A'));
        $this->assertFalse($projectmember->isMember(456));
        $this->assertFalse($projectmember->isMember(456, 'A'));
        $this->assertFalse($projectmember->isMember(1));
        $this->assertFalse($projectmember->isMember(1, 'A'));
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
        $this->assertEquals($user->getAuthorizedKeysRaw(), $k1);
        $res = $user->getAuthorizedKeysArray();
        $this->assertEquals($res[0], $k1);
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
        $this->assertEquals($user->getAuthorizedKeysRaw(), $ssh);
        $res = $user->getAuthorizedKeysArray();
        $this->assertEquals($k1, $res[0]);
        $this->assertEquals($k2, $res[1]);
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
        $this->assertEquals($k1, $res[0]);
        $this->assertFalse(isset($res[1]));
        $this->assertEquals($k2, $res[2]);
    }

    public function testGetAuthorizedKeysSplitedWithoutKey(): void
    {
        $user = new PFUser(['language_id'     => 'en_US',
            'authorized_keys' => '',
        ]);
        $res  = $user->getAuthorizedKeysArray();
        $this->assertCount(0, $res);
    }

    public function testGetAllProjectShouldListOnlyOneOccurenceOfEachProject(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $user->shouldReceive('getProjects')->andReturns([101, 103]);

        $dar = TestHelper::arrayToDar(['group_id' => 102], ['group_id' => 103], ['group_id' => 104]);
        $dao = \Mockery::spy(\UGroupDao::class);
        $dao->shouldReceive('searchGroupByUserId')->andReturns($dar);
        $user->shouldReceive('getUGroupDao')->andReturns($dao);

        $this->assertEquals([102, 103, 104, 101], $user->getAllProjects());
    }

    public function testGetLanguageShouldUserLanguageFactoryIfNotDefined(): void
    {
        $langFactory = \Mockery::spy(\BaseLanguageFactory::class);
        $langFactory->shouldReceive('getBaseLanguage')->with('fr_BE')->once();

        $user = new PFUser(['language_id' => 'fr_BE']);
        $user->setLanguageFactory($langFactory);
        $user->getLanguage();
    }

    public function testItStringifiesTheUser(): void
    {
        $user = new PFUser(['user_id' => 123, 'language_id' => 'en_US']);
        $this->assertEquals("User #123", (string) $user);
    }

    public function testItReturnsTrueWhenUserIsAdminOfProjectAdministration(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getUserGroupData')->andReturns([1 => ['admin_flags' => 'A']]);
        $user->shouldReceive('doesUserHaveSuperUserPermissionDelegation')->andReturns(false);

        $this->assertTrue($user->isSuperUser());
    }

    public function testItReturnsTrueWhenUserHasSiteAdministrationPermissionDelegation(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getUserGroupData')->andReturns([]);
        $user->shouldReceive('doesUserHaveSuperUserPermissionDelegation')->andReturns(true);

        $this->assertTrue($user->isSuperUser());
    }

    public function testItReturnsFalseWhenUserIsNotSiteAdministrator(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user->shouldReceive('getUserGroupData')->andReturns([]);
        $user->shouldReceive('doesUserHaveSuperUserPermissionDelegation')->andReturns(false);

        $this->assertFalse($user->isSuperUser());
    }

    public function testItSetTheAlternateValueWhenPreferenceIsTheDefaultOne(): void
    {
        $user_id = 101;
        $user    = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao     = \Mockery::mock(\UserPreferencesDao::class);
        $user->shouldReceive('getPreferencesDao')->andReturns($dao);

        $this->expectNotToPerformAssertions();

        $dao->shouldReceive('search')->with($user_id, 'pref_name')->andReturns(\TestHelper::arrayToDar([
            'user_id'          => $user_id,
            'preference_name'  => 'pref_name',
            'preference_value' => 'default_value',
        ]));

        $user->togglePreference('pref_name', 'default_value', 'alternate_value');
    }

    public function testItSetTheDefaultValueWhenPreferenceIsTheAlternateOne(): void
    {
        $user_id = 101;
        $user    = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao     = \Mockery::mock(\UserPreferencesDao::class);
        $user->shouldReceive('getPreferencesDao')->andReturns($dao);

        $this->expectNotToPerformAssertions();

        $dao->shouldReceive('search')->with($user_id, 'pref_name')->andReturns(\TestHelper::arrayToDar([
            'user_id'          => $user_id,
            'preference_name'  => 'pref_name',
            'preference_value' => 'alt_value',
        ]));

        $user->togglePreference('pref_name', 'default_value', 'alt_value');
    }

    public function testItSetTheDefaultValueWhenNoPreference(): void
    {
        $user = \Mockery::mock(\PFUser::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao  = \Mockery::mock(\UserPreferencesDao::class);
        $user->shouldReceive('getPreferencesDao')->andReturns($dao);

        $this->expectNotToPerformAssertions();

        $dao->shouldReceive('search')->with(101, 'pref_name')->andReturns(\TestHelper::emptyDar());

        $user->togglePreference('pref_name', 'default_value', 'alt_value');
    }
}
