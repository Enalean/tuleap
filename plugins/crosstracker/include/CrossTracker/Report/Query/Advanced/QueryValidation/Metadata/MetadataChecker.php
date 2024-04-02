<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata;

use Tuleap\CrossTracker\Report\Query\Advanced\InvalidComparisonCollectorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final readonly class MetadataChecker
{
    public function __construct(
        private CheckMetadataUsage $semantic_usage_checker,
        private InvalidMetadataChecker $comparison_checker,
    ) {
    }

    /**
     * @throws InvalidQueryException
     */
    public function checkMetadataIsValid(
        Metadata $metadata,
        Comparison $comparison,
        InvalidComparisonCollectorParameters $collector_parameters,
    ): void {
        $this->semantic_usage_checker->checkMetadataIsUsedByAllTrackers($metadata, $collector_parameters);
        $this->comparison_checker->checkComparisonIsValid($metadata, $comparison);
    }
}
