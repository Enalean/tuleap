<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\NotEqualComparison;

use CodendiDataAccess;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Report\Query\Advanced\FromWhere;
use Tuleap\Tracker\Report\Query\Advanced\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereEmptyComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereNotEqualComparisonListFieldBuilder;

class ForListBindUsers implements FromWhereBuilder
{
    /**
     * @var FromWhereComparisonFieldBuilder
     */
    private $empty_comparison_builder;
    /**
     * @var FromWhereNotEqualComparisonListFieldBuilder
     */
    private $comparison_builder;

    public function __construct(
        FromWhereComparisonFieldBuilder $empty_comparison_builder,
        FromWhereNotEqualComparisonListFieldBuilder $comparison_builder
    ) {
        $this->empty_comparison_builder = $empty_comparison_builder;
        $this->comparison_builder       = $comparison_builder;
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $suffix           = spl_object_hash($comparison);
        $comparison_value = $comparison->getValueWrapper();
        $value            = $comparison_value->getValue();
        $field_id         = (int) $field->getId();

        $changeset_value_list_alias = "CVList_{$field_id}_{$suffix}";
        $changeset_value_alias      = "CV_{$field_id}_{$suffix}";

        if ($value === '') {
            return $this->getFromWhereForEmptyCondition(
                $field_id,
                $changeset_value_alias,
                $changeset_value_list_alias
            );
        }
    }

    /**
     * @return FromWhere
     */
    private function getFromWhereForEmptyCondition(
        $field_id,
        $changeset_value_alias,
        $changeset_value_list_alias
    ) {
        $matches_value = " != " . $this->escapeInt(Tracker_FormElement_Field_List::NONE_VALUE);
        $condition     = "$changeset_value_list_alias.bindvalue_id $matches_value";

        return $this->empty_comparison_builder->getFromWhere(
            $field_id,
            $changeset_value_alias,
            $changeset_value_list_alias,
            'tracker_changeset_value_list',
            $condition
        );
    }

    private function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }
}
