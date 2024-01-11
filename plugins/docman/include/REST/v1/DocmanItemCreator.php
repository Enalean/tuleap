<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedPOSTRepresentation;
use Tuleap\Docman\REST\v1\Empties\DocmanEmptyPOSTRepresentation;
use Tuleap\Docman\REST\v1\Files\CreatedItemFilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\Files\EmptyFileToUploadFinisher;
use Tuleap\Docman\REST\v1\Files\FilePropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPOSTRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinksValidityChecker;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataRepresentationRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\MetadataToCreate;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSet;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetFactory;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPOSTRepresentation;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\Document\DocumentToUploadCreator;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;

class DocmanItemCreator
{
    public function __construct(
        private \Docman_ItemFactory $item_factory,
        private DocumentOngoingUploadRetriever $document_ongoing_upload_retriever,
        private DocumentToUploadCreator $document_to_upload_creator,
        private AfterItemCreationVisitor $creator_visitor,
        private EmptyFileToUploadFinisher $empty_file_to_upload_finisher,
        private DocmanLinksValidityChecker $links_validity_checker,
        private ItemStatusMapper $status_mapper,
        private HardcodedMetadataObsolescenceDateRetriever $date_retriever,
        private CustomMetadataRepresentationRetriever $custom_checker,
        private \Docman_MetadataValueDao $metadata_value_dao,
        private DocmanItemPermissionsForGroupsSetFactory $permissions_for_groups_set_factory,
    ) {
    }

