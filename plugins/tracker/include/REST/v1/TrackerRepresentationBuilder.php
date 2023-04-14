<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Project;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\Tracker\REST\TrackerRepresentation;
use Tuleap\Tracker\RetrieveTrackersByGroupIdAndUserCanView;
use Tuleap\Tracker\Semantic\ArtifactCannotBeCreatedReasonsGetter;
use Tuleap\Tracker\Semantic\CollectionOfCreationSemanticToCheck;

final class TrackerRepresentationBuilder
{
    public function __construct(private readonly RetrieveTrackersByGroupIdAndUserCanView $tracker_retriever, private readonly BuildCompleteTrackerRESTRepresentation $full_representation_builder, private readonly ArtifactCannotBeCreatedReasonsGetter $creation_semantic_checker)
    {
    }

    /**
     * @param CompleteTrackerRepresentation::FULL_REPRESENTATION | MinimalTrackerRepresentation::MINIMAL_REPRESENTATION $tracker_representation
     * @return TrackerRepresentation[]
     * @throws RestException
     */
    public function buildTrackerRepresentations(
        PFUser $user,
        Project $project,
        string $tracker_representation,
        int $limit,
        int $offset,
        bool $filter_on_tracker_administration_permission,
        CollectionOfCreationSemanticToCheck $with_creation_semantic_check,
    ): array {
        if (! $with_creation_semantic_check->isEmpty() && $tracker_representation === CompleteTrackerRepresentation::FULL_REPRESENTATION) {
            throw new RestException(400, "'with_creation_semantic_check' is not available when 'full' tracker representation is chosen");
        }

        $all_trackers = $this->tracker_retriever->getTrackersByGroupIdUserCanView(
            $project->getId(),
            $user
        );
        $trackers     = array_slice($all_trackers, $offset, $limit);

        $tracker_representations = [];

        foreach ($trackers as $tracker) {
            if ($filter_on_tracker_administration_permission && ! $tracker->userIsAdmin($user)) {
                continue;
            }
            if ($tracker_representation === MinimalTrackerRepresentation::MINIMAL_REPRESENTATION) {
                $tracker_minimal_representation = MinimalTrackerRepresentation::withCannotCreateArtifactReasons(
                    $tracker,
                    $this->creation_semantic_checker->getCannotCreateArtifactReasons($with_creation_semantic_check, $tracker, $user)
                );
                $tracker_representations[]      = $tracker_minimal_representation;
            } else {
                $tracker_representations[] = $this->full_representation_builder->getTrackerRepresentationInTrackerContext($user, $tracker);
            }
        }
        return $tracker_representations;
    }
}
