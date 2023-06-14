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

use ParagonIE\EasyDB\EasyStatement;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereNotEqualComparisonListFieldBindUgroupsBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListBindUgroupsFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\QueryListFieldPresenter;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

final class ForListBindUGroups implements ListBindUgroupsFromWhereBuilder
{
    public function __construct(
        private readonly CollectionOfListValuesExtractor $values_extractor,
        private readonly FromWhereNotEqualComparisonListFieldBindUgroupsBuilder $from_where_builder,
        private readonly UgroupLabelConverter $label_converter,
    ) {
    }

    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field): IProvideParametrizedFromAndWhereSQLFragments
    {
        $query_presenter = new QueryListFieldPresenter($comparison, $field);

        $values = $this->values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);

        $normalized_values = [];
        foreach ($values as $value) {
            if ($this->label_converter->isASupportedDynamicUgroup($value)) {
                $value = $this->label_converter->convertLabelToTranslationKey($value);
            }

            $normalized_values[] = $value;
        }

        $in = EasyStatement::open()->in('?*', $normalized_values);

        $condition = "$query_presenter->list_value_alias.name IN($in)";

        $query_presenter->setCondition($condition);
        $query_presenter->setParameters($in->values());

        return $this->from_where_builder->getFromWhere($query_presenter);
    }
}
