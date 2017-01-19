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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\BetweenComparison;

use CodendiDataAccess;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\FromWhere;
use Tuleap\Tracker\Report\Query\Advanced\FromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;

class ForFloat implements FromWhereBuilder
{
    public function getFromWhere(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $suffix           = spl_object_hash($comparison);
        $comparison_value = $comparison->getValueWrapper();
        $min_value        = $comparison_value->getMinValue()->getValue();
        $max_value        = $comparison_value->getMaxValue()->getValue();
        $field_id         = (int) $field->getId();

        $changeset_value_float_alias = "CVFloat_{$field_id}_{$suffix}";
        $changeset_value_alias       = "CV_{$field_id}_{$suffix}";

        if ($min_value === '' && $max_value === '') {
            $condition = "1";
        } else if ($min_value === '') {
            $condition = "$changeset_value_float_alias.value <= ".$this->escapeFloat($max_value);
        } else if ($max_value === '') {
            $condition = "$changeset_value_float_alias.value >= ".$this->escapeFloat($min_value);
        } else {
            $condition = "$changeset_value_float_alias.value BETWEEN ".$this->escapeFloat($min_value)." AND ".$this->escapeFloat($max_value);
        }

        $from = " LEFT JOIN (
            tracker_changeset_value AS $changeset_value_alias
            INNER JOIN tracker_changeset_value_float AS $changeset_value_float_alias
             ON ($changeset_value_float_alias.changeset_value_id = $changeset_value_alias.id
                 AND $condition
             )
         ) ON ($changeset_value_alias.changeset_id = c.id AND $changeset_value_alias.field_id = $field_id)";

        $where = "$changeset_value_alias.changeset_id IS NOT NULL";

        return new FromWhere($from, $where);
    }

    private function escapeFloat($value)
    {
        return CodendiDataAccess::instance()->escapeFloat($value);
    }
}
