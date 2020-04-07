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

namespace Tuleap\Docman\REST\v1\Wiki;

use Docman_ItemFactory;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;

class DocmanWikiVersionCreator
{
    /**
     * @var \Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var \Docman_ItemFactory
     */
    private $docman_item_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var DocmanItemUpdator
     */
    private $updator;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        \Docman_VersionFactory $version_factory,
        Docman_ItemFactory $docman_item_factory,
        \EventManager $event_manager,
        DocmanItemUpdator $updator,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->version_factory            = $version_factory;
        $this->docman_item_factory        = $docman_item_factory;
        $this->event_manager              = $event_manager;
        $this->updator                    = $updator;
        $this->transaction_executor       = $transaction_executor;
    }

    public function createWikiVersion(
        \Docman_Wiki $item,
        \PFUser $current_user,
        DocmanWikiVersionPOSTRepresentation $representation,
        int $status_id,
        int $obsolescence_date_timestamp,
        string $title,
        ?string $description
    ): void {
        $this->transaction_executor->execute(
            function () use ($item, $current_user, $representation, $status_id, $obsolescence_date_timestamp, $title, $description) {
                $next_version_id = (int) $this->version_factory->getNextVersionNumber($item);

                $new_wiki_version_row = [
                    'id'                => $item->getId(),
                    'user_id'           => $current_user->getId(),
                    'wiki_page'         => $representation->wiki_properties->page_name,
                    'title'             => $title,
                    'description'       => $description,
                    'status'            => $status_id,
                    'obsolescence_date' => $obsolescence_date_timestamp
                ];

                $this->docman_item_factory->update($new_wiki_version_row);

                $documents = $this->docman_item_factory->getWikiPageReferencers($item->getPagename(), $item->getGroupId());
                foreach ($documents as $document) {
                    $this->event_manager->processEvent(
                        'plugin_docman_event_wikipage_update',
                        [
                            'group_id'  => $item->getGroupId(),
                            'item'      => $document,
                            'user'      => $current_user,
                            'wiki_page' => $representation->wiki_properties->page_name,
                            'old_value' => $next_version_id - 1,
                            'new_value' => $next_version_id
                        ]
                    );
                }

                $last_version = $this->version_factory->getCurrentVersionForItem($item);
                $this->event_manager->processEvent(
                    'plugin_docman_event_edit',
                    [
                        'group_id' => $item->getGroupId(),
                        'item'     => $item,
                        'user'     => $current_user
                    ]
                );

                $this->updator->updateCommonDataWithoutApprovalTable(
                    $item,
                    $representation->should_lock_file,
                    $current_user,
                    $last_version
                );
            }
        );
    }
}
