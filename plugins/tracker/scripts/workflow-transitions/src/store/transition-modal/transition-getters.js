/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

export { is_transition_from_new_artifact, post_actions, set_value_action_fields };

function is_transition_from_new_artifact(state) {
    return state.current_transition !== null ? state.current_transition.from_id === null : false;
}

function post_actions(state) {
    if (!state.post_actions_by_unique_id) {
        return null;
    }
    return Object.keys(state.post_actions_by_unique_id).map(
        (unique_id) => state.post_actions_by_unique_id[unique_id],
    );
}

function set_value_action_fields(state, getters, root_state) {
    if (getters.post_actions === null) {
        return null;
    }
    const field_ids_used_in_post_actions = getters.post_actions.map(({ field_id }) => field_id);
    const fields = [...root_state.current_tracker.fields].map((field) => {
        return {
            ...field,
            disabled: field_ids_used_in_post_actions.includes(field.field_id),
        };
    });
    return fields;
}

export function is_agile_dashboard_used(state) {
    return state.used_services_names.indexOf("agile_dashboard") !== -1;
}

export function is_program_management_used(state) {
    return state.used_services_names.indexOf("plugin_program_management") !== -1;
}
