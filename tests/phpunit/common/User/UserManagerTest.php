<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;
use Tuleap\CookieManager;

class UserManagerTest extends TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testLogout()
    {
        $cm               = \Mockery::mock(CookieManager::class);
        $cm->shouldReceive('getCookie')->andReturns('valid_hash');
        $cm->shouldReceive('removeCookie')->with('session_hash')->once();

        $user123 = \Mockery::mock(PFUser::class, [
            'getId'          => 123,
            'getUserName'    => 'user_123',
            'getSessionHash' => 'valid_hash',
            'isAnonymous'    => false,
            'isSuspended'    => false,
            'isDeleted'      => false,
        ]);

        $session_manager  = \Mockery::mock(\Tuleap\User\SessionManager::class, ['getUser' => $user123]);
        $session_manager->shouldReceive('destroyCurrentSession')->with($user123)->once();

        $um               = \Mockery::mock(UserManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $um->shouldReceive([
            'getDao'            => \Mockery::spy(UserDao::class),
            'getCookieManager'  => $cm,
            'getSessionManager' => $session_manager,
            '_getEventManager'  => \Mockery::spy(EventManager::class)
        ]);
        $um->shouldReceive('getUserInstanceFromRow')->with(['user_name' => 'user_123', 'user_id' => 123])->andReturn($user123);

        $um->shouldReceive('destroySession')->once();

        $um->logout();
    }
}
