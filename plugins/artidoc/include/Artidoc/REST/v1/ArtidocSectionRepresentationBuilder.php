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
use Tuleap\Artidoc\Document\SearchOneSection;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class ArtidocSectionRepresentationBuilder
{
    public function __construct(
        private SearchOneSection $dao,
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private TransformRawSectionsToRepresentation $transformer,
    ) {
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    public function build(SectionIdentifier $id, \PFUser $user): Ok|Err
    {
        $row = $this->dao->searchSectionById($id);
        if ($row === null) {
            return Result::err(Fault::fromMessage('Unable to find section'));
        }

        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanRead($row->item_id)
            ->andThen(fn () => $this->transformer->getRepresentation(new PaginatedRawSections($row->item_id, [$row], 1), $user))
            ->andThen($this->getFirstAndOnlySectionFromCollection(...));
    }

    private function getFirstAndOnlySectionFromCollection(
        PaginatedArtidocSectionRepresentationCollection $collection,
    ): Ok|Err {
        if (count($collection->sections) !== 1) {
            return Result::err(Fault::fromMessage('We should have exactly one matching section'));
        }

        return Result::ok($collection->sections[0]);
    }
}
