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

namespace Tuleap\Docman\REST\v1\Metadata;

use PFUser;
use Tuleap\Docman\Actions\OwnerRetriever;
use Tuleap\Docman\Metadata\MetadataEventProcessor;

class MetadataUpdator
{
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var HardcodedMetadataObsolescenceDateRetriever
     */
    private $date_retriever;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var OwnerRetriever
     */
    private $owner_retriever;

    /**
     * @var MetadataEventProcessor
     */
    private $event_processor;

    public function __construct(
        \Docman_ItemFactory $item_factory,
        ItemStatusMapper $status_mapper,
        HardcodedMetadataObsolescenceDateRetriever $date_retriever,
        \UserManager $user_manager,
        OwnerRetriever $owner_retriever,
        MetadataEventProcessor $event_processor
    ) {
        $this->item_factory    = $item_factory;
        $this->status_mapper   = $status_mapper;
        $this->date_retriever  = $date_retriever;
        $this->user_manager    = $user_manager;
        $this->owner_retriever = $owner_retriever;
        $this->event_processor = $event_processor;
    }

    /**
     * @throws HardCodedMetadataException
     * @throws \UserNotExistException
     * @throws \UserNotAuthorizedException
     */
    public function updateDocumentMetadata(
        PUTMetadataRepresentation $representation,
        \Docman_Item $item,
        \DateTimeImmutable $current_time,
        PFUser $current_user
    ): void {
        $status            = $this->status_mapper->getItemStatusIdFromItemStatusString($representation->status);
        $obsolescence_date = $this->date_retriever->getTimeStampOfDate(
            $representation->obsolescence_date,
            $current_time
        );
        $old_owner = $this->user_manager->getUserById((int)$item->getOwnerId());
        if (! $old_owner) {
            $old_owner = $current_user;
        }
        $new_owner = $this->owner_retriever->getUserFromRepresentationId($representation->owner_id);
        $row = [
            'id'                => $item->getId(),
            'title'             => $representation->title,
            'description'       => $representation->description,
            'status'            => $status,
            'obsolescence_date' => $obsolescence_date,
            'user_id'           => $representation->owner_id
        ];

        if ($representation->owner_id !== $item->getOwnerId()) {
            $this->event_processor->raiseUpdateEvent(
                $item,
                $current_user,
                $old_owner->getName(),
                $new_owner->getUnixName(),
                'owner'
            );
        }

        if ($status !== $item->getStatus()) {
            $old_status = $this->status_mapper->getItemStatusStringFormId($item->getStatus());
            $this->event_processor->raiseUpdateEvent(
                $item,
                $current_user,
                $old_status,
                $representation->status,
                'status'
            );
        }

        $this->item_factory->update($row);
    }
}
