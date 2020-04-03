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

namespace Tuleap\Docman\REST\v1\EmbeddedFiles;

use Docman_FileStorage;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\PostUpdateEventAdder;

class EmbeddedFileVersionCreator
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
     * @var DocmanItemUpdator
     */
    private $updator;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var PostUpdateEventAdder
     */
    private $post_update_event_adder;

    public function __construct(
        Docman_FileStorage $file_storage,
        \Docman_VersionFactory $version_factory,
        \Docman_ItemFactory $item_factory,
        DocmanItemUpdator $updator,
        DBTransactionExecutor $transaction_executor,
        PostUpdateEventAdder $post_update_event_adder
    ) {
        $this->file_storage            = $file_storage;
        $this->version_factory         = $version_factory;
        $this->updator                 = $updator;
        $this->item_factory            = $item_factory;
        $this->transaction_executor    = $transaction_executor;
        $this->post_update_event_adder = $post_update_event_adder;
    }

    public function createEmbeddedFileVersion(
        \Docman_File $item,
        \PFUser $current_user,
        DocmanEmbeddedFileVersionPOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        int $status,
        ?int $obsolesence_date,
        string $title,
        ?string $description
    ): void {
        $this->transaction_executor->execute(
            function () use ($item, $current_user, $representation, $status, $obsolesence_date, $current_time, $title, $description) {
                $next_version_id = (int) $this->version_factory->getNextVersionNumber($item);

                $created_file_path = $this->file_storage->store(
                    $representation->embedded_properties->content,
                    $item->getGroupId(),
                    $item->getId(),
                    $next_version_id
                );

                $new_embedded_version_row = [
                    'item_id'   => $item->getId(),
                    'number'    => $next_version_id,
                    'user_id'   => $current_user->getId(),
                    'label'     => $representation->version_title,
                    'changelog' => $representation->change_log,
                    'date'      => $current_time->getTimestamp(),
                    'filename'  => basename($created_file_path),
                    'filesize'  => filesize($created_file_path),
                    'filetype'  => 'text/html',
                    'path'      => $created_file_path
                ];

                $this->version_factory->create($new_embedded_version_row);

                $new_embedded_hardcoded_metadata_row = [
                    'id'                => $item->getId(),
                    'title'             => $title,
                    'description'       => $description,
                    'status'            => $status,
                    'obsolescence_date' => $obsolesence_date
                ];

                $this->item_factory->update($new_embedded_hardcoded_metadata_row);

                $this->updator->updateCommonData(
                    $item,
                    $representation->should_lock_file,
                    $current_user,
                    $representation->approval_table_action,
                    $this->version_factory->getCurrentVersionForItem($item)
                );
            }
        );
    }

    public function createEmbeddedFileVersionFromEmpty(
        \Docman_Empty $item,
        \PFUser $current_user,
        EmbeddedPropertiesPOSTPATCHRepresentation $representation,
        \DateTimeImmutable $current_time
    ): void {
        $this->transaction_executor->execute(
            function () use (
                $item,
                $current_user,
                $representation,
                $current_time
            ) {
                $next_version_id = 1;

                $created_file_path = $this->file_storage->store(
                    $representation->content,
                    $item->getGroupId(),
                    $item->getId(),
                    $next_version_id
                );

                $new_embedded_version_row = [
                    'item_id'   => $item->getId(),
                    'number'    => $next_version_id,
                    'changelog' => 'Initial version',
                    'date'      => $current_time->getTimestamp(),
                    'id'        => $item->getId(),
                    'filename'  => basename($created_file_path),
                    'filesize'  => filesize($created_file_path),
                    'filetype'  => 'text/html',
                    'path'      => $created_file_path
                ];

                $this->version_factory->create($new_embedded_version_row);

                $new_embedded_hardcoded_metadata_row = [
                    'id'        => $item->getId(),
                    'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE
                ];

                $this->item_factory->update($new_embedded_hardcoded_metadata_row);

                $this->post_update_event_adder->triggerPostUpdateEvents(
                    $item,
                    $current_user,
                    $this->version_factory->getCurrentVersionForItem($item)
                );
            }
        );
    }
}
