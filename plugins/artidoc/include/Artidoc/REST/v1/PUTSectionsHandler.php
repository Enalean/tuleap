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
use Tuleap\Artidoc\Document\RawSection;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Document\SaveSections;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class PUTSectionsHandler
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private TransformRawSectionsToRepresentation $transformer,
        private SaveSections $dao,
        private SectionIdentifierFactory $identifier_factory,
    ) {
    }

    /**
     * @param ArtidocPUTSectionRepresentation[] $sections
     * @return Ok<true>|Err<Fault>
     */
    public function handle(int $id, array $sections, \PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanWrite($id)
            ->andThen(fn() => $this->ensureThatUserCanReadAllNewSections($id, $sections, $user))
            ->andThen(
                /**
                 * @param list<int> $artifact_ids
                 */
                fn(array $artifact_ids) => $this->saveSections($id, $artifact_ids)
            );
    }

    /**
     * @param ArtidocPUTSectionRepresentation[] $sections
     * @return Ok<list<int>>|Err<Fault>
     */
    private function ensureThatUserCanReadAllNewSections(int $id, array $sections, \PFUser $user): Ok|Err
    {
        $artifact_ids = [];
        foreach ($sections as $section) {
            $dummy_identifier = $this->identifier_factory->buildIdentifier();

            $artifact_ids[] = RawSection::fromRow([
                'artifact_id' => $section->artifact->id,
                'id'          => $dummy_identifier,
                'item_id'     => $id,
                'rank'        => 0,
            ]);
        }

        $raw_sections = new PaginatedRawSections($id, $artifact_ids, count($artifact_ids));

        return $this->transformer
            ->getRepresentation($raw_sections, $user)
            ->andThen(static fn() => Result::ok(array_column($artifact_ids, 'artifact_id')))
            ->mapErr(static fn(Fault $fault) => UserCannotReadSectionFault::fromFault($fault));
    }

    /**
     * @param list<int> $artifact_ids
     * @return Ok<true>|Err<Fault>
     */
    private function saveSections(int $id, array $artifact_ids): Ok|Err
    {
        $this->dao->save($id, $artifact_ids);

        return Result::ok(true);
    }
}
