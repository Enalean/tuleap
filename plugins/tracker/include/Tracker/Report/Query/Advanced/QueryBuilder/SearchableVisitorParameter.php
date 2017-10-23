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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tracker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;

class SearchableVisitorParameter implements VisitorParameters
{
    /**
     * @var Comparison
     */
    private $comparison;
    /**
     * @var Tracker
     */
    private $tracker;
    /**
     * @var ComparisonVisitor
     */
    private $comparison_visitor;

    public function __construct(Comparison $comparison, ComparisonVisitor $comparison_visitor, Tracker $tracker)
    {

        $this->comparison         = $comparison;
        $this->tracker            = $tracker;
        $this->comparison_visitor = $comparison_visitor;
    }

    /**
     * @return Comparison
     */
    public function getComparison()
    {
        return $this->comparison;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * @return ComparisonVisitor
     */
    public function getComparisonVisitor()
    {
        return $this->comparison_visitor;
    }
}
