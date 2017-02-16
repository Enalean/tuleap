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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\InComparison;

use CodendiDataAccess;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListBindStaticFromWhereBuilder;

class ForListBindStatic implements FromWhereBuilder, ValueWrapperVisitor, ListBindStaticFromWhereBuilder
{
    /**
     * @var FromWhereComparisonListFieldBuilder
     */
    private $from_where_builder;

    public function __construct(FromWhereComparisonListFieldBuilder $from_where_builder)
    {
        $this->from_where_builder = $from_where_builder;
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $suffix   = spl_object_hash($comparison);
        $values   = $comparison->getValueWrapper()->accept($this, new ValueWrapperParameters($field));
        $field_id = (int)$field->getId();

        $changeset_value_list_alias = "CVList_{$field_id}_{$suffix}";
        $changeset_value_alias      = "CV_{$field_id}_{$suffix}";
        $list_value_alias           = "ListValue_{$field_id}_{$suffix}";

        $escaped_values = $this->quoteSmartImplode($values);
        $condition      = "$changeset_value_list_alias.bindvalue_id = $list_value_alias.id
            AND $list_value_alias.label IN($escaped_values)";

        return $this->from_where_builder->getFromWhere(
            $field_id,
            $changeset_value_alias,
            $changeset_value_list_alias,
            'tracker_changeset_value_list',
            'tracker_field_list_bind_static_value',
            $list_value_alias,
            $condition
        );
    }

    private function quoteSmartImplode($values)
    {
        return CodendiDataAccess::instance()->quoteSmartImplode(',', $values);
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
        return $value_wrapper->getValue();
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, ValueWrapperParameters $parameters)
    {
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, ValueWrapperParameters $parameters)
    {
        $values = array();
        foreach ($collection_of_value_wrappers->getValueWrappers() as $value_wrapper) {
            $values[] = $value_wrapper->accept($this, $parameters);
        }

        return $values;
    }
}
