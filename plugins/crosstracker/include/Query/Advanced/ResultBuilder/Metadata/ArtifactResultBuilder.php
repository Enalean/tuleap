<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata;

use LogicException;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\ArtifactRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Query\InstantiateRetrievedQueryTrackers;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\Tracker\Artifact\RetrieveArtifact;

final readonly class ArtifactResultBuilder
{
    public function __construct(
        private RetrieveArtifact $artifact_retriever,
        private InstantiateRetrievedQueryTrackers $query_tracker_ids_retriever,
    ) {
    }

    public function getResult(array $select_results, PFUser $user): SelectedValuesCollection
    {
        $values = [];

        foreach ($select_results as $result) {
            $id = $result['id'];
            if (isset($values[$id])) {
                continue;
            }

            $artifact = $this->artifact_retriever->getArtifactById($id);
            if ($artifact === null) {
                throw new LogicException("Artifact #$id not found");
            }

            $linked_artifacts    = $artifact->getLinkedAndReverseArtifacts($user);
            $tracker_ids         = array_map(
                fn ($linked_artifact) => $linked_artifact->getTrackerId(),
                $linked_artifacts
            );
            $allowed_tracker_ids = array_map(
                fn ($allowed_tracker) => $allowed_tracker->getId(),
                $this->query_tracker_ids_retriever->getTrackers($tracker_ids)
            );

            $number_of_forward_link = count(
                array_filter(
                    $artifact->getLinkedArtifacts($user),
                    fn ($linked_artifact) => in_array($linked_artifact->getTrackerId(), $allowed_tracker_ids, true)
                )
            );
            $number_of_reverse_link = count(
                array_filter(
                    $artifact->getLinkedAndReverseArtifacts($user),
                    fn ($linked_artifact) => in_array($linked_artifact->getTrackerId(), $allowed_tracker_ids, true)
                )
            ) - $number_of_forward_link;

            $values[$id] = new SelectedValue('@artifact', new ArtifactRepresentation(
                $artifact->getId(),
                $artifact->getUri(),
                $number_of_forward_link,
                $number_of_reverse_link,
            ));
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@artifact', CrossTrackerSelectedType::TYPE_ARTIFACT),
            $values,
        );
    }
}
