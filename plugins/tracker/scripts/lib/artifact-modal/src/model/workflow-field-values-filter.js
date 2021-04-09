/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

export function enforceWorkflowTransitions(source_value_id, field, workflow) {
    const field_values = getPossibleValues(source_value_id, field.values, workflow.transitions);

    field.values = field_values;
    field.filtered_values = [...field_values];
    field.has_transitions = true;

    return field;
}

function getPossibleValues(source_value_id, field_values, transitions) {
    const possible_value_ids = getPossibleValueIds(source_value_id, transitions);

    return field_values.filter((value) => possible_value_ids.includes(value.id));
}

function getPossibleValueIds(source_value_id, transitions) {
    const possible_values = transitions
        .filter((transition) => transition.from_id === source_value_id)
        .map(({ to_id }) => to_id);

    return [source_value_id, ...possible_values];
}
