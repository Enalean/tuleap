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

use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\RetrievedSectionContentFreetext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class SectionRepresentationBuilder
{
    public function __construct(
        private ArtifactSectionRepresentationBuilder $artifact_section_representation_builder,
    ) {
    }

    /**
     * @return Ok<SectionRepresentation>|Err<Fault>
     */
    public function getSectionRepresentation(
        RetrievedSection $section,
        RequiredSectionInformationCollector $collector,
        \PFUser $user,
    ): Ok|Err {
        return $section->content->apply(
            fn (int $artifact_id) => $collector->getCollectedRequiredSectionInformation($artifact_id)
                ->map(fn(RequiredArtifactInformation $info) => new SectionWrapper($section->id, $info))
                ->map(
                    /**
                     * @return SectionRepresentation
                     */
                    fn (SectionWrapper $wrapper) => $this->artifact_section_representation_builder->build(
                        $wrapper->required_info,
                        $wrapper->section_identifier,
                        $section->level,
                        $user,
                    )
                ),
            fn (RetrievedSectionContentFreetext $freetext) => Result::ok(
                $this->getSectionRepresentationForFreetext($section->id, $section->level, $freetext),
            )
        );
    }

    private function getSectionRepresentationForFreetext(
        SectionIdentifier $section_identifier,
        Level $level,
        RetrievedSectionContentFreetext $freetext,
    ): SectionRepresentation {
        return FreetextSectionRepresentation::fromRetrievedSectionContentFreetext(
            $section_identifier,
            $level,
            $freetext,
        );
    }
}
