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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OwnerRetrieverTest extends TestCase
{
    private UserManager&MockObject $user_manager;
    private OwnerRetriever $owner_check;

    protected function setUp(): void
    {
        $this->user_manager = $this->createMock(UserManager::class);
        $this->owner_check  = new OwnerRetriever($this->user_manager);
    }

    public function testGetOwnerIdFromLoginRetrievesCorrectUser(): void
    {
        $user_login = 'peraltaj';
        $user       = UserTestBuilder::anActiveUser()->withId(10)->build();

        $this->user_manager->method('findUser')->with($user_login)->willReturn($user);
        $this->user_manager->method('getUserById')->with(10)->willReturn($user);

        $new_owner_id = $this->owner_check->getOwnerIdFromLoginName($user_login);

        self::assertEquals(10, $new_owner_id);
        self::assertSame($user, $this->owner_check->getUserFromRepresentationId(10));
    }

    public function testUserNeedsToBeFoundToBeMarkedAsOwner(): void
    {
        $this->user_manager->method('findUser')->willReturn(null);
        $this->user_manager->method('getUserById')->willReturn(null);

        self::assertNull($this->owner_check->getOwnerIdFromLoginName('Anonymous'));
        self::assertNull($this->owner_check->getUserFromRepresentationId(0));
    }

    public function testNotAliveUserCanNotBeConsideredAsOwner(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();

        $this->user_manager->method('findUser')->willReturn($user);
        $this->user_manager->method('getUserById')->willReturn($user);

        self::assertNull($this->owner_check->getOwnerIdFromLoginName('peraltaj'));
        self::assertNull($this->owner_check->getUserFromRepresentationId(10));
    }
}
