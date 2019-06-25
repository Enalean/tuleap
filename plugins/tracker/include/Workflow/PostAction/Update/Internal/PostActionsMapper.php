<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFields;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsets;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;

class PostActionsMapper
{
    /**
     * Converts from \Transition_PostAction_CIBuild to CIBuild object.
     * Sets the id to null to force the new post-action to be recreated.
     * @return CIBuildValue[]
     */
    public function convertToCIBuildWithNullId(\Transition_PostAction_CIBuild ...$ci_builds): array
    {
        $update_ci_builds = [];
        foreach ($ci_builds as $ci_build) {
            // We set $id to null so that all post actions are re-created from scratch in the $to transition
            $update_ci_builds[] = new CIBuildValue($ci_build->getJobUrl());
        }
        return $update_ci_builds;
    }

    /**
     * Converts from \Transition_PostAction_Field_Date to SetDateValue object.
     * Sets the id to null to force the new post-action to be recreated.
     * @return SetDateValue[]
     */
    public function convertToSetDateValueWithNullId(\Transition_PostAction_Field_Date ...$field_dates): array
    {
        $update_date_values = [];
        foreach ($field_dates as $field_date) {
            // We set $id to null so that all post actions are re-created from scratch in the $to transition
            $update_date_values[] = new SetDateValue(
                (int) $field_date->getFieldId(),
                $field_date->getValueType()
            );
        }
        return $update_date_values;
    }

    /**
     * Converts from \Transition_PostAction_Field_Float to SetFloatValue object.
     * Sets the id to null to force the new post-action to be recreated.
     * @return SetFloatValue[]
     */
    public function convertToSetFloatValueWithNullId(\Transition_PostAction_Field_Float ...$field_floats): array
    {
        $update_float_values = [];
        foreach ($field_floats as $field_float) {
            // We set $id to null so that all post actions are re-created from scratch in the $to transition
            $update_float_values[] = new SetFloatValue(
                (int) $field_float->getFieldId(),
                $field_float->getValue()
            );
        }
        return $update_float_values;
    }

    /**
     * Converts from \Transition_PostAction_Field_Int to SetIntValue object.
     * Sets the id to null to force the new post-action to be recreated.
     * @return SetIntValue[]
     */
    public function convertToSetIntValueWithNullId(\Transition_PostAction_Field_Int ...$field_ints): array
    {
        $update_int_values = [];
        foreach ($field_ints as $field_int) {
            // We set $id to null so that all post actions are re-created from scratch in the $to transition
            $update_int_values[] = new SetIntValue(
                (int) $field_int->getFieldId(),
                $field_int->getValue()
            );
        }
        return $update_int_values;
    }

    /**
     * Converts from FrozenFields to FrozenFieldsValue object.
     * Sets the id to null to force the new post-action to be recreated.
     * @return FrozenFieldsValue[]
     */
    public function convertToFrozenFieldValueWithNullId(FrozenFields $frozen_fields): array
    {
        $update_frozen_fields_value = [];
        // We set $id to null so that all post actions are re-created from scratch in the $to transition
        $update_frozen_fields_value[] = new FrozenFieldsValue(
            $frozen_fields->getFieldIds()
        );

        return $update_frozen_fields_value;
    }

    /**
     * Converts from HiddenFieldsets to HiddenFieldsetsValue object.
     * Sets the id to null to force the new post-action to be recreated.
     * @return HiddenFieldsetsValue[]
     */
    public function convertToHiddenFieldsetsValueWithNullId(HiddenFieldsets $hidden_fieldsets): array
    {
        $update_hidden_fieldsets_value = [];

        $fieldset_ids = [];
        foreach ($hidden_fieldsets->getFieldsets() as $fieldset) {
            $fieldset_ids[] = (int) $fieldset->getID();
        }

        // We set $id to null so that all post actions are re-created from scratch in the $to transition
        $update_hidden_fieldsets_value[] = new HiddenFieldsetsValue(
            $fieldset_ids
        );

        return $update_hidden_fieldsets_value;
    }
}
