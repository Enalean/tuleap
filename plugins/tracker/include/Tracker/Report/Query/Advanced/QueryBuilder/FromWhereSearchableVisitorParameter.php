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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tracker;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\VisitorParameters;

class FromWhereSearchableVisitorParameter implements VisitorParameters
{
    public function __construct(
        private readonly Comparison $comparison,
        private readonly FieldComparisonVisitor $field_comparison_visitor,
        private readonly Tracker $tracker,
        private readonly MetadataComparisonFromWhereBuilder $metadata_comparison_from_where_builder,
    ) {
    }

    public function getComparison(): Comparison
    {
        return $this->comparison;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    public function getFieldComparisonVisitor(): FieldComparisonVisitor
    {
        return $this->field_comparison_visitor;
    }

    public function getMetadataComparisonFromWhereBuilder(): MetadataComparisonFromWhereBuilder
    {
        return $this->metadata_comparison_from_where_builder;
    }
}
