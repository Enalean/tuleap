/*
 * Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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

import { SELECTBOX_FIELD, LIST_BIND_STATIC } from "../../../constants/fields-constants.js";
import { compare } from "../support/string.js";

export const workflow_field_label = state => {
    if (state.current_tracker === null) {
        return null;
    }

    const selected_field = state.current_tracker.fields.find(
        field => field.field_id === state.current_tracker.workflow.field_id
    );
    return selected_field.label;
};

export const are_transition_rules_enforced = state => {
    if (!state.current_tracker) {
        return null;
    }

    return Boolean(parseInt(state.current_tracker.workflow.is_used, 10));
};

export const is_workflow_legacy = state => {
    if (!state.current_tracker) {
        return null;
    }

    return state.current_tracker.workflow.is_legacy;
};

export const current_tracker_id = state => {
    if (state.current_tracker === null) {
        return null;
    }
    return state.current_tracker.id;
};

export const current_project_id = state => {
    return state.current_tracker !== null ? state.current_tracker.project.id : null;
};

export const selectbox_fields = state => {
    if (!state.current_tracker) {
        return [];
    }

    return state.current_tracker.fields
        .filter(field => field.type === SELECTBOX_FIELD && field.bindings.type === LIST_BIND_STATIC)
        .map(field => ({ id: field.field_id, label: field.label }))
        .sort((field1, field2) => compare(field1.label, field2.label));
};

export const has_selectbox_fields = (state, getters) => getters.selectbox_fields.length > 0;
