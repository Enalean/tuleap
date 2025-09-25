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
use Docman_Version;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanItemUpdatorTest extends TestCase
{
    private ApprovalTableUpdater&MockObject $approval_table_updater;
    private ApprovalTableUpdateActionChecker&MockObject $approval_table_action_checker;
    private PostUpdateEventAdder&MockObject $post_update_event_adder;
    private Docman_ItemFactory&MockObject $docman_item_factory;
    private Docman_LockFactory&MockObject $lock_factory;
    private DocmanItemUpdator $updator;

    #[\Override]
    protected function setUp(): void
    {
        $this->approval_table_updater        = $this->createMock(ApprovalTableUpdater::class);
        $this->approval_table_action_checker = $this->createMock(ApprovalTableUpdateActionChecker::class);
        $this->post_update_event_adder       = $this->createMock(PostUpdateEventAdder::class);
        $this->docman_item_factory           = $this->createMock(Docman_ItemFactory::class);
        $this->lock_factory                  = $this->createMock(Docman_LockFactory::class);
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
        $item                  = new Docman_Item(['item_id' => 100]);
        $user                  = UserTestBuilder::buildWithDefaults();
        $approval_table_action = 'copy';
        $version               = new Docman_Version();

        $this->docman_item_factory->expects($this->once())->method('update')->with(['id' => 100]);
        $this->docman_item_factory->expects($this->once())->method('getItemFromDb')->with(100)->willReturn($item);

        $this->approval_table_action_checker->expects($this->once())->method('checkAvailableUpdateAction')
            ->with($approval_table_action)
            ->willReturn(true);

        $this->approval_table_updater->expects($this->once())->method('updateApprovalTable')
            ->with($item, $user, $approval_table_action);

        $this->lock_factory->expects($this->once())->method('lock')->with($item, $user);
        $this->lock_factory->expects($this->never())->method('itemIsLocked');
        $this->lock_factory->expects($this->never())->method('unlock');

        $this->post_update_event_adder->expects($this->once())->method('triggerPostUpdateEvents')
            ->with($item, $user, $version);

        $this->updator->updateCommonData($item, true, $user, $approval_table_action, $version);
    }

    public function testItUpdatesAnItemWithoutAnApprovalTableOnly(): void
    {
        $item                  = new Docman_Item(['item_id' => 100]);
        $user                  = UserTestBuilder::buildWithDefaults();
        $approval_table_action = null;
        $version               = new Docman_Version();

        $this->docman_item_factory->expects($this->never())->method('update');
        $this->docman_item_factory->expects($this->never())->method('getItemFromDb');

        $this->approval_table_action_checker->expects($this->never())->method('checkAvailableUpdateAction');
        $this->approval_table_updater->expects($this->never())->method('updateApprovalTable');

        $this->lock_factory->expects($this->never())->method('lock');
        $this->lock_factory->expects($this->once())->method('itemIsLocked')->willReturn(false);
        $this->lock_factory->expects($this->never())->method('unlock');

        $this->post_update_event_adder->expects($this->once())->method('triggerPostUpdateEvents')
            ->with($item, $user, $version);

        $this->updator->updateCommonData($item, false, $user, $approval_table_action, $version);
    }

    public function testItUpdatesAnItemWithoutAnApprovalTableAndUnlockTheItem(): void
    {
        $item    = new Docman_Item(['item_id' => 100]);
        $user    = UserTestBuilder::buildWithDefaults();
        $version = new Docman_Version();

        $this->docman_item_factory->expects($this->never())->method('update');
        $this->docman_item_factory->expects($this->never())->method('getItemFromDb');

        $this->approval_table_action_checker->expects($this->never())->method('checkAvailableUpdateAction');
        $this->approval_table_updater->expects($this->never())->method('updateApprovalTable');

        $this->lock_factory->expects($this->never())->method('lock');
        $this->lock_factory->expects($this->once())->method('itemIsLocked')->willReturn(true);
        $this->lock_factory->expects($this->once())->method('unlock')->with($item, $user);

        $this->post_update_event_adder->expects($this->once())->method('triggerPostUpdateEvents')
            ->with($item, $user, $version);

        $this->updator->updateCommonDataWithoutApprovalTable($item, false, $user, $version);
    }
}
