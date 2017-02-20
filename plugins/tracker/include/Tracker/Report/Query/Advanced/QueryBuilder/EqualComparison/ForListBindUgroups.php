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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparison;

use CodendiDataAccess;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Report\Query\Advanced\FromWhere;
use Tuleap\Tracker\Report\Query\Advanced\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereEmptyComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListBindUgroupsFromWhereBuilder;

class ForListBindUgroups implements FromWhereBuilder, ListBindUgroupsFromWhereBuilder
{
    /**
     * @var FromWhereEmptyComparisonListFieldBuilder
     */
    private $empty_comparison_builder;
    /**
     * @var FromWhereComparisonListFieldBuilder
     */
    private $comparison_builder;

    public function __construct(
        FromWhereEmptyComparisonListFieldBuilder $empty_comparison_builder,
        FromWhereComparisonListFieldBuilder $comparison_builder
    ) {
        $this->empty_comparison_builder = $empty_comparison_builder;
        $this->comparison_builder       = $comparison_builder;
    }

    /**
     * @return FromWhere
     */
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
                $changeset_value_list_alias,
                $field_id,
                $changeset_value_alias
            );
        }
    }

    /**
     * @return FromWhere
     */
    private function getFromWhereForEmptyCondition($changeset_value_list_alias, $field_id, $changeset_value_alias)
    {
        $where = "($changeset_value_alias.changeset_id IS NULL OR $changeset_value_list_alias.bindvalue_id =" .
            $this->escapeInt(Tracker_FormElement_Field_List::NONE_VALUE).")";

        return $this->empty_comparison_builder->getFromWhere(
            $field_id,
            $changeset_value_alias,
            $changeset_value_list_alias,
            'tracker_changeset_value_list',
            $where
        );
    }

    private function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }
}
