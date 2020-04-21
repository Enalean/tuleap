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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Docman_LockFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Docman_Log|\Mockery\MockInterface
     */
    private $docman_log;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Docman_Item|\Mockery\MockInterface
     */
    private $item;
    /**
     * @var Docman_LockFactory
     */
    private $lock_factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->dao          = Mockery::mock(Docman_LockDao::class);
        $this->docman_log   = Mockery::mock(Docman_Log::class);
        $this->lock_factory = new Docman_LockFactory($this->dao, $this->docman_log);
        $this->user         = Mockery::mock(PFUser::class);
        $this->item         = Mockery::mock(Docman_Item::class);
    }

    public function testUserShouldBeAbleToLockADocument(): void
    {
        $this->item->shouldReceive('getId')->andReturn(1);
        $this->item->shouldReceive('getGroupId')->andReturn(100);
        $this->user->shouldReceive('getId')->andReturn(105);
        $this->dao->shouldReceive('searchLocksForProjectByItemId')->andReturn([]);
        $this->dao->shouldReceive('addLock')->once();
        $this->docman_log->shouldReceive('log')->once();

        $this->lock_factory->lock($this->item, $this->user);
    }

    public function testItemIsNotLockedAgainIfUserAlreadyHasTheLock(): void
    {
        $this->item->shouldReceive('getId')->andReturn(1);
        $this->item->shouldReceive('getGroupId')->andReturn(100);
        $this->user->shouldReceive('getId')->andReturn(105);
        $this->dao->shouldReceive('searchLocksForProjectByItemId')->andReturn([['item_id' => 1]]);
        $this->lock_factory->_cacheLocksForProject($this->item->getId());

        $this->dao->shouldReceive('addLock')->never();
        $this->docman_log->shouldReceive('log')->never();

        $this->lock_factory->lock($this->item, $this->user);
    }

    public function testUserShouldBeAbleToUnLockADocument(): void
    {
        $this->item->shouldReceive('getId')->andReturn(1);
        $this->item->shouldReceive('getGroupId')->andReturn(100);
        $this->user->shouldReceive('getId')->andReturn(105);
        $this->dao->shouldReceive('searchLocksForProjectByItemId')->andReturn([['item_id' => 1]]);
        $this->dao->shouldReceive('delLock')->once();
        $this->docman_log->shouldReceive('log')->once();

        $this->lock_factory->unlock($this->item, $this->user);
    }

    public function testItemIsNotUnlockedAgainIfTheDocumentIsAlreadyUnlocked(): void
    {
        $this->item->shouldReceive('getId')->andReturn(1);
        $this->item->shouldReceive('getGroupId')->andReturn(100);
        $this->user->shouldReceive('getId')->andReturn(105);
        $this->dao->shouldReceive('searchLocksForProjectByItemId')->andReturn([]);
        $this->dao->shouldReceive('delLock')->never();
        $this->docman_log->shouldReceive('log')->never();

        $this->lock_factory->unlock($this->item, $this->user);
    }
}
