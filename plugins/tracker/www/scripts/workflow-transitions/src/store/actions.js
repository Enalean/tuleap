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

import {
    createTransition,
    createWorkflowTransitions,
    getTracker,
    resetWorkflowTransitions,
    updateTransitionRulesEnforcement
} from "../api/rest-querier.js";
import { getErrorMessage } from "./exceptionHandler.js";

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
        const tracker_id = context.getters.current_tracker_id;
        await createWorkflowTransitions(tracker_id, field_id);
        context.commit("createWorkflow", field_id);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endOperation");
    }
}

export async function resetWorkflowTransitionsField(context) {
    try {
        context.commit("beginOperation");
        const tracker_id = context.getters.current_tracker_id;
        const new_tracker = await resetWorkflowTransitions(tracker_id);
        context.commit("saveCurrentTracker", new_tracker);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endOperation");
    }
}

export async function switchTransitionRulesEnforcement(context, new_enforcement) {
    try {
        context.commit("beginTransitionRulesEnforcement");
        const tracker_id = context.getters.current_tracker_id;
        const updated_tracker = await updateTransitionRulesEnforcement(tracker_id, new_enforcement);
        context.commit("saveCurrentTracker", updated_tracker);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endTransitionRulesEnforcement");
    }
}

export async function saveNewTransition(context, transition) {
    try {
        context.commit("beginOperation");
        const tracker_id = context.getters.current_tracker_id;
        const response = await createTransition(tracker_id, transition.from_id, transition.to_id);
        const new_transition = {
            id: response.id,
            ...transition
        };
        context.commit("addTransition", new_transition);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endOperation");
    }
}
