<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\CookieManager;

final class UserManagerTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testLogout(): void
    {
        $cookie_manager = $this->createMock(CookieManager::class);
        $cookie_manager->method('getCookie')->willReturn('valid_hash');
        $cookie_manager->expects(self::once())->method('removeCookie')->with('session_hash');

        $user123 = $this->createMock(PFUser::class);
        $user123->method('getId')->willReturn(123);
        $user123->method('getUserName')->willReturn('user_123');
        $user123->method('getSessionHash')->willReturn('valid_hash');
        $user123->method('isAnonymous')->willReturn(false);
        $user123->method('isSuspended')->willReturn(false);
        $user123->method('isDeleted')->willReturn(false);
        $user123->method('isFirstTimer')->willReturn(false);

        $session_manager = $this->createMock(\Tuleap\User\SessionManager::class);
        $session_manager->method('getUser')->willReturn($user123);
        $session_manager->expects(self::once())->method('destroyCurrentSession')->with($user123);

        $user_manager = $this->getMockBuilder(UserManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getDao',
                'getCookieManager',
                'getSessionManager',
                '_getEventManager',
                'getUserInstanceFromRow',
                'destroySession',
            ])
            ->getMock();

        $user_dao = $this->createMock(UserDao::class);
        $user_dao->method('getUserAccessInfo')->willReturn([]);
        $user_dao->method('storeLastAccessDate');

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('processEvent');

        $user_manager->method('getDao')->willReturn($user_dao);
        $user_manager->method('getCookieManager')->willReturn($cookie_manager);
        $user_manager->method('getSessionManager')->willReturn($session_manager);
        $user_manager->method('_getEventManager')->willReturn($event_manager);
        $user_manager->method('getUserInstanceFromRow')->with(['user_name' => 'user_123', 'user_id' => 123])->willReturn($user123);
        $user_manager->expects(self::once())->method('destroySession');

        $user_manager->logout();
    }
}
