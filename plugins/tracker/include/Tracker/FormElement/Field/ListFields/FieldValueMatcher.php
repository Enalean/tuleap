<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields;

use Tracker_FormElement_Field_List;

class FieldValueMatcher
{

    public function getMatchingValueByDuckTyping(
        Tracker_FormElement_Field_List $source_field,
        Tracker_FormElement_Field_List $target_field,
        $source_value_id
    ) {
        if (! $source_value_id || $source_value_id === Tracker_FormElement_Field_List::NONE_VALUE) {
            return Tracker_FormElement_Field_List::NONE_VALUE;
        }

        $source_value       = $source_field->getBind()->getValue($source_value_id);
        $source_value_label = $source_value->getLabel();

        foreach ($target_field->getBind()->getAllValues() as $target_value) {
            if (strtolower($source_value_label) === strtolower($target_value->getLabel())) {
                return $target_value->getId();
            }
        }

        return $target_field->getDefaultValue();
    }
}
