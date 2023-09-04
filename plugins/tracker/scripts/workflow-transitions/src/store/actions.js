/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    createTransition as restCreateTransition,
    createWorkflowTransitions as restCreateWorkflowTransitions,
    getTracker,
    resetWorkflowTransitions as restResetWorkflowTransitions,
    updateTransitionRulesEnforcement as restUpdateTransitionRulesEnforcement,
    deleteTransition as restDeleteTransition,
    deactivateLegacyTransitions as restDeactivateLegacyTransitions,
    changeWorkflowMode as restChangeWorkflowMode,
} from "../api/rest-querier.js";
import { getErrorMessage } from "./exception-handler.js";

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

export async function createWorkflowTransitions(context, field_id) {
    try {
        context.commit("beginOperation");
        const tracker_id = context.getters.current_tracker_id;
        const tracker = await restCreateWorkflowTransitions(tracker_id, field_id);
        context.commit("createWorkflow", tracker);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endOperation");
    }
}

export async function resetWorkflowTransitions(context) {
    try {
        context.commit("beginOperation");
        const tracker_id = context.getters.current_tracker_id;
        const new_tracker = await restResetWorkflowTransitions(tracker_id);
        context.commit("saveCurrentTracker", new_tracker);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endOperation");
    }
}

export async function updateTransitionRulesEnforcement(context, new_enforcement) {
    try {
        context.commit("beginTransitionRulesEnforcement");
        const tracker_id = context.getters.current_tracker_id;
        const updated_tracker = await restUpdateTransitionRulesEnforcement(
            tracker_id,
            new_enforcement,
        );
        context.commit("saveCurrentTracker", updated_tracker);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endTransitionRulesEnforcement");
    }
}

export async function changeWorkflowMode(context, is_workflow_advanced) {
    try {
        context.commit("beginWorkflowModeChange");
        const updated_tracker = await restChangeWorkflowMode(
            context.getters.current_tracker_id,
            is_workflow_advanced,
        );
        context.commit("saveCurrentTracker", updated_tracker);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endWorkflowModeChange");
    }
}

export async function createTransition(context, transition) {
    try {
        context.commit("beginOperation");
        const tracker_id = context.getters.current_tracker_id;
        const response = await restCreateTransition(
            tracker_id,
            transition.from_id,
            transition.to_id,
        );
        const new_transition = {
            id: response.id,
            ...transition,
        };
        context.commit("addTransition", new_transition);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endOperation");
    }
}

export async function deleteTransition(context, transition) {
    try {
        context.commit("beginOperation");
        await restDeleteTransition(transition.id);
        context.commit("deleteTransition", transition);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endOperation");
    }
}

export async function deactivateLegacyTransitions(context) {
    try {
        const tracker_id = context.getters.current_tracker_id;
        context.commit("beginOperation");
        const tracker_with_updated_workflow = await restDeactivateLegacyTransitions(tracker_id);
        context.commit("saveCurrentTracker", tracker_with_updated_workflow);
    } catch (exception) {
        const error_message = await getErrorMessage(exception);
        context.commit("failOperation", error_message);
    } finally {
        context.commit("endOperation");
    }
}
