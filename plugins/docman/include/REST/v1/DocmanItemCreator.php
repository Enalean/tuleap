<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Docman\REST\v1;

use Docman_Item;
use Luracast\Restler\RestException;
use PFUser;
use Project;
use Tuleap\Docman\Upload\DocumentToUploadCreationConflictException;
use Tuleap\Docman\Upload\DocumentToUploadCreationFileMismatchException;
use Tuleap\Docman\Upload\DocumentToUploadCreator;
use Tuleap\Docman\Upload\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\DocumentToUploadMaxSizeExceededException;

class DocmanItemCreator
{
    /**
     * @var \PermissionsManager
     */
    private $permission_manager;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var \Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var DocumentOngoingUploadRetriever
     */
    private $document_ongoing_upload_retriever;
    /**
     * @var DocumentToUploadCreator
     */
    private $document_to_upload_creator;

    public function __construct(
        \PermissionsManager $permission_manager,
        \EventManager $event_manager,
        \Docman_ItemFactory $item_factory,
        DocumentOngoingUploadRetriever $document_ongoing_upload_retriever,
        DocumentToUploadCreator $document_to_upload_creator
    ) {
        $this->permission_manager                = $permission_manager;
        $this->event_manager                     = $event_manager;
        $this->item_factory                      = $item_factory;
        $this->document_ongoing_upload_retriever = $document_ongoing_upload_retriever;
        $this->document_to_upload_creator        = $document_to_upload_creator;
    }

    /**
     * @return CreatedItemRepresentation
     * @throws \Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException
     * @throws RestException
     */
    public function create(
        Docman_Item $parent_item,
        PFUser $user,
        Project $project,
        DocmanItemPOSTRepresentation $docman_item_post_representation,
        \DateTimeImmutable $current_time
    ) {
        $this->checkDocumentIsNotBeingUploaded(
            $parent_item,
            $docman_item_post_representation->type,
            $docman_item_post_representation->title,
            $current_time
        );

        switch ($docman_item_post_representation->type) {
            case ItemRepresentation::TYPE_EMPTY:
                return $this->createEmptyDocument(
                    $parent_item,
                    $user,
                    $project,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description
                );
            case ItemRepresentation::TYPE_FILE:
                if ($docman_item_post_representation->file_properties === null) {
                    throw new RestException(
                        400,
                        'Providing file properties is mandatory when creating a new file'
                    );
                }
                return $this->createFileDocument(
                    $parent_item,
                    $user,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    $current_time,
                    $docman_item_post_representation->file_properties
                );
            default:
                throw new \DomainException('Unknown document type: ' . $docman_item_post_representation->type);
        }
    }

    /**
     * @throws RestException
     */
    private function checkDocumentIsNotBeingUploaded(
        Docman_Item $parent_item,
        $document_type,
        $title,
        \DateTimeImmutable $current_time
    ) {
        if ($document_type === ItemRepresentation::TYPE_FILE) {
            return;
        }

        $is_document_being_uploaded = $this->document_ongoing_upload_retriever->isThereAlreadyAnUploadOngoing(
            $parent_item,
            $title,
            $current_time
        );
        if ($is_document_being_uploaded) {
            throw new RestException(409, 'A document is already being uploaded for this item');
        }
    }

    /**
     * @throws \Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException
     */
    private function createEmptyDocument(
        Docman_Item $parent_item,
        PFUser $user,
        Project $project,
        $title,
        $description
    ) {
        $item = $this->item_factory->createWithoutOrdering(
            $title,
            $description,
            $parent_item->getId(),
            PLUGIN_DOCMAN_ITEM_STATUS_NONE,
            $user->getId(),
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );

        $this->inheritPermissionsFromParent($item);
        $this->triggerPostCreationEvents($item, $user, $parent_item, $project);

        $representation = new CreatedItemRepresentation();
        $representation->build($item->getId());

        return $representation;
    }

    private function inheritPermissionsFromParent(Docman_Item $item)
    {
        $this->permission_manager->clonePermissions(
            $item->getParentId(),
            $item->getId(),
            ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE']
        );
    }

    private function triggerPostCreationEvents(Docman_Item $item, PFUser $user, Docman_Item $parent, Project $project)
    {
        $params = [
            'group_id' => $project->getID(),
            'parent'   => $parent,
            'item'     => $item,
            'user'     => $user
        ];

        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_EMPTY, $params);
        $this->event_manager->processEvent('plugin_docman_event_add', $params);
        $this->event_manager->processEvent('send_notifications', []);
    }

    /**
     *
     * @throws RestException
     */
    private function createFileDocument(
        Docman_Item $parent_item,
        PFUser $user,
        $title,
        $description,
        \DateTimeImmutable $current_time,
        FilePropertiesPOSTRepresentation $file_properties
    ) {
        try {
            $document_to_upload = $this->document_to_upload_creator->create(
                $parent_item,
                $user,
                $current_time,
                $title,
                $description,
                $file_properties->file_name,
                $file_properties->file_size
            );
        } catch (DocumentToUploadCreationConflictException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (DocumentToUploadCreationFileMismatchException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (DocumentToUploadMaxSizeExceededException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $file_properties_representation = new CreatedItemFilePropertiesRepresentation();
        $file_properties_representation->build($document_to_upload->getUploadHref());
        $representation = new CreatedItemRepresentation();
        $representation->build($document_to_upload->getItemId(), $file_properties_representation);

        return $representation;
    }
}
