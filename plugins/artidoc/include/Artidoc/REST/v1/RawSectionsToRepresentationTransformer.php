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

use Tuleap\Artidoc\Domain\Document\Section\PaginatedRawSections;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;

final readonly class RawSectionsToRepresentationTransformer implements TransformRawSectionsToRepresentation
{
    public function __construct(
        private \Tracker_ArtifactDao $artifact_dao,
        private \Tracker_ArtifactFactory $artifact_factory,
        private SectionRepresentationBuilder $section_representation_builder,
        private RequiredArtifactInformationBuilder $required_artifact_information_builder,
    ) {
    }

    public function getRepresentation(ArtidocWithContext $artidoc, PaginatedRawSections $raw_sections, \PFUser $user): Ok|Err
    {
        return $this->instantiateArtifacts($raw_sections, $user)
            ->andThen(fn (array $artifacts) => $this->instantiateSections($artidoc, $artifacts, $user))
            ->map(
                /**
                 * @param list<ArtidocSectionRepresentation> $sections
                 */
                static fn (array $sections) => new PaginatedArtidocSectionRepresentationCollection($sections, $raw_sections->total)
            );
    }

    /**
     * @return Ok<list<array{artifact: Artifact, section_identifier: SectionIdentifier}>>|Err<Fault>
     */
    private function instantiateArtifacts(PaginatedRawSections $raw_sections, \PFUser $user): Ok|Err
    {
        $identifiers  = [];
        $artifact_ids = [];
        foreach ($raw_sections->rows as $row) {
            $artifact_ids[]                 = $row->artifact_id;
            $identifiers[$row->artifact_id] = $row->id;
        }
        if (count($artifact_ids) === 0) {
            return Result::ok([]);
        }

        $artifact_order = array_flip($artifact_ids);

        $artifacts = [];
        foreach ($this->artifact_dao->searchByIds($artifact_ids) as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if (! $artifact->userCanView($user)) {
                return Result::err(Fault::fromMessage('User cannot read one of the artifact of artidoc #' . $raw_sections->id));
            }

            $id = $artifact->getId();

            $artifacts[$artifact_order[$id]] = [
                'artifact'           => $artifact,
                'section_identifier' => $identifiers[$id],
            ];
        }

        ksort($artifacts);

        return Result::ok(array_values($artifacts));
    }

    /**
     * @param list<array{artifact: Artifact, section_identifier: SectionIdentifier}> $artifacts
     * @return Ok<list<ArtidocSectionRepresentation>>|Err<Fault>
     */
    private function instantiateSections(ArtidocWithContext $artidoc, array $artifacts, \PFUser $user): Ok|Err
    {
        $sections = [];
        foreach ($artifacts as $section) {
            $artifact = $section['artifact'];

            $result = $this->required_artifact_information_builder
                ->getRequiredArtifactInformation($artidoc, $artifact, $user)
                ->andThen(
                    function (RequiredArtifactInformation $artifact_information) use ($user, $section, &$sections) {
                        $sections[] = $this->section_representation_builder->build(
                            $artifact_information,
                            $section['section_identifier'],
                            $user,
                        );

                        return Result::ok(true);
                    },
                );

            if (Result::isErr($result)) {
                return $result;
            }
        }

        return Result::ok($sections);
    }
}
