<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\MoveItem;

use DateTimeImmutable;
use Docman_Document;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_Link;
use Docman_Wiki;
use LogicException;
use Luracast\Restler\RestException;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;

/**
 * @template-implements ItemVisitor<void>
 */
final class BeforeMoveVisitor implements ItemVisitor
{
    /**
     * @var DoesItemHasExpectedTypeVisitor
     */
    private $does_item_has_expected_type;
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var DocumentOngoingUploadRetriever
     */
    private $document_ongoing_upload_retriever;

    public function __construct(
        DoesItemHasExpectedTypeVisitor $does_item_has_expected_type,
        Docman_ItemFactory $item_factory,
        DocumentOngoingUploadRetriever $document_ongoing_upload_retriever
    ) {
        $this->does_item_has_expected_type       = $does_item_has_expected_type;
        $this->item_factory                      = $item_factory;
        $this->document_ongoing_upload_retriever = $document_ongoing_upload_retriever;
    }

    /**
     * @throws RestException
     */
    public function visitFolder(Docman_Folder $folder_to_move, array $params = []): void
    {
        $this->handleItem($folder_to_move);
        $destination_folder = $params['destination'];
        assert($destination_folder instanceof Docman_Folder);

        $folder_title = $folder_to_move->getTitle();
        if ($this->item_factory->doesTitleCorrespondToExistingFolder($folder_title, $destination_folder->getId())) {
            throw new RestException(400, 'A folder with same title already exists in the destination folder');
        }

        if ($destination_folder->getId() === $folder_to_move->getId()) {
            throw new RestException(400, 'Cannot move a folder into itself');
        }

        if ($this->item_factory->isInSubTree($destination_folder->getId(), $folder_to_move->getId())) {
            throw new RestException(400, 'Cannot move a folder into one of its child');
        }
    }

    /**
     * @throws RestException
     */
    public function visitWiki(Docman_Wiki $item, array $params = []): void
    {
        $this->handleDocument($item, $params['destination'], $params['current_time']);
    }

    /**
     * @throws RestException
     */
    public function visitLink(Docman_Link $item, array $params = []): void
    {
        $this->handleDocument($item, $params['destination'], $params['current_time']);
    }

    /**
     * @throws RestException
     */
    public function visitFile(Docman_File $item, array $params = []): void
    {
        $this->handleDocument($item, $params['destination'], $params['current_time']);
    }

    /**
     * @throws RestException
     */
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): void
    {
        $this->handleDocument($item, $params['destination'], $params['current_time']);
    }

    /**
     * @throws RestException
     */
    public function visitEmpty(Docman_Empty $item, array $params = []): void
    {
        $this->handleDocument($item, $params['destination'], $params['current_time']);
    }

    public function visitItem(Docman_Item $item, array $params = []): void
    {
        throw new LogicException('Cannot move a non specialized item');
    }

    /**
     * @throws RestException
     */
    private function handleDocument(
        Docman_Document $document_to_move,
        Docman_Folder $destination,
        DateTimeImmutable $current_time
    ): void {
        $this->handleItem($document_to_move);

        $document_title = $document_to_move->getTitle();
        if ($this->item_factory->doesTitleCorrespondToExistingDocument($document_title, $destination->getId())) {
            throw new RestException(400, 'A document with same title already exists in the destination folder');
        }

        $is_document_being_uploaded = $this->document_ongoing_upload_retriever->isThereAlreadyAnUploadOngoing(
            $destination,
            $document_title,
            $current_time
        );
        if ($is_document_being_uploaded) {
            throw new RestException(409, 'A document with the same title is being uploaded in the destination folder');
        }
    }

    /**
     * @throws RestException
     */
    private function handleItem(Docman_Item $item_to_move): void
    {
        $this->checkTypeExpectation($item_to_move);

        if (! $this->item_factory->isMoveable($item_to_move)) {
            throw new RestException(
                400,
                'The item is not movable, the document is the only child of the root or the root itself'
            );
        }
    }

    /**
     * @throws RestException
     */
    private function checkTypeExpectation(Docman_Item $item): void
    {
        if (! $item->accept($this->does_item_has_expected_type)) {
            throw new RestException(400, 'The item to move does not match the type expected by the route');
        }
    }
}
