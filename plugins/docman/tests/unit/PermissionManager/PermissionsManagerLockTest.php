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

namespace Tuleap\Docman\PermissionManager;

use Docman_LockFactory;
use Docman_PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsManagerLockTest extends TestCase
{
    private int $item_id;
    private PFUser $user;
    private Docman_PermissionsManager&MockObject $permissions_manager;

    #[\Override]
    public function setUp(): void
    {
        $this->user                = UserTestBuilder::anActiveUser()->withoutSiteAdministrator()->withId(1234)->build();
        $this->item_id             = 1848;
        $this->permissions_manager = $this->createPartialMock(Docman_PermissionsManager::class, [
            '_isUserDocmanAdmin',
            'userCanManage',
            'getLockFactory',
        ]);
    }

    public function testItemIsNotLocked(): void
    {
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user cannot manage
        $this->permissions_manager->method('userCanManage')->willReturn(false);

        $lock_factory = $this->createMock(Docman_LockFactory::class);
        $lock_factory->method('itemIsLockedByItemId')->willReturn(false);
        $this->permissions_manager->method('getLockFactory')->willReturn($lock_factory);

        self::assertFalse($this->permissions_manager->_itemIsLockedForUser($this->user, $this->item_id));
    }

    public function testItemIsLockedBySomeoneElse(): void
    {
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user cannot manage
        $this->permissions_manager->method('userCanManage')->willReturn(false);

        $lock_factory = $this->createMock(Docman_LockFactory::class);
        $lock_factory->method('itemIsLockedByItemId')->willReturn(true);
        $lock_factory->method('userIsLockerByItemId')->willReturn(false);
        $this->permissions_manager->method('getLockFactory')->willReturn($lock_factory);

        self::assertTrue($this->permissions_manager->_itemIsLockedForUser($this->user, $this->item_id));
    }

    public function testItemIsLockedBySomeoneElseButUserCanManage(): void
    {
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user cannot manage
        $this->permissions_manager->method('userCanManage')->willReturn(true);

        $lock_factory = $this->createMock(Docman_LockFactory::class);
        $lock_factory->method('itemIsLockedByItemId')->willReturn(true);
        $lock_factory->method('userIsLockerByItemId')->willReturn(false);
        $this->permissions_manager->method('getLockFactory')->willReturn($lock_factory);

        self::assertFalse($this->permissions_manager->_itemIsLockedForUser($this->user, $this->item_id));
    }

    public function testItemIsLockedByOwner(): void
    {
        // user is not docman admin
        $this->permissions_manager->method('_isUserDocmanAdmin')->willReturn(false);
        // user cannot manage
        $this->permissions_manager->method('userCanManage')->willReturn(false);

        $lock_factory = $this->createMock(Docman_LockFactory::class);
        $lock_factory->method('itemIsLockedByItemId')->willReturn(true);
        $lock_factory->method('userIsLockerByItemId')->willReturn(true);
        $this->permissions_manager->method('getLockFactory')->willReturn($lock_factory);

        self::assertFalse($this->permissions_manager->_itemIsLockedForUser($this->user, $this->item_id));
    }
}
