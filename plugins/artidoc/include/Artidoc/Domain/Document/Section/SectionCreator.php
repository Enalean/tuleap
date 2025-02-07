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

namespace Tuleap\Artidoc\Domain\Document\Section;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\SectionContentToBeCreatedFreetext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\Option\Option;

final readonly class SectionCreator
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private SaveOneSection $save_section,
        private CollectRequiredSectionInformation $collect_required_section_information_for_creation,
    ) {
    }

    /**
     * @param Option<SectionIdentifier> $before_section_id
     * @return Ok<SectionIdentifier>|Err<Fault>
     */
    public function create(int $id, Option $before_section_id, Level $level, SectionContentToBeCreated $content): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanWrite($id)
            ->andThen(fn (ArtidocWithContext $artidoc) => $content->apply(
                fn (int $artifact_id) => $this->collect_required_section_information_for_creation
                    ->collectRequiredSectionInformation($artidoc, $artifact_id)
                    ->andThen(fn () => $this->saveSection($artidoc, ContentToInsert::fromArtifactId($artifact_id, $level), $before_section_id)),
                fn (SectionContentToBeCreatedFreetext $freetext) => $this->saveSection($artidoc, ContentToInsert::fromFreetext($freetext->content, $level), $before_section_id)
            ));
    }

    /**
     * @param Option<SectionIdentifier> $before_section_id
     * @return Ok<SectionIdentifier>|Err<Fault>
     */
    private function saveSection(
        ArtidocWithContext $artidoc,
        ContentToInsert $content,
        Option $before_section_id,
    ): Ok|Err {
        return $before_section_id->match(
            fn (SectionIdentifier $sibling_section_id) => $this->save_section->saveSectionBefore($artidoc, $content, $sibling_section_id),
            fn () => $this->save_section->saveSectionAtTheEnd($artidoc, $content),
        );
    }
}
