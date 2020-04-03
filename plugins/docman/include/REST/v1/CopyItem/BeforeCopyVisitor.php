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

namespace Tuleap\Docman\REST\v1\CopyItem;

use DateTimeImmutable;
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
use Tuleap\REST\I18NRestException;

/**
 * @template-implements ItemVisitor<ItemBeingCopiedExpectation>
 */
final class BeforeCopyVisitor implements ItemVisitor
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

    public function visitFolder(Docman_Folder $item, array $params = []): ItemBeingCopiedExpectation
    {
        return $this->handleItem($item, $params['destination'], $this->isFolderTitleConflictingVerifier());
    }

    public function visitWiki(Docman_Wiki $item, array $params = []): ItemBeingCopiedExpectation
    {
        return $this->handleDocument($params['current_time'], $item, $params['destination']);
    }

    public function visitLink(Docman_Link $item, array $params = []): ItemBeingCopiedExpectation
    {
        return $this->handleDocument($params['current_time'], $item, $params['destination']);
    }

    public function visitFile(Docman_File $item, array $params = []): ItemBeingCopiedExpectation
    {
        return $this->handleDocument($params['current_time'], $item, $params['destination']);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): ItemBeingCopiedExpectation
    {
        return $this->handleDocument($params['current_time'], $item, $params['destination']);
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): ItemBeingCopiedExpectation
    {
        return $this->handleDocument($params['current_time'], $item, $params['destination']);
    }

    public function visitItem(Docman_Item $item, array $params = []): ItemBeingCopiedExpectation
    {
        throw new LogicException('Cannot copy a non specialized item');
    }

    /**
     * @psalm-param callable(string, Docman_Folder): bool $does_title_conflict
     * @throws RestException
     */
    private function handleDocument(
        DateTimeImmutable $current_time,
        Docman_Item $item,
        Docman_Folder $destination
    ): ItemBeingCopiedExpectation {
        $is_document_being_uploaded = $this->document_ongoing_upload_retriever->isThereAlreadyAnUploadOngoing(
            $destination,
            $item->getTitle(),
            $current_time
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

        return $this->handleItem($item, $destination, $this->isDocumentTitleConflictingVerifier());
    }

    /**
     * @psalm-param callable(string, Docman_Folder): bool $does_title_conflict
     * @throws RestException
     */
    private function handleItem(Docman_Item $item, Docman_Folder $destination, callable $does_title_conflict): ItemBeingCopiedExpectation
    {
        if (! $item->accept($this->does_item_has_expected_type)) {
            throw new RestException(400, 'The item to copy does not match the one expected by the route');
        }

        $item_to_copy_title = $item->getTitle();
        $expected_title     = $item_to_copy_title;
        while ($does_title_conflict($expected_title, $destination)) {
            $expected_title = sprintf(dgettext('tuleap-docman', 'Copy of %s'), $expected_title);
        }

        return new ItemBeingCopiedExpectation($expected_title);
    }

    /**
     * @psalm-return callable(string, Docman_Folder): bool
     */
    private function isDocumentTitleConflictingVerifier(): callable
    {
        return function (string $title, Docman_Folder $destination): bool {
            return $this->item_factory->doesTitleCorrespondToExistingDocument($title, $destination->getId());
        };
    }

    /**
     * @psalm-return callable(string, Docman_Folder): bool
     */
    private function isFolderTitleConflictingVerifier(): callable
    {
        return function (string $title, Docman_Folder $destination): bool {
            return $this->item_factory->doesTitleCorrespondToExistingFolder($title, $destination->getId());
        };
    }
}
