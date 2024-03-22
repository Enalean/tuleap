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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Between\BetweenComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\CheckComparison;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\Equal\EqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\GreaterThan\GreaterThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\In\InComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\LesserThan\LesserThanOrEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotEqual\NotEqualComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\NotIn\NotInComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final readonly class FlatInvalidMetadataChecker implements CheckComparison
{
    public function __construct(
        private EqualComparisonChecker $equal_checker,
        private NotEqualComparisonChecker $not_equal_checker,
        private GreaterThanComparisonChecker $greater_than_checker,
        private GreaterThanOrEqualComparisonChecker $greater_than_or_equal_checker,
        private LesserThanComparisonChecker $lesser_than_checker,
        private LesserThanOrEqualComparisonChecker $lesser_than_or_equal_checker,
        private BetweenComparisonChecker $between_checker,
        private InComparisonChecker $in_checker,
        private NotInComparisonChecker $not_in_checker,
        private TextSemanticChecker $text_semantic_checker,
        private StatusChecker $status_checker,
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
            AllowedMetadata::SUBMITTED_ON,
            AllowedMetadata::LAST_UPDATE_DATE,
            AllowedMetadata::SUBMITTED_BY,
            AllowedMetadata::LAST_UPDATE_BY,
            AllowedMetadata::ASSIGNED_TO => $this->matchOnComparisonType($metadata, $comparison),
            default => throw new \LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }

    /**
     * @throws InvalidQueryException
     */
    private function matchOnComparisonType(Metadata $metadata, Comparison $comparison): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal => $this->equal_checker->checkComparisonIsValid($metadata, $comparison),
            ComparisonType::NotEqual => $this->not_equal_checker->checkComparisonIsValid($metadata, $comparison),
            ComparisonType::LesserThan => $this->lesser_than_checker->checkComparisonIsValid($metadata, $comparison),
            ComparisonType::LesserThanOrEqual => $this->lesser_than_or_equal_checker->checkComparisonIsValid($metadata, $comparison),
            ComparisonType::GreaterThan => $this->greater_than_checker->checkComparisonIsValid($metadata, $comparison),
            ComparisonType::GreaterThanOrEqual => $this->greater_than_or_equal_checker->checkComparisonIsValid($metadata, $comparison),
            ComparisonType::Between => $this->between_checker->checkComparisonIsValid($metadata, $comparison),
            ComparisonType::In => $this->in_checker->checkComparisonIsValid($metadata, $comparison),
            ComparisonType::NotIn => $this->not_in_checker->checkComparisonIsValid($metadata, $comparison),
        };
    }
}
