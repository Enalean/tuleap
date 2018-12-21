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
use Tuleap\Docman\Upload\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\DocumentToUploadCreationConflictException;
use Tuleap\Docman\Upload\DocumentToUploadCreationFileMismatchException;
use Tuleap\Docman\Upload\DocumentToUploadCreator;
use Tuleap\Docman\Upload\DocumentToUploadMaxSizeExceededException;

class DocmanItemCreator
{
    const ITEM_TYPE_ID = [
        ItemRepresentation::TYPE_EMPTY => PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
        ItemRepresentation::TYPE_WIKI  => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
    ];

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
    /**
     * @var AfterItemCreationVisitor
     */
    private $creator_visitor;

    public function __construct(
        \Docman_ItemFactory $item_factory,
        DocumentOngoingUploadRetriever $document_ongoing_upload_retriever,
        DocumentToUploadCreator $document_to_upload_creator,
        AfterItemCreationVisitor $creator_visitor
    ) {
        $this->item_factory                      = $item_factory;
        $this->document_ongoing_upload_retriever = $document_ongoing_upload_retriever;
        $this->document_to_upload_creator        = $document_to_upload_creator;
        $this->creator_visitor                   = $creator_visitor;
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
                if (!$this->checkAllItemPropertiesAreNull($docman_item_post_representation)) {
                    throw new RestException(
                        400,
                        sprintf('The type "empty" and the properties given does not match')
                    );
                }
                return $this->createDocument(
                    PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                    $parent_item,
                    $user,
                    $project,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    null
                );
            case ItemRepresentation::TYPE_WIKI:
                if ($docman_item_post_representation->wiki_properties === null) {
                    throw new RestException(
                        400,
                        "Please provide wiki_properties in order to create a wiki document."
                    );
                }
                if ($docman_item_post_representation->file_properties !== null) {
                    throw new RestException(
                        400,
                        sprintf('"file_properties" is not null while the given type is "wiki"')
                    );
                }

                return $this->createDocument(
                    PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
                    $parent_item,
                    $user,
                    $project,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    $docman_item_post_representation->wiki_properties->page_name
                );
            case ItemRepresentation::TYPE_FILE:
                if ($docman_item_post_representation->file_properties === null) {
                    throw new RestException(
                        400,
                        'Providing file properties is mandatory when creating a new file'
                    );
                }
                if ($docman_item_post_representation->wiki_properties !== null) {
                    throw new RestException(
                        400,
                        sprintf('"wiki_properties" is not null while the given type is "file"')
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
    private function createDocument(
        $item_type_id,
        Docman_Item $parent_item,
        PFUser $user,
        Project $project,
        $title,
        $description,
        $wiki_page
    ) {
        $item = $this->item_factory->createWithoutOrdering(
            $title,
            $description,
            $parent_item->getId(),
            PLUGIN_DOCMAN_ITEM_STATUS_NONE,
            $user->getId(),
            $item_type_id,
            $wiki_page
        );

        $params = [
            'group_id' => $project->getID(),
            'parent'   => $parent_item,
            'item'     => $item,
            'user'     => $user
        ];
        $item->accept($this->creator_visitor, $params);

        $representation = new CreatedItemRepresentation();
        $representation->build($item->getId());

        return $representation;
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

    private function checkAllItemPropertiesAreNull(DocmanItemPOSTRepresentation $docman_item_post_representation)
    {
        return ($docman_item_post_representation->wiki_properties === null && $docman_item_post_representation->file_properties == null);
    }
}
