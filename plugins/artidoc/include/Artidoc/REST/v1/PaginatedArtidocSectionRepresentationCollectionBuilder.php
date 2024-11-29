<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Document\PaginatedRawSections;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Document\SearchPaginatedRawSections;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

final readonly class PaginatedArtidocSectionRepresentationCollectionBuilder
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private SearchPaginatedRawSections $dao,
        private TransformRawSectionsToRepresentation $transformer,
    ) {
    }

    /**
     * @return Ok<PaginatedArtidocSectionRepresentationCollection>|Err<Fault>
     */
    public function build(int $id, int $limit, int $offset, \PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanRead($id)
            ->map(fn () => $this->dao->searchPaginatedRawSectionsByItemId($id, $limit, $offset))
            ->andThen(fn (PaginatedRawSections $raw_sections) => $this->transformer->getRepresentation($raw_sections, $user));
    }
}
