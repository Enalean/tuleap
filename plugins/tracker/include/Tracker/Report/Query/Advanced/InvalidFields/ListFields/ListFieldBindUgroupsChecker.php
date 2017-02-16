<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;

class ListFieldBindUgroupsChecker implements InvalidFieldChecker
{
    /**
     * @var ListFieldChecker
     */
    private $list_field_checker;

    public function __construct(
        ListFieldChecker $list_field_checker
    ) {
        $this->list_field_checker = $list_field_checker;
    }

    public function checkFieldIsValidForComparison(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $existing_values = $this->extractLabelValues($field->getAllValues());
        $this->list_field_checker->checkFieldIsValidForComparison($comparison, $field, $existing_values);
    }

    private function extractLabelValues(array $list_values)
    {
        $list_label_values = array();

        foreach ($list_values as $value) {
            $list_label_values[] = $value->getLabel();
        }

        return $list_label_values;
    }
}
