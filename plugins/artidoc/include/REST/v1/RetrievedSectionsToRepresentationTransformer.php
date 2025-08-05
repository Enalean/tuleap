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
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSections;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class RetrievedSectionsToRepresentationTransformer implements TransformRetrievedSectionsToRepresentation
{
    public function __construct(
        private SectionRepresentationBuilder $section_representation_builder,
        private RequiredSectionInformationCollector $required_section_information_collector,
    ) {
    }

    public function getRepresentation(PaginatedRetrievedSections $retrieved_sections, \PFUser $user): Ok|Err
    {
        return $this->collectRequiredSectionInformationForAllSections($retrieved_sections)
            ->andThen(function () use ($retrieved_sections, $user) {
                $representations = [];
                foreach ($retrieved_sections->rows as $section) {
                    $result = $this->section_representation_builder
                        ->getSectionRepresentation($section, $this->required_section_information_collector, $user);

                    if (Result::isErr($result)) {
                        return $result;
                    }

                    $result->map(
                        static function (SectionRepresentation $representation) use (&$representations) {
                            $representations[] = $representation;
                        },
                    );
                }

                return Result::ok(new PaginatedArtidocSectionRepresentationCollection($representations, $retrieved_sections->total));
            });
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function collectRequiredSectionInformationForAllSections(PaginatedRetrievedSections $retrieved_sections): Ok|Err
    {
        foreach ($retrieved_sections->rows as $section) {
            $result = $section->content->apply(
                function (int $artifact_id) use ($retrieved_sections) {
                    return $this->required_section_information_collector
                        ->collectRequiredSectionInformation($retrieved_sections->artidoc, $artifact_id);
                },
                static fn () => Result::ok(null),
            );

            if (Result::isErr($result)) {
                return $result;
            }
        }

        return Result::ok(null);
    }
}
