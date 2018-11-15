/*
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
        return false;
    }

    return Boolean(parseInt(state.current_tracker.workflow.is_used, 10));
};
