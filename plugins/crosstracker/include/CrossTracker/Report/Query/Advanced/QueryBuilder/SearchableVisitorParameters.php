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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\MetadataComparisonFromWhereBuilder;

class SearchableVisitorParameters implements VisitorParameters
{
    /** @var Comparison */
    private $comparison;
    /** @var SemanticComparisonFromWhereBuilder */
    private $from_where_builder;

    public function __construct(
        Comparison $comparison,
        SemanticComparisonFromWhereBuilder $from_where_builder
    ) {
        $this->comparison               = $comparison;
        $this->from_where_builder       = $from_where_builder;
    }

    /** @return SemanticComparisonFromWhereBuilder */
    public function getFromWhereBuilder()
    {
        return $this->from_where_builder;
    }

    /** @return Comparison */
    public function getComparison()
    {
        return $this->comparison;
    }
}
