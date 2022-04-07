<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\Item\PaginatedFileVersionRepresentationCollection;
use Tuleap\Docman\REST\v1\Files\FileVersionRepresentation;
use Tuleap\Docman\Version\VersionDao;

final class VersionRepresentationCollectionBuilder
{
    public function __construct(private VersionDao $docman_version_dao)
    {
    }

    public function buildVersionsCollection(
        \Docman_Item $item,
        int $limit,
        int $offset,
    ): PaginatedFileVersionRepresentationCollection {
        $dar      = $this->docman_version_dao->searchByItemId($item->getId(), $offset, $limit);
        $versions = [];
        foreach ($dar as $row) {
            $versions[] = FileVersionRepresentation::build(
                $row["number"],
                $row["label"],
                $row["filename"],
                (int) $item->getGroupId(),
                (int) $item->getId()
            );
        }

        return new PaginatedFileVersionRepresentationCollection($versions, $this->docman_version_dao->countByItemId($item->getId()));
    }
}
