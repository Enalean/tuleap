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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Comparison\ComparisonChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\ICheckMetadataForAComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;

final class InvalidSearchableCollectorParameters implements VisitorParameters
{
    /** @var InvalidComparisonCollectorParameters */
    private $invalid_searchables_collector_parameters;
    /** @var ICheckMetadataForAComparison */
    private $metadata_checker;
    /** @var Comparison */
    private $comparison;
    /** @var ComparisonChecker */
    private $comparison_checker;

    public function __construct(
        InvalidComparisonCollectorParameters $invalid_searchables_collector_parameters,
        ICheckMetadataForAComparison $metadata_checker,
        ComparisonChecker $comparison_checker,
        Comparison $comparison,
    ) {
        $this->invalid_searchables_collector_parameters = $invalid_searchables_collector_parameters;
        $this->metadata_checker                         = $metadata_checker;
        $this->comparison_checker                       = $comparison_checker;
        $this->comparison                               = $comparison;
    }

    /** @return Comparison */
    public function getComparison()
    {
        return $this->comparison;
    }

    /** @return InvalidComparisonCollectorParameters */
    public function getInvalidSearchablesCollectorParameters()
    {
        return $this->invalid_searchables_collector_parameters;
    }

    /** @return ICheckMetadataForAComparison */
    public function getMetadataChecker()
    {
        return $this->metadata_checker;
    }

    /** @return ComparisonChecker */
    public function getComparisonChecker()
    {
        return $this->comparison_checker;
    }
}
