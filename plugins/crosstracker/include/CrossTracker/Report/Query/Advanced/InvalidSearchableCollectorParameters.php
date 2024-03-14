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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ComparisonChecker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;

final readonly class InvalidSearchableCollectorParameters implements VisitorParameters
{
    public function __construct(
        private InvalidComparisonCollectorParameters $invalid_searchables_collector_parameters,
        private ComparisonChecker $comparison_checker,
        private Comparison $comparison,
    ) {
    }

    public function getComparison(): Comparison
    {
        return $this->comparison;
    }

    public function getInvalidSearchablesCollectorParameters(): InvalidComparisonCollectorParameters
    {
        return $this->invalid_searchables_collector_parameters;
    }

    public function getComparisonChecker(): ComparisonChecker
    {
        return $this->comparison_checker;
    }
}
