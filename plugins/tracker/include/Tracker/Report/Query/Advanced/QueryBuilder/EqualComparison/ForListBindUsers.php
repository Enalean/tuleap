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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparison;

use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereEmptyComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListBindUsersFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\QueryListFieldPresenter;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

final class ForListBindUsers implements FieldFromWhereBuilder, ListBindUsersFromWhereBuilder
{
    public function __construct(
        private readonly CollectionOfListValuesExtractor $values_extractor,
        private readonly FromWhereEmptyComparisonListFieldBuilder $empty_comparison_builder,
        private readonly FromWhereComparisonListFieldBuilder $comparison_builder,
    ) {
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field): IProvideParametrizedFromAndWhereSQLFragments
    {
        $parameter_collection = new QueryListFieldPresenter($comparison, $field);

        $values = $this->values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);
        $value  = $values[0];

        if ($value === '') {
            return $this->getFromWhereForEmptyCondition($parameter_collection);
        }

        return $this->getFromWhereForNonEmptyCondition($parameter_collection, $value);
    }

    private function getFromWhereForNonEmptyCondition(QueryListFieldPresenter $parameter_collection, $value): IProvideParametrizedFromAndWhereSQLFragments
    {
        $condition = "$parameter_collection->changeset_value_list_alias.bindvalue_id = $parameter_collection->list_value_alias.user_id
            AND $parameter_collection->list_value_alias.user_name = ?";

        $parameter_collection->setCondition($condition);
        $parameter_collection->setListValueTable('user');
        $parameter_collection->setParameters([$value]);

        return $this->comparison_builder->getFromWhere($parameter_collection);
    }

    private function getFromWhereForEmptyCondition(QueryListFieldPresenter $parameter_collection): IProvideParametrizedFromAndWhereSQLFragments
    {
        $condition = "($parameter_collection->changeset_value_alias.changeset_id IS NULL OR $parameter_collection->changeset_value_list_alias.bindvalue_id = ?)";

        $parameter_collection->setCondition($condition);
        $parameter_collection->setParameters([Tracker_FormElement_Field_List::NONE_VALUE]);

        return $this->empty_comparison_builder->getFromWhere($parameter_collection);
    }
}
