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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class PermissionsManagerLockTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $itemId;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    private $docmanPm;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = \Mockery::spy(PFUser::class);
        $this->user->allows(['getId' => 1234]);
        $this->itemId = 1848;
        $this->docmanPm = \Mockery::mock(Docman_PermissionsManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItemIsNotLocked(): void
    {
        // user is not docman admin
        $this->docmanPm->allows(['_isUserDocmanAdmin' => false]);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        // user cannot manage
        $this->docmanPm->allows(['userCanManage' => false]);

        $lockFactory = \Mockery::spy(Docman_LockFactory::class);
        $lockFactory->allows(['itemIsLockedByItemId' => false]);
        $this->docmanPm->allows(['getLockFactory' => $lockFactory]);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    public function testItemIsLockedBySomeoneElse(): void
    {
        // user is not docman admin
        $this->docmanPm->allows(['_isUserDocmanAdmin' => false]);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        // user cannot manage
        $this->docmanPm->allows(['userCanManage' => false]);

        $lockFactory = \Mockery::spy(Docman_LockFactory::class);
        $lockFactory->allows(['itemIsLockedByItemId' => true]);
        $lockFactory->allows(['userIsLockerByItemId' => false]);
        $this->docmanPm->allows(['getLockFactory' => $lockFactory]);

        $this->assertTrue($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    public function testItemIsLockedBySomeoneElseButUserCanManage(): void
    {
        // user is not docman admin
        $this->docmanPm->allows(['_isUserDocmanAdmin' => false]);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        // user cannot manage
        $this->docmanPm->allows(['userCanManage' => true]);

        $lockFactory = \Mockery::spy(Docman_LockFactory::class);
        $lockFactory->allows(['itemIsLockedByItemId' => true]);
        $lockFactory->allows(['userIsLockerByItemId' => false]);
        $this->docmanPm->allows(['getLockFactory' => $lockFactory]);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }

    public function testItemIsLockedByOwner(): void
    {
        // user is not docman admin
        $this->docmanPm->allows(['_isUserDocmanAdmin' => false]);
        // user is not super admin
        $this->user->allows(['isSuperUser' => false]);
        // user cannot manage
        $this->docmanPm->allows(['userCanManage' => false]);

        $lockFactory = \Mockery::spy(Docman_LockFactory::class);
        $lockFactory->allows(['itemIsLockedByItemId' => true]);
        $lockFactory->allows(['userIsLockerByItemId' => true]);
        $this->docmanPm->allows(['getLockFactory' => $lockFactory]);

        $this->assertFalse($this->docmanPm->_itemIsLockedForUser($this->user, $this->itemId));
    }
}
