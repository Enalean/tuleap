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

namespace Tuleap\Docman\Metadata\Owner;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use UserManager;

final class OwnerRetrieverTest extends TestCase
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

    protected function setUp(): void
    {
        $this->user_manager = Mockery::mock(UserManager::class);

        $this->owner_check = new OwnerRetriever($this->user_manager);
    }

    public function testGetOwnerIdFromLoginRetrievesCorrectUser(): void
    {
        $user_login = 'peraltaj';
        $user       = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAlive')->AndReturn(true);

        $this->user_manager->shouldReceive('findUser')->with($user_login)->andReturn($user);
        $this->user_manager->shouldReceive('getUserById')->with(10)->andReturn($user);

        $user->shouldReceive('getId')->andReturn(10);

        $new_owner_id = $this->owner_check->getOwnerIdFromLoginName($user_login);

        $this->assertEquals(10, $new_owner_id);
        $this->assertSame($user, $this->owner_check->getUserFromRepresentationId(10));
    }

    public function testUserNeedsToBeFoundToBeMarkedAsOwner(): void
    {
        $this->user_manager->shouldReceive('findUser')->andReturn(null);
        $this->user_manager->shouldReceive('getUserById')->andReturn(null);

        $this->assertNull($this->owner_check->getOwnerIdFromLoginName('Anonymous'));
        $this->assertNull($this->owner_check->getUserFromRepresentationId(0));
    }

    public function testNotAliveUserCanNotBeConsideredAsOwner(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAlive')->AndReturn(false);

        $this->user_manager->shouldReceive('findUser')->andReturn($user);
        $this->user_manager->shouldReceive('getUserById')->andReturn($user);

        $user->shouldReceive('getId')->never();

        $this->assertNull($this->owner_check->getOwnerIdFromLoginName('peraltaj'));
        $this->assertNull($this->owner_check->getUserFromRepresentationId(10));
    }
}
