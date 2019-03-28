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

namespace Tuleap\Docman\REST\v1\EmbeddedFiles;

use Docman_FileStorage;
use Docman_ItemFactory;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\Lock\LockChecker;
use Tuleap\Docman\Lock\LockUpdater;
use Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser;
use Tuleap\Docman\REST\v1\PostUpdateEventAdder;

class DocmanEmbeddedFileUpdator
{
    /**
     * @var \Docman_FileStorage
     */
    private $file_storage;
    /**
     * @var \Docman_VersionFactory
     */
    private $version_factory;
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
     * @var \Docman_ItemFactory
     */
    private $docman_item_factory;
    /**
     * @var EmbeddedFileVersionCreationBeforeUpdateValidator
     */
    private $before_update_validator;
    /**
     * @var LockUpdater
     */
    private $lock_updater;

    public function __construct(
        Docman_FileStorage $file_storage,
        \Docman_VersionFactory $version_factory,
        ApprovalTableUpdater $approval_table_updater,
        ApprovalTableUpdateActionChecker $approval_table_action_checker,
        LockChecker $lock_checker,
        PostUpdateEventAdder $post_update_event_adder,
        Docman_ItemFactory $docman_item_factory,
        EmbeddedFileVersionCreationBeforeUpdateValidator $before_update_validator,
        LockUpdater $lock_updater
    ) {
        $this->file_storage                  = $file_storage;
        $this->version_factory               = $version_factory;
        $this->approval_table_updater        = $approval_table_updater;
        $this->approval_table_action_checker = $approval_table_action_checker;
        $this->lock_checker                  = $lock_checker;
        $this->post_update_event_adder       = $post_update_event_adder;
        $this->docman_item_factory           = $docman_item_factory;
        $this->before_update_validator       = $before_update_validator;
        $this->lock_updater                  = $lock_updater;
    }

    /**
     * @throws ExceptionItemIsLockedByAnotherUser
     */
    public function updateEmbeddedFile(
        \Docman_Item $item,
        \PFUser $current_user,
        DocmanEmbeddedFilesPATCHRepresentation $representation
    ): void {
        $this->lock_checker->checkItemIsLocked($item, $current_user);

        $item->accept($this->before_update_validator, []);

        $next_version_id = (int)$this->version_factory->getNextVersionNumber($item);

        $created_file_path = $this->file_storage->store(
            $representation->embedded_properties->content,
            $item->getGroupId(),
            $item->getId(),
            $next_version_id
        );

        $date = new \DateTimeImmutable();

        $new_embedded_version_row = [
            'item_id'   => $item->getId(),
            'number'    => $next_version_id,
            'user_id'   => $current_user->getId(),
            'label'     => '',
            'changelog' => $representation->change_log,
            'date'      => $date->getTimestamp(),
            'filename'  => basename($created_file_path),
            'filesize'  => filesize($created_file_path),
            'filetype'  => 'text/html',
            'path'      => $created_file_path
        ];

        $this->version_factory->create($new_embedded_version_row);

        $this->lock_updater->updateLockInformation($item, (bool)$representation->should_lock_file, $current_user);

        $this->docman_item_factory->update(['id' => $item->getId()]);

        $item = $this->docman_item_factory->getItemFromDb($item->getId());

        $approval_table_action = $representation->approval_table_action;
        if ($this->approval_table_action_checker->checkAvailableUpdateAction($approval_table_action)
        ) {
            $this->approval_table_updater->updateApprovalTable($item, $current_user, $approval_table_action);
        }

        $this->post_update_event_adder->triggerPostUpdateEvents($item, $current_user);
    }
}
