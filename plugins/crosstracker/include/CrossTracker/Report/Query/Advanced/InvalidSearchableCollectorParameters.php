<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\ICheckSemanticFieldForAComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;
use Tuleap\Tracker\Report\Query\Advanced\InvalidComparisonCollectorParameters;

class InvalidSearchableCollectorParameters implements VisitorParameters
{
    /** @var InvalidComparisonCollectorParameters */
    private $invalid_searchables_collector_parameters;
    /** @var ICheckSemanticFieldForAComparison */
    private $semantic_checker;
    /** @var Comparison */
    private $comparison;

    public function __construct(
        InvalidComparisonCollectorParameters $invalid_searchables_collector_parameters,
        ICheckSemanticFieldForAComparison $semantic_checker,
        Comparison $comparison
    ) {
        $this->invalid_searchables_collector_parameters = $invalid_searchables_collector_parameters;
        $this->semantic_checker                         = $semantic_checker;
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

    /** @return ICheckSemanticFieldForAComparison */
    public function getSemanticFieldChecker()
    {
        return $this->semantic_checker;
    }
}