    /**
     * @throws RestException
     */
    private function checkDocumentIsNotBeingUploaded(
        Docman_Item $parent_item,
        $document_type,
        $title,
        \DateTimeImmutable $current_time,
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
     * @return CreatedItemRepresentation
     * @throws Metadata\HardCodedMetadataException
     * @throws \Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException
     */
    private function createDocument(
        $item_type_id,
        \DateTimeImmutable $current_time,
        Docman_Item $parent_item,
        PFUser $user,
        Project $project,
        $title,
        $description,
        MetadataToCreate $metadata_to_create,
        ?string $status,
        ?string $obsolescence_date,
        ?DocmanItemPermissionsForGroupsSet $permissions_for_groups,
        $wiki_page,
        $link_url,
        $content,
    ) {
        $status_id = $this->status_mapper->getItemStatusWithParentInheritance($parent_item, $status);

        if ($item_type_id !== PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
            $obsolescence_date_time_stamp = $this->date_retriever->getTimeStampOfDateWithoutPeriodValidity(
                $obsolescence_date,
                $current_time
            );
        } else {
            $obsolescence_date_time_stamp = (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE;
        }

        $current_date = new \DateTimeImmutable();
        $item         = $this->item_factory->createWithoutOrdering(
            $title,
            $description,
            $parent_item->getId(),
            $status_id,
            $obsolescence_date_time_stamp,
            $user->getId(),
            $item_type_id,
            $current_date,
            $current_date,
            $wiki_page,
            $link_url
        );

        $params = [
            'group_id'               => $project->getID(),
            'parent'                 => $parent_item,
            'item'                   => $item,
            'user'                   => $user,
            'creation_time'          => $current_time,
            'formatted_metadata'     => $metadata_to_create->getMetadataListValues(),
            'permissions_for_groups' => $permissions_for_groups,
        ];

        if ($metadata_to_create->isInheritedFromParent()) {
            $this->metadata_value_dao->inheritMetadataFromParent((int) $item->getId(), (int) $parent_item->getId());
        }

        if ($item_type_id === PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
            $params['content'] = $content;
        }

        $item->accept($this->creator_visitor, $params);
        return CreatedItemRepresentation::build($item->getId());
    }

    /**
     * @throws Metadata\HardCodedMetadataException
     * @throws RestException
     */
    public function createFileDocument(
        Docman_Item $parent_item,
        PFUser $user,
        string $title,
        string $description,
        ?string $status,
        ?string $obsolescence_date,
        \DateTimeImmutable $current_time,
        FilePropertiesPOSTPATCHRepresentation $file_properties,
        MetadataToCreate $metadata_to_create,
        ?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_representation,
    ): CreatedItemRepresentation {
        if ($this->item_factory->doesTitleCorrespondToExistingDocument($title, $parent_item->getId())) {
            throw new RestException(400, "A file with same title already exists in the given folder.");
        }

        $status_id = $this->status_mapper->getItemStatusWithParentInheritance($parent_item, $status);

        $obsolescence_date_time_stamp = $this->date_retriever->getTimeStampOfDateWithoutPeriodValidity(
            $obsolescence_date,
            $current_time
        );

        try {
            $document_to_upload = $this->document_to_upload_creator->create(
                $parent_item,
                $user,
                $current_time,
                $title,
                $description,
                $file_properties->file_name,
                $file_properties->file_size,
                $status_id,
                $obsolescence_date_time_stamp,
                $metadata_to_create->getMetadataListValues(),
                $this->getPermissionsForGroupsSet($parent_item, $permissions_for_groups_representation)
            );

            if ($metadata_to_create->isInheritedFromParent()) {
                $this->metadata_value_dao->inheritMetadataFromParent($document_to_upload->getItemId(), (int) $parent_item->getId());
            }

            if ($file_properties->file_size === 0) {
                $this->empty_file_to_upload_finisher->createEmptyFile($document_to_upload, $file_properties->file_name);

                return CreatedItemRepresentation::build($document_to_upload->getItemId());
            }
        } catch (UploadCreationConflictException | UploadCreationFileMismatchException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadMaxSizeExceededException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $file_properties_representation = CreatedItemFilePropertiesRepresentation::build($document_to_upload->getUploadHref());
        return CreatedItemRepresentation::build($document_to_upload->getItemId(), $file_properties_representation);
    }

    /**
     * @throws Metadata\HardCodedMetadataException
     * @throws RestException
     * @throws \Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException
     * @throws CustomMetadataException
     */
    public function createFolder(
        Docman_Item $parent_item,
        PFUser $user,
        DocmanFolderPOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        Project $project,
    ): CreatedItemRepresentation {
        if ($this->item_factory->doesTitleCorrespondToExistingFolder($representation->title, $parent_item->getId())) {
            throw new RestException(400, "A folder with same title already exists in the given folder.");
        }

        $metadata_to_create = $this->custom_checker->checkAndRetrieveFormattedRepresentation(
            $parent_item,
            $representation->metadata
        );

        return $this->createDocument(
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            $current_time,
            $parent_item,
            $user,
            $project,
            $representation->title,
            $representation->description,
            $metadata_to_create,
            $representation->status,
            ItemRepresentation::OBSOLESCENCE_DATE_NONE,
            $this->getPermissionsForGroupsSet($parent_item, $representation->permissions_for_groups),
            null,
            null,
            null
        );
    }

    /**
     * @throws Metadata\HardCodedMetadataException
     * @throws RestException
     * @throws \Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException
     * @throws CustomMetadataException
     */
    public function createEmpty(
        Docman_Item $parent_item,
        PFUser $user,
        DocmanEmptyPOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        Project $project,
    ): CreatedItemRepresentation {
        if ($this->item_factory->doesTitleCorrespondToExistingDocument($representation->title, $parent_item->getId())) {
            throw new RestException(400, "A document with same title already exists in the given folder.");
        }

        $metadata_to_create = $this->custom_checker->checkAndRetrieveFormattedRepresentation(
            $parent_item,
            $representation->metadata
        );

        $this->checkDocumentIsNotBeingUploaded(
            $parent_item,
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
            $representation->title,
            $current_time
        );

        return $this->createDocument(
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
            $current_time,
            $parent_item,
            $user,
            $project,
            $representation->title,
            $representation->description,
            $metadata_to_create,
            $representation->status,
            $representation->obsolescence_date,
            $this->getPermissionsForGroupsSet($parent_item, $representation->permissions_for_groups),
            null,
            null,
            null
        );
    }

    /**
     * @throws Metadata\HardCodedMetadataException
     * @throws RestException
     * @throws \Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException
     * @throws CustomMetadataException
     */
    public function createWiki(
        Docman_Item $parent_item,
        PFUser $user,
        DocmanWikiPOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        Project $project,
    ): CreatedItemRepresentation {
        if ($this->item_factory->doesTitleCorrespondToExistingDocument($representation->title, $parent_item->getId())) {
            throw new RestException(400, "A document with same title already exists in the given folder.");
        }

        $metadata_to_create = $this->custom_checker->checkAndRetrieveFormattedRepresentation(
            $parent_item,
            $representation->metadata
        );

        if (! $project->usesWiki()) {
            throw new RestException(
                400,
                sprintf('The wiki service of the project: "%s" is not available', $project->getUnixName())
            );
        }

        $this->checkDocumentIsNotBeingUploaded(
            $parent_item,
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            $representation->title,
            $current_time
        );

        return $this->createDocument(
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            $current_time,
            $parent_item,
            $user,
            $project,
            $representation->title,
            $representation->description,
            $metadata_to_create,
            $representation->status,
            $representation->obsolescence_date,
            $this->getPermissionsForGroupsSet($parent_item, $representation->permissions_for_groups),
            $representation->wiki_properties->page_name,
            null,
            null
        );
    }

    /**
     * @throws Metadata\HardCodedMetadataException
     * @throws RestException
     * @throws \Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException
     * @throws CustomMetadataException
     */
    public function createEmbedded(
        Docman_Item $parent_item,
        PFUser $user,
        DocmanEmbeddedPOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        Project $project,
    ): CreatedItemRepresentation {
        if ($this->item_factory->doesTitleCorrespondToExistingDocument($representation->title, $parent_item->getId())) {
            throw new RestException(400, "A document with same title already exists in the given folder.");
        }

        $metadata_to_create = $this->custom_checker->checkAndRetrieveFormattedRepresentation(
            $parent_item,
            $representation->metadata
        );

        $this->checkDocumentIsNotBeingUploaded(
            $parent_item,
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            $representation->title,
            $current_time
        );

        return $this->createDocument(
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            $current_time,
            $parent_item,
            $user,
            $project,
            $representation->title,
            $representation->description,
            $metadata_to_create,
            $representation->status,
            $representation->obsolescence_date,
            $this->getPermissionsForGroupsSet($parent_item, $representation->permissions_for_groups),
            null,
            null,
            $representation->embedded_properties->content
        );
    }

    /**
     * @throws Metadata\HardCodedMetadataException
     * @throws RestException
     * @throws \Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException
     * @throws CustomMetadataException
     */
    public function createLink(
        Docman_Item $parent_item,
        PFUser $user,
        DocmanLinkPOSTRepresentation $representation,
        \DateTimeImmutable $current_time,
        Project $project,
    ): CreatedItemRepresentation {
        if ($this->item_factory->doesTitleCorrespondToExistingDocument($representation->title, $parent_item->getId())) {
            throw new RestException(400, "A document with same title already exists in the given folder.");
        }

        $metadata_to_create = $this->custom_checker->checkAndRetrieveFormattedRepresentation(
            $parent_item,
            $representation->metadata
        );

        $link_url = $representation->link_properties->link_url;
        $this->links_validity_checker->checkLinkValidity($link_url);

        $this->checkDocumentIsNotBeingUploaded(
            $parent_item,
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            $representation->title,
            $current_time
        );

        return $this->createDocument(
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            $current_time,
            $parent_item,
            $user,
            $project,
            $representation->title,
            $representation->description,
            $metadata_to_create,
            $representation->status,
            $representation->obsolescence_date,
            $this->getPermissionsForGroupsSet($parent_item, $representation->permissions_for_groups),
            null,
            $link_url,
            null
        );
    }

    /**
     * @throws RestException
     */
    private function getPermissionsForGroupsSet(
        Docman_Item $parent_item,
        ?DocmanItemPermissionsForGroupsSetRepresentation $representation,
    ): ?DocmanItemPermissionsForGroupsSet {
        if ($representation === null) {
            return null;
        }
        return $this->permissions_for_groups_set_factory->fromRepresentation(
            $parent_item,
            $representation
        );
    }
}
