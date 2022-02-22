<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindValueIdCollection;

class ValidValuesAccordingToTransitionsRetriever
{
    public static function getValidValuesAccordingToTransitions(
        Artifact $artifact,
        \Tracker_FormElement_Field_List $field,
        BindValueIdCollection $list_of_values,
        \Workflow $workflow,
    ): void {
        $changeset_value      = $artifact->getValue($field);
        $field_artifact_value = $changeset_value ? (int) $changeset_value->getValue()[0] : null;

        $linked_field_artifact_value = $field->getListValueById($field_artifact_value);

        if (! $workflow->isUsed() || ! $linked_field_artifact_value) {
            return;
        }

        foreach ($list_of_values->getValueIds() as $value) {
            $value_in_list = $field->getListValueById($value);
            if (! $value_in_list || ! $workflow->isTransitionExist($linked_field_artifact_value, $value_in_list)) {
                $list_of_values->removeValue($value);
            }
        }
    }
}
