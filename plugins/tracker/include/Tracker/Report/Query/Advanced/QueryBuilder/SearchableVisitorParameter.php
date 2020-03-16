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
     * @var FieldComparisonVisitor
     */
    private $field_comparison_visitor;
    /**
     * @var MetadataComparisonFromWhereBuilder
     */
    private $metadata_comparison_from_where_builder;

    public function __construct(
        Comparison $comparison,
        FieldComparisonVisitor $field_comparison_visitor,
        Tracker $tracker,
        MetadataComparisonFromWhereBuilder $metadata_comparison_from_where_builder
    ) {
        $this->comparison                             = $comparison;
        $this->tracker                                = $tracker;
        $this->field_comparison_visitor               = $field_comparison_visitor;
        $this->metadata_comparison_from_where_builder = $metadata_comparison_from_where_builder;
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
     * @return FieldComparisonVisitor
     */
    public function getFieldComparisonVisitor()
    {
        return $this->field_comparison_visitor;
    }

    /**
     * @return MetadataComparisonFromWhereBuilder
     */
    public function getMetadataComparisonFromWhereBuilder()
    {
        return $this->metadata_comparison_from_where_builder;
    }
}
