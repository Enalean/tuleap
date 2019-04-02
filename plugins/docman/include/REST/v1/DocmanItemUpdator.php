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

namespace Tuleap\Docman\REST\v1;

use Docman_ItemFactory;
use Docman_Version;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\Lock\LockUpdater;
use Tuleap\Docman\Version\Version;

class DocmanItemUpdator
{
    /**
     * @var ApprovalTableUpdater
     */
    private $approval_table_updater;
    /**
     * @var ApprovalTableUpdateActionChecker
     */
    private $approval_table_action_checker;
    /**
     * @var LockChecker
     */
    private $lock_checker;
    /**
     * @var PostUpdateEventAdder
     */
    private $post_update_event_adder;
    /**
     * @var Docman_ItemFactory
     */
    private $docman_item_factory;
    /**
     * @var LockUpdater
     */
    private $lock_updater;

    public function __construct(
        ApprovalTableUpdater $approval_table_updater,
        ApprovalTableUpdateActionChecker $approval_table_action_checker,
        LockChecker $lock_checker,
        PostUpdateEventAdder $post_update_event_adder,
        Docman_ItemFactory $docman_item_factory,
        LockUpdater $lock_updater
    ) {

        $this->approval_table_updater        = $approval_table_updater;
        $this->approval_table_action_checker = $approval_table_action_checker;
        $this->lock_checker                  = $lock_checker;
        $this->post_update_event_adder       = $post_update_event_adder;
        $this->docman_item_factory           = $docman_item_factory;
        $this->lock_updater                  = $lock_updater;
    }

    public function updateCommonData(
        \Docman_Item $item,
        bool $should_lock_item,
        \PFUser $user,
        string $approval_table_action,
        ?Version $version
    ): void {
        $this->updateApprovalTable($item, $user, $approval_table_action);
        $this->updateCommonDataWithoutApprovalTable($item, $should_lock_item, $user, $version);
    }

    public function updateCommonDataWithoutApprovalTable(
        \Docman_Item $item,
        bool $should_lock_item,
        \PFUser $user,
        ?Version $version
    ): void {
        $this->updateLock($item, $should_lock_item, $user);
        $this->post_update_event_adder->triggerPostUpdateEvents($item, $user, $version);
    }

    /**
     * @param \Docman_Item $item
     * @param bool         $should_lock_item
     * @param \PFUser      $user
     */
    private function updateLock(\Docman_Item $item, bool $should_lock_item, \PFUser $user): void
    {
        $this->lock_updater->updateLockInformation($item, $should_lock_item, $user);
    }

    private function updateApprovalTable(\Docman_Item $item, \PFUser $user, string $approval_table_action): void
    {
        $this->docman_item_factory->update(['id' => $item->getId()]);

        $item = $this->docman_item_factory->getItemFromDb($item->getId());
        if ($this->approval_table_action_checker->checkAvailableUpdateAction($approval_table_action)) {
            $this->approval_table_updater->updateApprovalTable($item, $user, $approval_table_action);
        }
    }
}
