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
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereEmptyComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListBindUsersFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\QueryListFieldPresenter;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

class ForListBindUsers implements FieldFromWhereBuilder, ListBindUsersFromWhereBuilder
{
    /**
     * @var FromWhereEmptyComparisonListFieldBuilder
     */
    private $empty_comparison_builder;
    /**
     * @var FromWhereComparisonListFieldBuilder
     */
    private $comparison_builder;
    /**
     * @var CollectionOfListValuesExtractor
     */
    private $values_extractor;

    public function __construct(
        CollectionOfListValuesExtractor $values_extractor,
        FromWhereEmptyComparisonListFieldBuilder $empty_comparison_builder,
        FromWhereComparisonListFieldBuilder $comparison_builder
    ) {
        $this->values_extractor         = $values_extractor;
        $this->comparison_builder       = $comparison_builder;
        $this->empty_comparison_builder = $empty_comparison_builder;
    }

    /**
     * @return IProvideFromAndWhereSQLFragments
     */
    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $parameter_collection = new QueryListFieldPresenter($comparison, $field);

        $values = $this->values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);
        $value  = $values[0];

        if ($value === '') {
            return $this->getFromWhereForEmptyCondition($parameter_collection);
        }
        return $this->getFromWhereForNonEmptyCondition($parameter_collection, $value);
    }

    /**
     * @return IProvideFromAndWhereSQLFragments
     */
    private function getFromWhereForNonEmptyCondition(QueryListFieldPresenter $parameter_collection, $value)
    {
        $condition = "$parameter_collection->changeset_value_list_alias.bindvalue_id = $parameter_collection->list_value_alias.user_id
            AND $parameter_collection->list_value_alias.user_name = " . $this->quoteSmart($value);

        $parameter_collection->setCondition($condition);
        $parameter_collection->setListValueTable('user');

        return $this->comparison_builder->getFromWhere($parameter_collection);
    }

    /**
     * @return IProvideFromAndWhereSQLFragments
     */
    private function getFromWhereForEmptyCondition(QueryListFieldPresenter $parameter_collection)
    {
        $condition = "($parameter_collection->changeset_value_alias.changeset_id IS NULL OR $parameter_collection->changeset_value_list_alias.bindvalue_id =" .
            $this->escapeInt(Tracker_FormElement_Field_List::NONE_VALUE) . ")";

        $parameter_collection->setCondition($condition);

        return $this->empty_comparison_builder->getFromWhere($parameter_collection);
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
