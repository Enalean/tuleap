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
use Tuleap\Tracker\Report\Query\Advanced\FromWhere;
use Tuleap\Tracker\Report\Query\Advanced\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonListFieldBindUgroupsBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\FromWhereEmptyComparisonListFieldBuilder;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\ListBindUgroupsFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\UgroupLabelConverter;

class ForListBindUgroups implements FromWhereBuilder, ListBindUgroupsFromWhereBuilder
{
    /**
     * @var FromWhereEmptyComparisonListFieldBuilder
     */
    private $empty_comparison_builder;
    /**
     * @var FromWhereComparisonListFieldBindUgroupsBuilder
     */
    private $comparison_builder;
    /**
     * @var UgroupLabelConverter
     */
    private $label_converter;
    /**
     * @var CollectionOfListValuesExtractor
     */
    private $values_extractor;

    public function __construct(
        CollectionOfListValuesExtractor $values_extractor,
        FromWhereEmptyComparisonListFieldBuilder $empty_comparison_builder,
        FromWhereComparisonListFieldBindUgroupsBuilder $comparison_builder,
        UgroupLabelConverter $label_converter
    ) {
        $this->empty_comparison_builder = $empty_comparison_builder;
        $this->comparison_builder       = $comparison_builder;
        $this->label_converter          = $label_converter;
        $this->values_extractor         = $values_extractor;
    }

    /**
     * @return FromWhere
     */
    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $suffix   = spl_object_hash($comparison);
        $values   = $this->values_extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);
        $value    = $values[0];
        $field_id = (int)$field->getId();

        $changeset_value_list_alias = "CVList_{$field_id}_{$suffix}";
        $changeset_value_alias      = "CV_{$field_id}_{$suffix}";
        $list_value_alias           = "ListValue_{$field_id}_{$suffix}";
        $bind_value_alias           = "BindValue_{$field_id}_{$suffix}";

        if ($value === '') {
            return $this->getFromWhereForEmptyCondition(
                $changeset_value_list_alias,
                $field_id,
                $changeset_value_alias
            );
        }

        return $this->getFromWhereForNonEmptyCondition(
            $field_id,
            $changeset_value_alias,
            $changeset_value_list_alias,
            $list_value_alias,
            $bind_value_alias,
            $value
        );
    }

    private function getFromWhereForNonEmptyCondition(
        $field_id,
        $changeset_value_alias,
        $changeset_value_field_alias,
        $ugroup_alias,
        $bind_value_alias,
        $value
    ) {
        if ($this->label_converter->isASupportedDynamicUgroup($value)) {
            $value = $this->label_converter->convertLabelToTranslationKey($value);
        }

        $condition = "$ugroup_alias.name = " . $this->quoteSmart($value);

        return $this->comparison_builder->getFromWhere(
            $field_id,
            $changeset_value_alias,
            $changeset_value_field_alias,
            $ugroup_alias,
            $bind_value_alias,
            $condition
        );
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

    private function quoteSmart($value)
    {
        return CodendiDataAccess::instance()->quoteSmart($value);
    }
}
