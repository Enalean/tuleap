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

namespace Tuleap\Docman\REST\v1\Links;

use Docman_ItemFactory;
use Docman_VersionFactory;
use EventManager;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\PostUpdateEventAdder;
use Tuleap\Docman\Version\LinkVersionDataUpdator;

class DocmanLinkVersionCreator
{
    /**
     * @var \Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var DocmanItemUpdator
     */
    private $updator;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var \Docman_LinkVersionFactory
     */
    private $docman_link_version_factory;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var PostUpdateEventAdder
     */
    private $post_update_event_adder;
    /**
     * @var LinkVersionDataUpdator
     */
    private $link_version_data_updator;

    public function __construct(
        Docman_VersionFactory $version_factory,
        DocmanItemUpdator $updator,
        Docman_ItemFactory $item_factory,
        EventManager $event_manager,
        \Docman_LinkVersionFactory $docman_link_version_factory,
        DBTransactionExecutor $transaction_executor,
        PostUpdateEventAdder $post_update_event_adder,
        LinkVersionDataUpdator $link_version_data_updator
    ) {
        $this->version_factory             = $version_factory;
        $this->updator                     = $updator;
        $this->item_factory                = $item_factory;
        $this->event_manager               = $event_manager;
        $this->docman_link_version_factory = $docman_link_version_factory;
        $this->transaction_executor        = $transaction_executor;
        $this->post_update_event_adder     = $post_update_event_adder;
        $this->link_version_data_updator   = $link_version_data_updator;
    }

    public function createLinkVersion(
        \Docman_Link $item,
        \PFUser $current_user,
        DocmanLinkVersionPOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        int $status_id,
        int $obsolescence_date_timestamp,
        string $title,
        ?string $description
    ): void {
        $this->transaction_executor->execute(
            function () use ($item, $current_user, $representation, $status_id, $obsolescence_date_timestamp, $current_time, $title, $description) {
                $next_version_id = (int) $this->version_factory->getNextVersionNumber($item);

                $new_link_version_row = [
                    'item_id'           => $item->getId(),
                    'number'            => $next_version_id,
                    'user_id'           => $current_user->getId(),
                    'label'             => $representation->version_title,
                    'changelog'         => $representation->change_log,
                    'date'              => $current_time->getTimestamp(),
                    'link_url'          => $representation->link_properties->link_url,
                    'title'             => $title,
                    'description'       => $description,
                    'status'            => $status_id,
                    'obsolescence_date' => $obsolescence_date_timestamp
                ];

                $this->item_factory->updateLinkWithMetadata($item, $new_link_version_row);

                $version = $this->docman_link_version_factory->getLatestVersion($item);

                $this->updator->updateCommonData(
                    $item,
                    $representation->should_lock_file,
                    $current_user,
                    $representation->approval_table_action,
                    $version
                );

                $last_version = $this->version_factory->getCurrentVersionForItem($item);

                $event_data = [
                    'item'    => $item,
                    'version' => $last_version,
                ];
                $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_LINKVERSION, $event_data);
            }
        );
    }

    public function createLinkVersionFromEmpty(
        \Docman_Empty $item,
        \PFUser $current_user,
        LinkPropertiesPOSTPATCHRepresentation $representation,
        \DateTimeImmutable $current_time
    ): void {
        $this->transaction_executor->execute(
            function () use ($item, $current_user, $representation, $current_time) {
                $next_version_id = 1;

                $new_link_version_row = [
                    'item_id'   => $item->getId(),
                    'number'    => $next_version_id,
                    'label'     => '',
                    'changelog' => 'Initial version',
                    'date'      => $current_time->getTimestamp(),
                    'link_url'  => $representation->link_url
                ];

                $new_link_item = $this->link_version_data_updator->updateLinkFromEmptyVersionData($item, $new_link_version_row);

                $version = $this->docman_link_version_factory->getLatestVersion($new_link_item);

                $this->post_update_event_adder->triggerPostUpdateEvents($item, $current_user, $version);
            }
        );
    }
}
