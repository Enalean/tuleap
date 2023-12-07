<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

use SystemEvent;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testUpdateLdapUidShouldPrepareRenameOfUserInTheWholePlatform(): void
    {
        // Parameters
        $user     = UserTestBuilder::aUser()->withId(105)->build();
        $ldap_uid = 'johndoe';

        $lum = $this->getMockBuilder(\LDAP_UserManager::class)
            ->onlyMethods(['getDao'])
            ->disableOriginalConstructor()
            ->getMock();

        $dao = new class {
            public function updateLdapUid(int $user_id, string $ldap_uid): void
            {
            }
        };
        $lum->method('getDao')->willReturn($dao);

        $lum->updateLdapUid($user, $ldap_uid);
        self::assertEquals($lum->getUsersToRename(), [$user]);
    }

    public function testTriggerRenameOfUsersShouldUpdateSVNAccessFileOfProjectWhereTheUserIsMember(): void
    {
        // Parameters
        $user = UserTestBuilder::aUser()->withId(105)->build();

        $lum = $this->getMockBuilder(\LDAP_UserManager::class)
            ->onlyMethods(['getSystemEventManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $sem = $this->createMock(\SystemEventManager::class);
        $sem->expects(self::once())->method('createEvent')->with('PLUGIN_LDAP_UPDATE_LOGIN', '105', SystemEvent::PRIORITY_MEDIUM);
        $lum->method('getSystemEventManager')->willReturn($sem);

        $lum->addUserToRename($user);

        $lum->triggerRenameOfUsers();
    }

    public function testTriggerRenameOfUsersWithSeveralUsers(): void
    {
        $user1 = UserTestBuilder::aUser()->withId(101)->build();
        $user2 = UserTestBuilder::aUser()->withId(102)->build();
        $user3 = UserTestBuilder::aUser()->withId(103)->build();

        $lum = $this->getMockBuilder(\LDAP_UserManager::class)
            ->onlyMethods(['getSystemEventManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $sem = $this->createMock(\SystemEventManager::class);
        $sem->expects(self::once())->method('createEvent')->with('PLUGIN_LDAP_UPDATE_LOGIN', '101' . SystemEvent::PARAMETER_SEPARATOR . '102' . SystemEvent::PARAMETER_SEPARATOR . '103', SystemEvent::PRIORITY_MEDIUM);
        $lum->method('getSystemEventManager')->willReturn($sem);

        $lum->addUserToRename($user1);
        $lum->addUserToRename($user2);
        $lum->addUserToRename($user3);

        $lum->triggerRenameOfUsers();
    }

    public function testTriggerRenameOfUsersWithoutUser(): void
    {
        $lum = $this->getMockBuilder(\LDAP_UserManager::class)
            ->onlyMethods(['getSystemEventManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $sem = $this->createMock(\SystemEventManager::class);
        $sem->expects(self::never())->method('createEvent');
        $lum->method('getSystemEventManager')->willReturn($sem);

        $lum->triggerRenameOfUsers();
    }
}
