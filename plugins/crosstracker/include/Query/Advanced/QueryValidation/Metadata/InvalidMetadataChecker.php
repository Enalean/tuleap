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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata;

use Tuleap\CrossTracker\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final readonly class InvalidMetadataChecker
{
    public function __construct(
        private TextSemanticChecker $text_semantic_checker,
        private StatusChecker $status_checker,
        private AssignedToChecker $assigned_to_checker,
        private ArtifactSubmitterChecker $submitter_checker,
        private SubmissionDateChecker $submission_date_checker,
        private ArtifactIdMetadataChecker $artifact_id_metadata_checker,
    ) {
    }

    /**
     * @throws InvalidQueryException
     */
    public function checkComparisonIsValid(Metadata $metadata, Comparison $comparison): void
    {
        match ($metadata->getName()) {
            AllowedMetadata::TITLE,
            AllowedMetadata::DESCRIPTION => $this->text_semantic_checker->checkSemanticIsValidForComparison($comparison, $metadata),
            AllowedMetadata::STATUS => $this->status_checker->checkSemanticIsValidForComparison($comparison, $metadata),
            AllowedMetadata::ASSIGNED_TO => $this->assigned_to_checker->checkSemanticIsValidForComparison($comparison, $metadata),
            AllowedMetadata::SUBMITTED_ON,
            AllowedMetadata::LAST_UPDATE_DATE => $this->submission_date_checker->checkAlwaysThereFieldIsValidForComparison($comparison, $metadata),
            AllowedMetadata::SUBMITTED_BY,
            AllowedMetadata::LAST_UPDATE_BY => $this->submitter_checker->checkAlwaysThereFieldIsValidForComparison($comparison, $metadata),
            AllowedMetadata::ID => $this->artifact_id_metadata_checker->checkAlwaysThereFieldIsValidForComparison($comparison, $metadata),
            default => throw new \LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }
}
