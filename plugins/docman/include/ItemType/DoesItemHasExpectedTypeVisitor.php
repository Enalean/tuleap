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

namespace Tuleap\Docman\ItemType;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;

/**
 * @template-implements ItemVisitor<bool>
 */
final class DoesItemHasExpectedTypeVisitor implements ItemVisitor
{
    /**
     * @var string
     * @psalm-var class-string<Docman_Item>
     */
    private $expected_item_class;

    /**
     * @psalm-param class-string<Docman_Item> $expected_item_class
     */
    public function __construct(string $expected_item_class)
    {
        $this->expected_item_class = $expected_item_class;
    }

    /**
     * @psalm-return class-string<Docman_Item>
     */
    public function getExpectedItemClass(): string
    {
        return $this->expected_item_class;
    }

    public function visitFolder(Docman_Folder $item, array $params = []): bool
    {
        return $this->visitItem($item);
    }

    public function visitWiki(Docman_Wiki $item, array $params = []): bool
    {
        return $this->visitItem($item);
    }

    public function visitLink(Docman_Link $item, array $params = []): bool
    {
        return $this->visitItem($item);
    }

    public function visitFile(Docman_File $item, array $params = []): bool
    {
        return $this->visitItem($item);
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): bool
    {
        return $this->visitItem($item);
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): bool
    {
        return $this->visitItem($item);
    }

    public function visitItem(Docman_Item $item, array $params = []): bool
    {
        return get_class($item) === $this->expected_item_class;
    }
}
