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
use Rule_Regexp;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\Document\DocumentToUploadCreator;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Valid_FTPURI;
use Valid_LocalURI;

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
    /**
     * @var EmptyFileToUploadFinisher
     */
    private $empty_file_to_upload_finisher;

    public function __construct(
        \Docman_ItemFactory $item_factory,
        DocumentOngoingUploadRetriever $document_ongoing_upload_retriever,
        DocumentToUploadCreator $document_to_upload_creator,
        AfterItemCreationVisitor $creator_visitor,
        EmptyFileToUploadFinisher $empty_file_to_upload_finisher
    ) {
        $this->item_factory                      = $item_factory;
        $this->document_ongoing_upload_retriever = $document_ongoing_upload_retriever;
        $this->document_to_upload_creator        = $document_to_upload_creator;
        $this->creator_visitor                   = $creator_visitor;
        $this->empty_file_to_upload_finisher     = $empty_file_to_upload_finisher;
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
        \DateTimeImmutable $current_time,
        bool $is_embedded_allowed
    ) {
        $this->checkDocumentDoesNotAlreadyExists(
            $docman_item_post_representation
        );

        $this->checkDocumentIsNotBeingUploaded(
            $parent_item,
            $docman_item_post_representation->type,
            $docman_item_post_representation->title,
            $current_time
        );

        switch ($docman_item_post_representation->type) {
            case ItemRepresentation::TYPE_FOLDER:
                if (!$this->checkAllItemPropertiesAreNull($docman_item_post_representation)) {
                    throw new RestException(
                        400,
                        sprintf('The type "folder" and the properties given does not match')
                    );
                }
                return $this->createDocument(
                    PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
                    $current_time,
                    $parent_item,
                    $user,
                    $project,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    null,
                    null,
                    null
                );
            case ItemRepresentation::TYPE_EMPTY:
                if (!$this->checkAllItemPropertiesAreNull($docman_item_post_representation)) {
                    throw new RestException(
                        400,
                        sprintf('The type "empty" and the properties given does not match')
                    );
                }
                return $this->createDocument(
                    PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                    $current_time,
                    $parent_item,
                    $user,
                    $project,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    null,
                    null,
                    null
                );
            case ItemRepresentation::TYPE_WIKI:
                if (! $project->usesWiki()) {
                    throw new RestException(
                        400,
                        sprintf('The wiki service of the project: "%s" is not available', $project->getUnixName())
                    );
                }
                $this->checkPropertiesByType($docman_item_post_representation, ItemRepresentation::TYPE_WIKI);
                return $this->createDocument(
                    PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
                    $current_time,
                    $parent_item,
                    $user,
                    $project,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    $docman_item_post_representation->wiki_properties->page_name,
                    null,
                    null
                );
            case ItemRepresentation::TYPE_FILE:
                $this->checkPropertiesByType($docman_item_post_representation, ItemRepresentation::TYPE_FILE);
                return $this->createFileDocument(
                    $parent_item,
                    $user,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    $current_time,
                    $docman_item_post_representation->file_properties
                );

            case ItemRepresentation::TYPE_LINK:
                $this->checkPropertiesByType($docman_item_post_representation, ItemRepresentation::TYPE_LINK);
                $link_url   = $docman_item_post_representation->link_properties->link_url;
                $valid_http = new Rule_Regexp(Valid_LocalURI::URI_REGEXP);
                $valid_ftp  = new Rule_Regexp(Valid_FTPURI::URI_REGEXP);
                if (!$valid_ftp->isValid($link_url) && !$valid_http->isValid($link_url)) {
                    throw new RestException(
                        400,
                        sprintf('The link is not a valid URL')
                    );
                }
                return $this->createDocument(
                    PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                    $current_time,
                    $parent_item,
                    $user,
                    $project,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    null,
                    $link_url,
                    null
                );

            case ItemRepresentation::TYPE_EMBEDDED:
                if ($is_embedded_allowed === false) {
                    throw new RestException(403, 'Embedded files are not allowed');
                }
                $this->checkPropertiesByType($docman_item_post_representation, ItemRepresentation::TYPE_EMBEDDED);

                return $this->createDocument(
                    PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
                    $current_time,
                    $parent_item,
                    $user,
                    $project,
                    $docman_item_post_representation->title,
                    $docman_item_post_representation->description,
                    null,
                    null,
                    $docman_item_post_representation->embedded_properties->content
                );
                break;
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
        \DateTimeImmutable $current_time,
        Docman_Item $parent_item,
        PFUser $user,
        Project $project,
        $title,
        $description,
        $wiki_page,
        $link_url,
        $content
    ) {
        $item = $this->item_factory->createWithoutOrdering(
            $title,
            $description,
            $parent_item->getId(),
            PLUGIN_DOCMAN_ITEM_STATUS_NONE,
            $user->getId(),
            $item_type_id,
            $wiki_page,
            $link_url
        );

        $params = [
            'group_id'      => $project->getID(),
            'parent'        => $parent_item,
            'item'          => $item,
            'user'          => $user,
            'creation_time' => $current_time
        ];

        if ($item_type_id === PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
            $params['content'] = $content;
        }

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
        FilePropertiesPOSTPATCHRepresentation $file_properties
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

            if ($file_properties->file_size === 0) {
                $this->empty_file_to_upload_finisher->createEmptyFile($document_to_upload);

                $representation = new CreatedItemRepresentation();
                $representation->build($document_to_upload->getItemId());

                return $representation;
            }
        } catch (UploadCreationConflictException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadCreationFileMismatchException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (UploadMaxSizeExceededException $exception) {
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
        return ($docman_item_post_representation->wiki_properties === null
            && $docman_item_post_representation->file_properties === null
            && $docman_item_post_representation->link_properties === null
            && $docman_item_post_representation->embedded_properties === null
        );
    }

    /**
     * @throws RestException
     */
    private function checkDocumentDoesNotAlreadyExists(DocmanItemPOSTRepresentation $representation)
    {
        if ($representation->type !== \Tuleap\Docman\REST\v1\ItemRepresentation::TYPE_FOLDER
            && $this->item_factory->doesTitleCorrespondToExistingDocument($representation->title, $representation->parent_id)
        ) {
            throw new RestException(400, "A document with same title already exists in the given folder.");
        }

        if ($representation->type === \Tuleap\Docman\REST\v1\ItemRepresentation::TYPE_FOLDER
            && $this->item_factory->doesTitleCorrespondToExistingFolder($representation->title, $representation->parent_id)
        ) {
            throw new RestException(400, "A folder with same title already exists in the given folder.");
        }
    }

    /**
     * @throws RestException
     */
    private function checkPropertiesByType(
        DocmanItemPOSTRepresentation $docman_item_post_representation,
        $checked_type
    ) : void {
        $types_with_properties = [
            ItemRepresentation::TYPE_WIKI,
            ItemRepresentation::TYPE_FILE,
            ItemRepresentation::TYPE_LINK,
            ItemRepresentation::TYPE_EMBEDDED
        ];

        foreach ($types_with_properties as $type) {
            if ($type === $checked_type && $docman_item_post_representation->{$type . "_properties"} === null) {
                throw new RestException(
                    400,
                    "Please provide " .$type . "_properties in order to create a $checked_type document."
                );
            }

            if ($type !== $checked_type && $docman_item_post_representation->{$type . "_properties"} !== null) {
                throw new RestException(
                    400,
                    $type . "_properties" . ' is not null while the given type is "' . $checked_type . '"'
                );
            }
        }
    }
}
