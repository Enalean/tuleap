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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata;

use LogicException;
use PFUser;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\AlwaysThereField\ArtifactId\ArtifactIdResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Date\MetadataDateResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Semantic\AssignedTo\AssignedToResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Semantic\Status\StatusResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Special\ProjectName\ProjectNameResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Special\TrackerName\TrackerNameResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\Text\MetadataTextResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Metadata\User\MetadataUserResultBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final readonly class MetadataResultBuilder
{
    public function __construct(
        private MetadataTextResultBuilder $text_builder,
        private StatusResultBuilder $status_builder,
        private AssignedToResultBuilder $assigned_to_builder,
        private MetadataDateResultBuilder $date_builder,
        private MetadataUserResultBuilder $user_builder,
        private ArtifactIdResultBuilder $artifact_id_builder,
        private ProjectNameResultBuilder $project_name_builder,
        private TrackerNameResultBuilder $tracker_name_builder,
    ) {
    }

    public function getResult(
        Metadata $metadata,
        array $select_results,
        PFUser $user,
    ): SelectedValuesCollection {
        return match ($metadata->getName()) {
            // Semantics
            AllowedMetadata::TITLE,
            AllowedMetadata::DESCRIPTION      => $this->text_builder->getResult($metadata, $select_results),
            AllowedMetadata::STATUS           => $this->status_builder->getResult($select_results),
            AllowedMetadata::ASSIGNED_TO      => $this->assigned_to_builder->getResult($select_results),

            // Always there fields
            AllowedMetadata::SUBMITTED_ON,
            AllowedMetadata::LAST_UPDATE_DATE => $this->date_builder->getResult($metadata, $select_results, $user),
            AllowedMetadata::SUBMITTED_BY,
            AllowedMetadata::LAST_UPDATE_BY   => $this->user_builder->getResult($metadata, $select_results),
            AllowedMetadata::ID               => $this->artifact_id_builder->getResult($select_results),

            // Custom fields
            AllowedMetadata::PROJECT_NAME     => $this->project_name_builder->getResult($select_results),
            AllowedMetadata::TRACKER_NAME     => $this->tracker_name_builder->getResult($select_results),
            AllowedMetadata::PRETTY_TITLE     => new SelectedValuesCollection(null, []),
            default                           => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }
}
