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

final class BeforeCopyVisitor implements ItemVisitor
{
    /**
     * @var string
     * @psalm-var class-string<Docman_Item>
     */
    private $expected_item_class_to_copy;
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;

    /**
     * @psalm-param class-string<Docman_Item> $expected_item_class_to_copy
     */
    public function __construct(string $expected_item_class_to_copy, Docman_ItemFactory $item_factory)
    {
        $this->expected_item_class_to_copy = $expected_item_class_to_copy;
        $this->item_factory                = $item_factory;
    }

    public function visitFolder(Docman_Folder $item, array $params = []) : ItemBeingCopiedExpectation
    {
        return $this->handleItem($item, $params['destination'], $this->isFolderTitleConflictingVerifier());
    }

    public function visitWiki(Docman_Wiki $item, array $params = []) : ItemBeingCopiedExpectation
    {
        return $this->handleItem($item, $params['destination'], $this->isDocumentTitleConflictingVerifier());
    }

    public function visitLink(Docman_Link $item, array $params = []) : ItemBeingCopiedExpectation
    {
        return $this->handleItem($item, $params['destination'], $this->isDocumentTitleConflictingVerifier());
    }

    public function visitFile(Docman_File $item, array $params = []) : ItemBeingCopiedExpectation
    {
        return $this->handleItem($item, $params['destination'], $this->isDocumentTitleConflictingVerifier());
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []) : ItemBeingCopiedExpectation
    {
        return $this->handleItem($item, $params['destination'], $this->isDocumentTitleConflictingVerifier());
    }

    public function visitEmpty(Docman_Empty $item, array $params = []) : ItemBeingCopiedExpectation
    {
        return $this->handleItem($item, $params['destination'], $this->isDocumentTitleConflictingVerifier());
    }

    public function visitItem(Docman_Item $item, array $params = []) : ItemBeingCopiedExpectation
    {
        throw new LogicException('Cannot copy a non specialized item');
    }

    /**
     * @psalm-param callable(string, Docman_Folder): bool $does_title_conflict
     * @throws RestException
     */
    private function handleItem(Docman_Item $item, Docman_Folder $destination, callable $does_title_conflict) : ItemBeingCopiedExpectation
    {
        if (get_class($item) !== $this->expected_item_class_to_copy) {
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
    private function isDocumentTitleConflictingVerifier() : callable
    {
        return function (string $title, Docman_Folder $destination): bool {
            return $this->item_factory->doesTitleCorrespondToExistingDocument($title, $destination->getId());
        };
    }

    /**
     * @psalm-return callable(string, Docman_Folder): bool
     */
    private function isFolderTitleConflictingVerifier() : callable
    {
        return function (string $title, Docman_Folder $destination): bool {
            return $this->item_factory->doesTitleCorrespondToExistingFolder($title, $destination->getId());
        };
    }
}
