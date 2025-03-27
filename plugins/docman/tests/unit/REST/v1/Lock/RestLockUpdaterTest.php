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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Lock;

use Docman_File;
use Docman_LockFactory;
use Docman_PermissionsManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RestLockUpdaterTest extends TestCase
{
    private PFUser $user;
    private Docman_File $item;
    private RestLockUpdater $updater;
    private Docman_PermissionsManager&MockObject $permissions_manager;
    private Docman_LockFactory&MockObject $lock_factory;

    public function setUp(): void
    {
        $this->lock_factory        = $this->createMock(Docman_LockFactory::class);
        $this->permissions_manager = $this->createMock(Docman_PermissionsManager::class);
        $this->updater             = new RestLockUpdater($this->lock_factory, $this->permissions_manager);

        $this->item = new Docman_File(['item_id' => 10]);
        $this->user = UserTestBuilder::buildWithDefaults();
    }

    public function testUserCannotAddALockOnAlreadyLockedDocument(): void
    {
        $this->lock_factory->method('itemIsLockedByItemId')->willReturn(true);
        self::expectException(I18NRestException::class);
        $this->lock_factory->expects(self::never())->method('lock');
        $this->updater->lockItem($this->item, $this->user);
    }

    public function testUserCanLockADocument(): void
    {
        $this->lock_factory->method('itemIsLockedByItemId')->willReturn(false);
        $this->lock_factory->expects($this->once())->method('lock');
        $this->updater->lockItem($this->item, $this->user);
    }

    public function testUserCannotUnlockADocumentIfHeHasNoSufficentPermission(): void
    {
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(true);
        self::expectException(I18NRestException::class);
        $this->updater->unlockItem($this->item, $this->user);
    }

    public function testUserCanUnLockADocument(): void
    {
        $this->permissions_manager->method('_itemIsLockedForUser')->willReturn(false);
        $this->lock_factory->expects($this->once())->method('unlock');
        $this->updater->unlockItem($this->item, $this->user);
    }
}
