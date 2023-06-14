<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\LesserThanComparison;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonFieldBuilder;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

final class ForFloat implements FieldFromWhereBuilder
{
    public function __construct(private readonly FromWhereComparisonFieldBuilder $from_where_builder)
    {
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field): IProvideParametrizedFromAndWhereSQLFragments
    {
        $suffix           = spl_object_hash($comparison);
        $comparison_value = $comparison->getValueWrapper();
        $value            = $comparison_value->getValue();
        $field_id         = (int) $field->getId();

        $changeset_value_float_alias = "CVFloat_{$field_id}_{$suffix}";
        $changeset_value_alias       = "CV_{$field_id}_{$suffix}";

        $condition = "$changeset_value_float_alias.value < ?";

        return $this->from_where_builder->getFromWhere(
            $field_id,
            $changeset_value_alias,
            $changeset_value_float_alias,
            'tracker_changeset_value_float',
            $condition,
            [$value],
        );
    }
}
