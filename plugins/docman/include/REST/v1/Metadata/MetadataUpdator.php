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
use Project;
use RuntimeException;
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\Docman\Metadata\ItemImpactedByMetadataChangeCollection;
use Tuleap\Docman\Metadata\MetadataDoesNotExistException;
use Tuleap\Docman\Metadata\MetadataEventProcessor;
use Tuleap\Docman\Metadata\MetadataRecursiveUpdator;
use Tuleap\Docman\Metadata\MetadataValueUpdator;
use Tuleap\Docman\Metadata\NoItemToRecurseException;
use Tuleap\Docman\Metadata\Owner\OwnerRetriever;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
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
    /**
     * @var MetadataRecursiveUpdator
     */
    private $recursive_updator;
    /**
     * @var DocumentOngoingUploadRetriever
     */
    private $document_ongoing_upload_retriever;
    /**
     * @var CustomMetadataRepresentationRetriever
     */
    private $custom_metadata_representation_retriever;
    /**
     * @var MetadataValueUpdator
     */
    private $metadata_value_updator;

    public function __construct(
        \Docman_ItemFactory $item_factory,
        ItemStatusMapper $status_mapper,
        HardcodedMetadataObsolescenceDateRetriever $date_retriever,
        \UserManager $user_manager,
        OwnerRetriever $owner_retriever,
        MetadataEventProcessor $event_processor,
        MetadataRecursiveUpdator $recursive_updator,
        DocumentOngoingUploadRetriever $document_ongoing_upload_retriever,
        CustomMetadataRepresentationRetriever $custom_metadata_representation_retriever,
        MetadataValueUpdator $metadata_value_updator
    ) {
        $this->item_factory                             = $item_factory;
        $this->status_mapper                            = $status_mapper;
        $this->date_retriever                           = $date_retriever;
        $this->user_manager                             = $user_manager;
        $this->owner_retriever                          = $owner_retriever;
        $this->event_processor                          = $event_processor;
        $this->recursive_updator                        = $recursive_updator;
        $this->document_ongoing_upload_retriever        = $document_ongoing_upload_retriever;
        $this->custom_metadata_representation_retriever = $custom_metadata_representation_retriever;
        $this->metadata_value_updator                   = $metadata_value_updator;
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     * @throws MetadataDoesNotExistException
     */
    public function updateDocumentMetadata(
        PUTMetadataRepresentation $representation,
        \Docman_Item $item,
        PFUser $current_user
    ): void {
        if (
            $representation->title !== $item->getTitle() &&
            $this->item_factory->doesTitleCorrespondToExistingDocument($representation->title, (int) $item->getParentId())
        ) {
            throw new RestException(400, "A file with same title already exists in the given folder.");
        }

        $this->checkThereIsNoOnGoingUploadWithSameDocumentName($item);

        try {
            $status            = $this->status_mapper->getItemStatusIdFromItemStatusString($representation->status);
            $obsolescence_date = $this->date_retriever->getTimeStampOfDate($representation->obsolescence_date);
        } catch (HardCodedMetadataException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }

        $new_owner_id = $representation->owner_id;
        $new_owner    = $this->owner_retriever->getUserFromRepresentationId($new_owner_id);
        if ($new_owner === null) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-docman', 'The specified owner ID #%d does not match a valid user.'),
                    $new_owner_id
                )
            );
        }

        try {
            $metadata_to_update_collection = $this->custom_metadata_representation_retriever->checkAndBuildMetadataToUpdate(
                $representation->metadata
            );
        } catch (CustomMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
            );
        }

        foreach ($metadata_to_update_collection as $metadata_to_update) {
                $this->metadata_value_updator->updateMetadata(
                    $metadata_to_update->getMetadata(),
                    (int) $item->getId(),
                    $metadata_to_update->getValue()
                );
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

        $this->sendStatusUpdateEvent($item, $current_user, $status);

        $this->item_factory->update($row);
    }

    /**
     * @throws I18NRestException
     * @throws MetadataDoesNotExistException
     * @throws RestException
     */
    public function updateFolderMetadata(
        PUTMetadataFolderRepresentation $representation,
        \Docman_Item $item,
        Project $project,
        PFUser $user
    ): void {
        if (
            $representation->title !== $item->getTitle() &&
            $this->item_factory->doesTitleCorrespondToExistingFolder($representation->title, (int) $item->getParentId())
        ) {
            throw new RestException(400, "A file with same title already exists in the given folder.");
        }

        $this->checkThereIsNoOnGoingUploadWithSameDocumentName($item);

        $item_id = $item->getId();

        try {
            $status = $this->status_mapper->getItemStatusIdFromItemStatusString($representation->status->value);
        } catch (HardCodedMetadataException $e) {
            throw new I18NRestException(400, $e->getI18NExceptionMessage());
        }

        $row = [
            'id'          => $item_id,
            'title'       => $representation->title,
            'description' => $representation->description,
            'status'      => $status
        ];
        $this->item_factory->update($row);
        $this->sendStatusUpdateEvent($item, $user, $status);

        try {
            $metadata_to_update_collection = $this->custom_metadata_representation_retriever->checkAndBuildFolderMetadataToUpdate(
                $representation->metadata
            );
        } catch (CustomMetadataException $e) {
            throw new I18NRestException(
                400,
                $e->getI18NExceptionMessage()
            );
        }

        foreach ($metadata_to_update_collection as $metadata_to_update) {
            $this->metadata_value_updator->updateMetadata(
                $metadata_to_update->getMetadata(),
                (int) $item->getId(),
                $metadata_to_update->getValue()
            );
        }

        $all_item_collection = ItemImpactedByMetadataChangeCollection::buildFromRest(
            $representation,
            $metadata_to_update_collection,
            PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS
        );

        $folders_only_collection = ItemImpactedByMetadataChangeCollection::buildFromRest(
            $representation,
            $metadata_to_update_collection,
            PUTRecursiveStatusRepresentation::RECURSION_FOLDER
        );
        try {
            if ($all_item_collection->getTotalElements() > 0) {
                $this->recursive_updator->updateRecursiveMetadataOnFolderAndItems(
                    $all_item_collection,
                    (int) $item->getId(),
                    (int) $project->getID()
                );
            }

            if ($folders_only_collection->getTotalElements() > 0) {
                $this->recursive_updator->updateRecursiveMetadataOnFolder(
                    $folders_only_collection,
                    (int) $item->getId(),
                    (int) $project->getID()
                );
            }
        } catch (NoItemToRecurseException $e) {
        }
    }

    private function sendStatusUpdateEvent(\Docman_Item $item, PFUser $current_user, int $status): void
    {
        if ($status !== $item->getStatus()) {
            $this->event_processor->raiseUpdateEvent(
                $item,
                $current_user,
                (string) $item->getStatus(),
                (string) $status,
                'status'
            );
        }
    }

    /**
     * @throws I18NRestException
     */
    private function checkThereIsNoOnGoingUploadWithSameDocumentName(\Docman_Item $item): void
    {
        if ($item->getParentId() === 0) {
            return;
        }

        $parent_item = $this->item_factory->getItemFromDb($item->getParentId());
        if (! $parent_item) {
            throw new \LogicException(
                sprintf('Parent item %d not found!', $item->getParentId())
            );
        }
        $is_document_being_uploaded = $this->document_ongoing_upload_retriever->isThereAlreadyAnUploadOngoing(
            $parent_item,
            $item->getTitle(),
            new \DateTimeImmutable()
        );

        if ($is_document_being_uploaded) {
            throw new I18NRestException(
                409,
                dgettext(
                    'tuleap-docman',
                    'A document with the same title is already being uploaded, you cannot copy your document here for now'
                )
            );
        }
    }
}
