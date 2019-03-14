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

declare(strict_types = 1);

namespace Tuleap\Docman\Lock;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LockUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var \Docman_File|\Mockery\MockInterface
     */
    private $item;
    /**
     * @var LockUpdater
     */
    private $lock_updater;

    /**
     * @var \Docman_LockFactory|\Mockery\MockInterface
     */
    private $lock_factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->lock_factory = \Mockery::mock(\Docman_LockFactory::class);
        $this->lock_updater = new LockUpdater($this->lock_factory);
        $this->item         = \Mockery::mock(\Docman_File::class);
        $this->user         = \Mockery::mock(\PFUser::class);
    }

    public function testItShouldRealeaseLockWhenWeRealeaseLockedOnALockedDocument()
    {
        $this->lock_factory->shouldReceive('getLockInfoForItem')->andReturn(
            [
                'item_id' => 1,
                'user_id' => 101,
                new \DateTimeImmutable()
            ]
        );

        $this->lock_factory->shouldReceive('unlock')->withArgs([$this->item])->once();
        $this->lock_factory->shouldReceive('lock')->never();

        $this->lock_updater->updateLockInformation($this->item, false, $this->user);
    }

    public function testItShouldDoNothingIfWeKeepLockOnAnExistingLockedDocument()
    {
        $this->lock_factory->shouldReceive('getLockInfoForItem')->andReturn(
            [
                'item_id' => 1,
                'user_id' => 101,
                new \DateTimeImmutable()
            ]
        );

        $this->lock_factory->shouldReceive('unlock')->never();
        $this->lock_factory->shouldReceive('lock')->never();

        $this->lock_updater->updateLockInformation($this->item, true, $this->user);
    }

    public function testItShouldLockDocumentWhenWeLockAnUnlockedDocument()
    {
        $this->lock_factory->shouldReceive('getLockInfoForItem')->andReturn(false);

        $this->lock_factory->shouldReceive('unlock')->never();
        $this->lock_factory->shouldReceive('lock')->withArgs([$this->item, $this->user])->once();

        $this->lock_updater->updateLockInformation($this->item, true, $this->user);
    }

    public function testItShouldDoNothingWhenWeTryToReleaseANonLockedDocument()
    {
        $this->lock_factory->shouldReceive('getLockInfoForItem')->andReturn(false);

        $this->lock_factory->shouldReceive('lock')->never();
        $this->lock_factory->shouldReceive('lock')->never();

        $this->lock_updater->updateLockInformation($this->item, false, $this->user);
    }
}
