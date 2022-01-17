<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Item;

use Tuleap\Docman\REST\v1\Folders\ParentFolderRepresentation;

/**
 * @psalm-immutable
 */
final class PaginatedParentRowCollection
{
    private int $size;

    /**
     * @var ParentFolderRepresentation[]
     */
    private array $paginated_element_collection;

    /**
     * @param $paginated_element_collection ParentFolderRepresentation[]
     */
    public function __construct(array $paginated_element_collection, int $size)
    {
        $this->paginated_element_collection = $paginated_element_collection;
        $this->size                         = $size;
    }

    /**
     * @return ParentFolderRepresentation[]
     */
    public function getPaginatedElementCollection(): array
    {
        return $this->paginated_element_collection;
    }

    public function getTotalSize(): int
    {
        return $this->size;
    }
}
