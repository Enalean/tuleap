<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata;

use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class GreaterThanOrEqualComparisonFromWhereBuilder implements FromWhereBuilder
{
    /**
     * @var AlwaysThereField\Date\GreaterThanOrEqualComparisonFromWhereBuilder
     */
    private $submitted_on_builder;
    /**
     * @var AlwaysThereField\Date\GreaterThanOrEqualComparisonFromWhereBuilder
     */
    private $last_update_date_builder;

    public function __construct(
        AlwaysThereField\Date\GreaterThanOrEqualComparisonFromWhereBuilder $submitted_on_builder,
        AlwaysThereField\Date\GreaterThanOrEqualComparisonFromWhereBuilder $last_update_date_builder,
    ) {
        $this->submitted_on_builder     = $submitted_on_builder;
        $this->last_update_date_builder = $last_update_date_builder;
    }

    /**
     * @return IProvideParametrizedFromAndWhereSQLFragments
     */
    public function getFromWhere(Metadata $metadata, Comparison $comparison, array $trackers)
    {
        switch ($metadata->getName()) {
            case AllowedMetadata::SUBMITTED_ON:
                return $this->submitted_on_builder->getFromWhere($metadata, $comparison, $trackers);
            case AllowedMetadata::LAST_UPDATE_DATE:
                return $this->last_update_date_builder->getFromWhere($metadata, $comparison, $trackers);
        }
    }
}
