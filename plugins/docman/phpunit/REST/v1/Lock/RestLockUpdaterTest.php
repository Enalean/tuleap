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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\REST\I18NRestException;

class RestLockUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Docman_File|\Mockery\MockInterface
     */
    private $item;
    /**
     * @var \Tuleap\Docman\REST\v1\Lock\RestLockUpdater
     */
    private $updater;
    /**
     * @var Docman_PermissionsManager|\Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var Docman_LockFactory|\Mockery\MockInterface
     */
    private $lock_factory;

    public function setUp(): void
    {
        $this->lock_factory        = Mockery::mock(Docman_LockFactory::class);
        $this->permissions_manager = Mockery::mock(Docman_PermissionsManager::class);
        $this->updater             = new RestLockUpdater($this->lock_factory, $this->permissions_manager);

        $this->item = Mockery::mock(Docman_File::class);
        $this->item->shouldReceive('getId')->andReturn(10);
        $this->user = Mockery::mock(PFUser::class);
    }

    public function testUserCannotAddALockOnAlreadyLockedDocument(): void
    {
        $this->lock_factory->shouldReceive('itemIsLockedByItemId')->andReturn(true);
        $this->expectException(I18NRestException::class);
        $this->lock_factory->shouldReceive('lock')->never();
        $this->updater->lockItem($this->item, $this->user);
    }

    public function testUserCanLockADocument(): void
    {
        $this->lock_factory->shouldReceive('itemIsLockedByItemId')->andReturn(false);
        $this->lock_factory->shouldReceive('lock')->once();
        $this->updater->lockItem($this->item, $this->user);
    }

    public function testUserCannotUnlockADocumentIfHeHasNoSufficentPermission(): void
    {
        $this->permissions_manager->shouldReceive('_itemIsLockedForUser')->andReturn(true);
        $this->expectException(I18NRestException::class);
        $this->updater->unlockItem($this->item, $this->user);
    }

    public function testUserCanUnLockADocument(): void
    {
        $this->permissions_manager->shouldReceive('_itemIsLockedForUser')->andReturn(false);
        $this->lock_factory->shouldReceive('unlock')->once();
        $this->updater->unlockItem($this->item, $this->user);
    }
}
