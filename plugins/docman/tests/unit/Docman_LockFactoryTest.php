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

namespace Tuleap\Docman;

use Docman_Item;
use Docman_LockDao;
use Docman_LockFactory;
use Docman_Log;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_LockFactoryTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Docman_Log&MockObject $docman_log;
    private Docman_LockDao&MockObject $dao;
    private PFUser $user;
    private Docman_Item $item;
    private Docman_LockFactory $lock_factory;

    public function setUp(): void
    {
        $this->dao          = $this->createMock(Docman_LockDao::class);
        $this->docman_log   = $this->createMock(Docman_Log::class);
        $this->lock_factory = new Docman_LockFactory($this->dao, $this->docman_log);
        $this->user         = UserTestBuilder::buildWithId(105);
        $this->item         = new Docman_Item(['item_id' => 1, 'group_id' => 100]);
    }

    public function testUserShouldBeAbleToLockADocument(): void
    {
        $this->dao->method('searchLocksForProjectByItemId')->willReturn([]);
        $this->dao->expects($this->once())->method('addLock');
        $this->docman_log->expects($this->once())->method('log');

        $this->lock_factory->lock($this->item, $this->user);
    }

    public function testItemIsNotLockedAgainIfUserAlreadyHasTheLock(): void
    {
        $this->dao->method('searchLocksForProjectByItemId')->willReturn([['item_id' => 1]]);
        $this->lock_factory->_cacheLocksForProject($this->item->getId());

        $this->dao->expects(self::never())->method('addLock');
        $this->docman_log->expects(self::never())->method('log');

        $this->lock_factory->lock($this->item, $this->user);
    }

    public function testUserShouldBeAbleToUnLockADocument(): void
    {
        $this->dao->method('searchLocksForProjectByItemId')->willReturn([['item_id' => 1]]);
        $this->dao->expects($this->once())->method('delLock');
        $this->docman_log->expects($this->once())->method('log');

        $this->lock_factory->unlock($this->item, $this->user);
    }

    public function testItemIsNotUnlockedAgainIfTheDocumentIsAlreadyUnlocked(): void
    {
        $this->dao->method('searchLocksForProjectByItemId')->willReturn([]);
        $this->dao->expects(self::never())->method('delLock');
        $this->docman_log->expects(self::never())->method('log');

        $this->lock_factory->unlock($this->item, $this->user);
    }
}
