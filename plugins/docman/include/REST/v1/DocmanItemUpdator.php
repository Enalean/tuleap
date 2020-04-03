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

namespace Tuleap\Docman\REST\v1;

use Docman_ItemFactory;
use Docman_LockFactory;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\Version\Version;

class DocmanItemUpdator
{
    /**
     * @var Docman_LockFactory
     */
    private $lock_factory;
    /**
     * @var ApprovalTableUpdater
     */
    private $approval_table_updater;
    /**
     * @var ApprovalTableUpdateActionChecker
     */
    private $approval_table_action_checker;
    /**
     * @var PostUpdateEventAdder
     */
    private $post_update_event_adder;
    /**
     * @var Docman_ItemFactory
     */
    private $docman_item_factory;


    public function __construct(
        ApprovalTableUpdater $approval_table_updater,
        ApprovalTableUpdateActionChecker $approval_table_action_checker,
        PostUpdateEventAdder $post_update_event_adder,
        Docman_ItemFactory $docman_item_factory,
        Docman_LockFactory $lock_factory
    ) {
        $this->approval_table_updater        = $approval_table_updater;
        $this->approval_table_action_checker = $approval_table_action_checker;
        $this->post_update_event_adder       = $post_update_event_adder;
        $this->docman_item_factory           = $docman_item_factory;
        $this->lock_factory                  = $lock_factory;
    }

    public function updateCommonData(
        \Docman_Item $item,
        bool $should_lock_item,
        \PFUser $user,
        ?string $approval_table_action,
        ?Version $version
    ): void {
        if ($approval_table_action) {
            $this->updateApprovalTable($item, $user, $approval_table_action);
        }
        $this->updateCommonDataWithoutApprovalTable($item, $should_lock_item, $user, $version);
    }

    public function updateCommonDataWithoutApprovalTable(
        \Docman_Item $item,
        bool $should_lock_item,
        \PFUser $user,
        ?Version $version
    ): void {
        if ($should_lock_item) {
            $this->lock_factory->lock($item, $user);
        } elseif (!$should_lock_item && $this->lock_factory->itemIsLocked($item)) {
            $this->lock_factory->unlock($item, $user);
        }
        $this->post_update_event_adder->triggerPostUpdateEvents($item, $user, $version);
    }

    private function updateApprovalTable(\Docman_Item $item, \PFUser $user, string $approval_table_action): void
    {
        $this->docman_item_factory->update(['id' => $item->getId()]);

        $item = $this->docman_item_factory->getItemFromDb($item->getId());
        if ($item !== null && $this->approval_table_action_checker->checkAvailableUpdateAction($approval_table_action)) {
            $this->approval_table_updater->updateApprovalTable($item, $user, $approval_table_action);
        }
    }
}
