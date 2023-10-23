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

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use Docman_ItemFactory;
use Docman_LockFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\Version\Version;

class DocmanItemUpdatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ApprovalTableUpdater
     */
    private $approval_table_updater;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ApprovalTableUpdateActionChecker
     */
    private $approval_table_action_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PostUpdateEventAdder
     */
    private $post_update_event_adder;
    /**
     * @var Docman_ItemFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $docman_item_factory;
    /**
     * @var Docman_LockFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $lock_factory;
    /**
     * @var DocmanItemUpdator
     */
    private $updator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->approval_table_updater        = \Mockery::mock(ApprovalTableUpdater::class);
        $this->approval_table_action_checker = \Mockery::mock(ApprovalTableUpdateActionChecker::class);
        $this->post_update_event_adder       = \Mockery::mock(PostUpdateEventAdder::class);
        $this->docman_item_factory           = \Mockery::mock(Docman_ItemFactory::class);
        $this->lock_factory                  = \Mockery::mock(Docman_LockFactory::class);
        $this->updator                       = new DocmanItemUpdator(
            $this->approval_table_updater,
            $this->approval_table_action_checker,
            $this->post_update_event_adder,
            $this->docman_item_factory,
            $this->lock_factory
        );
    }

    public function testItUpdatesAnItemWithAnApprovalTableAndLockAnItem(): void
    {
        $item                  = \Mockery::mock(Docman_Item::class);
        $user                  = \Mockery::mock(\PFUser::class);
        $approval_table_action = 'copy';
        $version               = \Mockery::mock(Version::class);

        $item->shouldReceive('getId')->andReturn(100);
        $this->docman_item_factory->shouldReceive("update")->once()->with(['id' => 100]);
        $this->docman_item_factory->shouldReceive("getItemFromDb")->once()->with(100)->andReturn($item);

        $this->approval_table_action_checker->shouldReceive('checkAvailableUpdateAction')
            ->once()
            ->with($approval_table_action)
            ->andReturn(true);

        $this->approval_table_updater->shouldReceive('updateApprovalTable')
            ->once()
            ->withArgs([$item, $user, $approval_table_action]);

        $this->lock_factory->shouldReceive('lock')->once()->withArgs([$item, $user]);
        $this->lock_factory->shouldReceive('itemIsLocked')->never();
        $this->lock_factory->shouldReceive('unlock')->never();

        $this->post_update_event_adder->shouldReceive('triggerPostUpdateEvents')
            ->once()
            ->withArgs([$item, $user, $version]);

        $this->updator->updateCommonData($item, true, $user, $approval_table_action, $version);
    }

    public function testItUpdatesAnItemWithoutAnApprovalTableOnly(): void
    {
        $item                  = \Mockery::mock(Docman_Item::class);
        $user                  = \Mockery::mock(\PFUser::class);
        $approval_table_action = null;
        $version               = \Mockery::mock(Version::class);

        $item->shouldReceive('getId')->andReturn(100);
        $this->docman_item_factory->shouldReceive("update")->never();
        $this->docman_item_factory->shouldReceive("getItemFromDb")->never();

        $this->approval_table_action_checker->shouldReceive('checkAvailableUpdateAction')
            ->never();
        $this->approval_table_updater->shouldReceive('updateApprovalTable')
            ->never();

        $this->lock_factory->shouldReceive('lock')->never();
        $this->lock_factory->shouldReceive('itemIsLocked')->once()->andReturn(false);
        $this->lock_factory->shouldReceive('unlock')->never();

        $this->post_update_event_adder->shouldReceive('triggerPostUpdateEvents')
            ->once()
            ->withArgs([$item, $user, $version]);

        $this->updator->updateCommonData($item, false, $user, $approval_table_action, $version);
    }

    public function testItUpdatesAnItemWithoutAnApprovalTableAndUnlockTheItem(): void
    {
        $item                  = \Mockery::mock(Docman_Item::class);
        $user                  = \Mockery::mock(\PFUser::class);
        $approval_table_action = null;
        $version               = \Mockery::mock(Version::class);

        $item->shouldReceive('getId')->andReturn(100);
        $this->docman_item_factory->shouldReceive("update")->never();
        $this->docman_item_factory->shouldReceive("getItemFromDb")->never();

        $this->approval_table_action_checker->shouldReceive('checkAvailableUpdateAction')
            ->never();
        $this->approval_table_updater->shouldReceive('updateApprovalTable')
            ->never();

        $this->lock_factory->shouldReceive('lock')->never();
        $this->lock_factory->shouldReceive('itemIsLocked')->once()->andReturn(true);
        $this->lock_factory->shouldReceive('unlock')->once()->withArgs([$item, $user]);

        $this->post_update_event_adder->shouldReceive('triggerPostUpdateEvents')
            ->once()
            ->withArgs([$item, $user, $version]);

        $this->updator->updateCommonDataWithoutApprovalTable($item, false, $user, $version);
    }
}
