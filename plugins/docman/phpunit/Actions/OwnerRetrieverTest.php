<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Actions;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use UserManager;
use UserNotAuthorizedException;
use UserNotExistException;

class OwnerRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var OwnerRetriever
     */
    private $owner_check;

    public function setUp(): void
    {
        parent::setUp();

        $this->user_manager = Mockery::mock(UserManager::class);

        $this->owner_check = new OwnerRetriever($this->user_manager);
    }

    public function testGetOwnerIdFromLoginRetrievesCorrectUser(): void
    {
        $user_login = 'peraltaj';
        $user       = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturn(false);
        $user->shouldReceive('isActive')->AndReturn(true);
        $user->shouldReceive('isRestricted')->AndReturn(false);

        $this->user_manager->shouldReceive('findUser')->with($user_login)->andReturn($user);

        $user->shouldReceive('getId')->andReturn(10);

        $new_owner_id = $this->owner_check->getOwnerIdFromLoginName($user_login);

        $this->assertEquals(10, $new_owner_id);
    }

    public function testItCheckRestrictedOwnerChangeAndReturnTheNewOwnerId(): void
    {
        $user_login = 'peraltaj';
        $user       = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturn(false);
        $user->shouldReceive('isActive')->AndReturn(false);
        $user->shouldReceive('isRestricted')->AndReturn(true);

        $this->user_manager->shouldReceive('findUser')->with($user_login)->andReturn($user);

        $user->shouldReceive('getId')->andReturn(10);

        $new_owner_id = $this->owner_check->getOwnerIdFromLoginName($user_login);

        $this->assertEquals(10, $new_owner_id);
    }

    public function testItThrowsExceptionIfTheNewOwnerIsAnonymous(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturn(true);
        $user->shouldReceive('isActive')->AndReturn(false);
        $user->shouldReceive('isRestricted')->AndReturn(true);

        $this->user_manager->shouldReceive('findUser')->andReturn($user);

        $user->shouldReceive('getId')->never();

        $this->expectException(UserNotAuthorizedException::class);

        $this->owner_check->getOwnerIdFromLoginName('Anonymous');
    }

    public function testItThrowsExceptionIfTheNewOwnerDoesNotExist(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->never();
        $user->shouldReceive('isActive')->never();
        $user->shouldReceive('isRestricted')->never();

        $this->user_manager->shouldReceive('findUser')->andReturn(null);

        $user->shouldReceive('getId')->never();

        $this->expectException(UserNotExistException::class);

        $this->owner_check->getOwnerIdFromLoginName('Anonymous');
    }

    public function testItThrowsExceptionIfTheNewOwnerIsNotActiveAndNotRestricted(): void
    {
        $user_login = 'peraltaj';
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturn(false);
        $user->shouldReceive('isActive')->AndReturn(false);
        $user->shouldReceive('isRestricted')->AndReturn(false);

        $this->user_manager->shouldReceive('findUser')->andReturn($user);

        $user->shouldReceive('getId')->never();

        $this->expectException(UserNotAuthorizedException::class);

        $this->owner_check->getOwnerIdFromLoginName($user_login);
    }
}
