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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata;

use LogicException;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Title\TitleFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

final readonly class MetadataFromWhereBuilder implements FromWhereBuilder
{
    public function __construct(
        private EqualComparisonFromWhereBuilder $metadata_equal_builder,
        private NotEqualComparisonFromWhereBuilder $metadata_not_equal_builder,
        private GreaterThanComparisonFromWhereBuilder $metadata_greater_than_builder,
        private GreaterThanOrEqualComparisonFromWhereBuilder $metadata_greater_than_or_equal_builder,
        private LesserThanComparisonFromWhereBuilder $metadata_lesser_than_builder,
        private LesserThanOrEqualComparisonFromWhereBuilder $metadata_lesser_than_or_equal_builder,
        private BetweenComparisonFromWhereBuilder $metadata_between_builder,
        private InComparisonFromWhereBuilder $metadata_in_builder,
        private NotInComparisonFromWhereBuilder $metadata_not_in_builder,
        private TitleFromWhereBuilder $title_builder,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function getFromWhere(
        Metadata $metadata,
        Comparison $comparison,
        array $trackers,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        return match ($metadata->getName()) {
            AllowedMetadata::TITLE       => $this->title_builder->getFromWhere($comparison),
            AllowedMetadata::DESCRIPTION,
            AllowedMetadata::STATUS,
            AllowedMetadata::SUBMITTED_ON,
            AllowedMetadata::LAST_UPDATE_DATE,
            AllowedMetadata::SUBMITTED_BY,
            AllowedMetadata::LAST_UPDATE_BY,
            AllowedMetadata::ASSIGNED_TO => $this->matchOnComparisonType($metadata, $comparison, $trackers),
            default                      => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }

    /**
     * @param Tracker[] $trackers
     */
    private function matchOnComparisonType(
        Metadata $metadata,
        Comparison $comparison,
        array $trackers,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        return match ($comparison->getType()) {
            ComparisonType::Equal              => $this->metadata_equal_builder->getFromWhere($metadata, $comparison, $trackers),
            ComparisonType::NotEqual           => $this->metadata_not_equal_builder->getFromWhere($metadata, $comparison, $trackers),
            ComparisonType::LesserThan         => $this->metadata_lesser_than_builder->getFromWhere($metadata, $comparison, $trackers),
            ComparisonType::GreaterThan        => $this->metadata_greater_than_builder->getFromWhere($metadata, $comparison, $trackers),
            ComparisonType::LesserThanOrEqual  => $this->metadata_lesser_than_or_equal_builder->getFromWhere($metadata, $comparison, $trackers),
            ComparisonType::GreaterThanOrEqual => $this->metadata_greater_than_or_equal_builder->getFromWhere($metadata, $comparison, $trackers),
            ComparisonType::Between            => $this->metadata_between_builder->getFromWhere($metadata, $comparison, $trackers),
            ComparisonType::In                 => $this->metadata_in_builder->getFromWhere($metadata, $comparison, $trackers),
            ComparisonType::NotIn              => $this->metadata_not_in_builder->getFromWhere($metadata, $comparison, $trackers),
        };
    }
}
