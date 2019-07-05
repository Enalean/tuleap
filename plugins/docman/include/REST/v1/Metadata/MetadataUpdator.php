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

use Luracast\Restler\RestException;
use PFUser;
use RuntimeException;
use Tuleap\Docman\Metadata\Owner\OwnerRetriever;
use Tuleap\Docman\Metadata\MetadataEventProcessor;
use Tuleap\REST\I18NRestException;

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
     * @throws I18nRestException
     */
    public function updateDocumentMetadata(
        PUTMetadataRepresentation $representation,
        \Docman_Item $item,
        \DateTimeImmutable $current_time,
        PFUser $current_user
    ): void {
        try {
            $status            = $this->status_mapper->getItemStatusIdFromItemStatusString($representation->status);
            $obsolescence_date = $this->date_retriever->getTimeStampOfDate(
                $representation->obsolescence_date,
                $current_time
            );
        } catch (HardCodedMetadataException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }

        $new_owner_id = $representation->owner_id;
        $new_owner    = $this->owner_retriever->getUserFromRepresentationId($new_owner_id);
        if ($new_owner === null) {
            throw new I18nRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The specified owner ID #%d does not match a valid user.'),
                    $new_owner_id
                )
            );
        }

        if ($representation->title !== $item->getTitle() &&
            $this->item_factory->doesTitleCorrespondToExistingDocument($representation->title, (int)$item->getParentId())) {
            throw new RestException(400, "A file with same title already exists in the given folder.");
        }

        $item_id = $item->getId();

        $previous_owner_id = (int) $item->getOwnerId();
        $previous_owner    = $this->user_manager->getUserById($previous_owner_id);
        if ($previous_owner === null) {
            throw new RuntimeException(
                sprintf(
                    'Previous owner ID #%d of item #%d does not seems to exist',
                    $previous_owner_id,
                    $item_id
                )
            );
        }

        $row = [
            'id'                => $item_id,
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
                $previous_owner->getUserName(),
                $new_owner->getUserName(),
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
