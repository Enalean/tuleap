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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\NotInComparison;

use CodendiDataAccess;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereNotEqualComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListBindStaticFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\QueryListFieldPresenter;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

final class ForListBindStatic implements FieldFromWhereBuilder, ListBindStaticFromWhereBuilder
{
    public function __construct(
        private readonly CollectionOfListValuesExtractor $values_extractor,
        private readonly FromWhereNotEqualComparisonListFieldBuilder $from_where_builder,
    ) {
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field): IProvideFromAndWhereSQLFragments
    {
        $query_presenter = new QueryListFieldPresenter($comparison, $field);

        $values = $this->values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);

        $escaped_values = $this->quoteSmartImplode($values);
        $condition      = "$query_presenter->changeset_value_list_alias.bindvalue_id = $query_presenter->list_value_alias.id
            AND $query_presenter->list_value_alias.label IN($escaped_values)";

        $query_presenter->setCondition($condition);

        return $this->from_where_builder->getFromWhere($query_presenter);
    }

    private function quoteSmartImplode($values): string
    {
        return CodendiDataAccess::instance()->quoteSmartImplode(',', $values);
    }
}
