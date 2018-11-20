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

import { getTracker, createWorkflowTransitions } from "../api/rest-querier.js";

export async function loadTracker(context, tracker_id) {
    try {
        context.commit("startCurrentTrackerLoading", tracker_id);
        const tracker = await getTracker(tracker_id);
        context.commit("saveCurrentTracker", tracker);
    } catch (e) {
        context.commit("failCurrentTrackerLoading");
    } finally {
        context.commit("stopCurrentTrackerLoading");
    }
}

export async function saveWorkflowTransitionsField(context, field_id) {
    try {
        context.commit("beginOperation");
        const tracker_id = context.state.current_tracker.id;
        await createWorkflowTransitions(tracker_id, field_id);
        context.commit("createWorkflow", field_id);
    } catch (exception) {
        if (!exception.response) {
            context.commit("failOperation");
            return;
        }
        let response;
        try {
            response = await exception.response.json();
        } catch (json_exception) {
            context.commit("failOperation");
            return;
        }
        if (!response.error || !("i18n_error_message" in response.error)) {
            context.commit("failOperation");
            return;
        }
        context.commit("failOperation", response.error.i18n_error_message);
    } finally {
        context.commit("endOperation");
    }
}
