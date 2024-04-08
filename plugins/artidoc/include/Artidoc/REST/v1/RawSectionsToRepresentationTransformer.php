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

use Tracker;
use Tuleap\Artidoc\Document\PaginatedRawSections;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentationBuilder;
use Tuleap\Tracker\REST\Artifact\StatusValueRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\TrackerRepresentation;

final readonly class RawSectionsToRepresentationTransformer implements TransformRawSectionsToRepresentation
{
    public const DEFAULT_TRACKER_REPRESENTATION      = null;
    public const DEFAULT_STATUS_VALUE_REPRESENTATION = null;

    /**
     * @var \Closure(Tracker): TrackerRepresentation
     */
    private \Closure $get_tracker_representation;
    /**
     * @var \Closure(Artifact, \PFUser): StatusValueRepresentation
     */
    private \Closure $get_status_value_representation;

    /**
     * @param ?\Closure(Tracker): TrackerRepresentation $get_tracker_representation
     * @param ?\Closure(Artifact, \PFUser): StatusValueRepresentation $get_status_value_representation
     */
    public function __construct(
        private \Tracker_ArtifactDao $artifact_dao,
        private \Tracker_ArtifactFactory $artifact_factory,
        private ArtifactRepresentationBuilder $artifact_representation_builder,
        ?\Closure $get_tracker_representation,
        ?\Closure $get_status_value_representation,
    ) {
        $this->get_tracker_representation      = $get_tracker_representation ?? MinimalTrackerRepresentation::build(...);
        $this->get_status_value_representation = $get_status_value_representation ?? StatusValueRepresentation::buildFromArtifact(...);
    }

    public function getRepresentation(PaginatedRawSections $raw_sections, \PFUser $user): Ok|Err
    {
        return $this->instantiateArtifacts($raw_sections, $user)
            ->andThen(fn (array $artifacts) => $this->instantiateSections($artifacts, $user))
            ->map(
                /**
                 * @param list<ArtidocSectionRepresentation> $sections
                 */
                static fn (array $sections) => new PaginatedArtidocSectionRepresentationCollection($sections, $raw_sections->total)
            );
    }

    /**
     * @return Ok<list<Artifact>>|Err<Fault>
     */
    private function instantiateArtifacts(PaginatedRawSections $raw_sections, \PFUser $user): Ok|Err
    {
        $artifact_ids = array_column($raw_sections->rows, 'artifact_id');
        if (count($artifact_ids) === 0) {
            return Result::ok([]);
        }

        $artifacts = [];
        foreach ($this->artifact_dao->searchByIds($artifact_ids) as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if (! $artifact->userCanView($user)) {
                return Result::err(Fault::fromMessage('User cannot read one of the artifact of artidoc #' . $raw_sections->id));
            }

            $artifacts[] = $artifact;
        }

        return Result::ok($artifacts);
    }

    /**
     * @param list<Artifact> $artifacts
     * @return Ok<list<ArtidocSectionRepresentation>>|Err<Fault>
     */
    private function instantiateSections(array $artifacts, \PFUser $user): Ok|Err
    {
        $sections = [];
        foreach ($artifacts as $artifact) {
            $sections[] = new ArtidocSectionRepresentation(
                $this->artifact_representation_builder->getArtifactRepresentationWithFieldValues(
                    $user,
                    $artifact,
                    call_user_func($this->get_tracker_representation, $artifact->getTracker()),
                    call_user_func($this->get_status_value_representation, $artifact, $user),
                )
            );
        }

        return Result::ok($sections);
    }
}
