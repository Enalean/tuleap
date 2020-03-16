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
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereEmptyComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListBindStaticFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\QueryListFieldPresenter;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

class ForListBindStatic implements FieldFromWhereBuilder, ListBindStaticFromWhereBuilder
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
        $this->comparison_builder       = $comparison_builder;
        $this->empty_comparison_builder = $empty_comparison_builder;
    }

    /**
     * @return IProvideFromAndWhereSQLFragments
     */
    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $query_presenter = new QueryListFieldPresenter($comparison, $field);

        $comparison_value = $comparison->getValueWrapper();
        $value            = $comparison_value->getValue();

        if ($value === '') {
            return $this->getFromWhereForEmptyCondition($query_presenter);
        }
        return $this->getFromWhereForNonEmptyCondition($query_presenter, $value);
    }

    /**
     * @return IProvideFromAndWhereSQLFragments
     */
    private function getFromWhereForNonEmptyCondition(QueryListFieldPresenter $query_presenter, $value)
    {
        $condition = "$query_presenter->changeset_value_list_alias.bindvalue_id = $query_presenter->list_value_alias.id
            AND $query_presenter->list_value_alias.label = " . $this->quoteSmart($value);

        $query_presenter->setCondition($condition);

        return $this->comparison_builder->getFromWhere($query_presenter);
    }

    /**
     * @return IProvideFromAndWhereSQLFragments
     */
    private function getFromWhereForEmptyCondition(QueryListFieldPresenter $query_presenter)
    {
        $condition = "($query_presenter->changeset_value_alias.changeset_id IS NULL OR $query_presenter->changeset_value_list_alias.bindvalue_id =" .
            $this->escapeInt(Tracker_FormElement_Field_List::NONE_VALUE) . ")";

        $query_presenter->setCondition($condition);

        return $this->empty_comparison_builder->getFromWhere($query_presenter);
    }

    private function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }

    private function quoteSmart($value)
    {
        return CodendiDataAccess::instance()->quoteSmart($value);
    }
}
