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

import state from "./state.js";

const initial_state = { ...state };

export function failOperation(state, message) {
    state.is_operation_failed = true;
    state.operation_failure_message = message;
}

export function beginOperation(state) {
    state.is_operation_running = true;
    state.is_operation_failed = false;
    state.operation_failure_message = null;
}

export function endOperation(state) {
    state.is_operation_running = false;
}

// Current tracker loading
export function startCurrentTrackerLoading(state) {
    state.is_current_tracker_loading = true;
}

export function failCurrentTrackerLoading(state) {
    state.is_current_tracker_load_failed = true;
}

export function stopCurrentTrackerLoading(state) {
    state.is_current_tracker_loading = false;
}

export function saveCurrentTracker(state, tracker) {
    const presented_transitions = [...tracker.workflow.transitions].map(presentTransition);
    state.current_tracker = {
        ...tracker,
        workflow: {
            ...tracker.workflow,
            transitions: presented_transitions,
        },
    };
}

// Transition operations
export function addTransition(state, transition) {
    if (!state.current_tracker || !state.current_tracker.workflow) {
        return;
    }
    if (!state.current_tracker.workflow.transitions) {
        state.current_tracker.workflow.transitions = [];
    }
    const presented_transition = presentTransition(transition);

    state.current_tracker.workflow.transitions = [
        ...state.current_tracker.workflow.transitions,
        presented_transition,
    ];
}

function presentTransition(transition) {
    return {
        ...transition,
        updated: false,
    };
}

export function deleteTransition(state, transition_to_delete) {
    if (
        !state.current_tracker ||
        !state.current_tracker.workflow ||
        !state.current_tracker.workflow.transitions
    ) {
        return;
    }
    state.current_tracker.workflow.transitions = state.current_tracker.workflow.transitions.filter(
        (transition) => transition !== transition_to_delete,
    );
}

export function markTransitionUpdated(state, { id }) {
    const transition = findTransitionById(state, id);
    if (transition) {
        transition.updated = true;
    }
}

export function hideTransitionUpdated(state, { id }) {
    const transition = findTransitionById(state, id);
    if (transition) {
        transition.updated = false;
    }
}

function findTransitionById(state, id) {
    return state.current_tracker.workflow.transitions.find((element) => element.id === id);
}

export function createWorkflow(state, tracker) {
    if (!state.current_tracker) {
        return;
    }

    state.current_tracker = tracker;
}

// Transition rules enforcement
export function beginTransitionRulesEnforcement() {
    state.is_operation_running = true;
    state.is_rules_enforcement_running = true;
}

export function endTransitionRulesEnforcement() {
    state.is_operation_running = false;
    state.is_rules_enforcement_running = false;
}

export function beginWorkflowModeChange() {
    state.is_operation_running = true;
    state.is_workflow_mode_change_running = true;
}

export function endWorkflowModeChange() {
    state.is_operation_running = false;
    state.is_workflow_mode_change_running = false;
}

export function resetState(state) {
    Object.assign(state, initial_state);
}
