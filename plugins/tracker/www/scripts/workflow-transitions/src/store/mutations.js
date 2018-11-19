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

import state from "./state.js";

const initial_state = { ...state };

export default {
    failOperation(state, message) {
        state.is_operation_failed = true;
        state.operation_failure_message = message;
    },
    beginOperation(state) {
        state.is_operation_running = true;
    },
    endOperation(state) {
        state.is_operation_running = false;
    },

    // Current tracker loading
    startCurrentTrackerLoading(state) {
        state.is_current_tracker_loading = true;
    },
    failCurrentTrackerLoading(state) {
        state.is_current_tracker_load_failed = true;
    },
    stopCurrentTrackerLoading(state) {
        state.is_current_tracker_loading = false;
    },
    saveCurrentTracker(state, tracker) {
        state.current_tracker = tracker;
    },

    createWorkflow(state, field_id) {
        state.current_tracker = {
            ...state.current_tracker,
            workflow: {
                ...state.current_tracker.workflow,
                field_id
            }
        };
    },

    resetState(state) {
        Object.assign(state, initial_state);
    }
};
