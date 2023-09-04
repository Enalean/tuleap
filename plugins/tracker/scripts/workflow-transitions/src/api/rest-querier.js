/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { del, get, put, patch, post } from "@tuleap/tlp-fetch";

const JSON_HEADERS = { "content-type": "application/json" };

export async function getTracker(tracker_id) {
    const response = await get(`/api/trackers/${tracker_id}`);
    return response.json();
}

export async function createWorkflowTransitions(tracker_id, field_id) {
    const query = JSON.stringify({
        workflow: {
            set_transitions_rules: {
                field_id,
            },
        },
    });
    const response = await patch(`/api/trackers/${tracker_id}?query=${encodeURIComponent(query)}`);
    return response.json();
}

export async function resetWorkflowTransitions(tracker_id) {
    const query = JSON.stringify({
        workflow: {
            delete_transitions_rules: true,
        },
    });

    const response = await patch(`/api/trackers/${tracker_id}?query=${encodeURIComponent(query)}`);
    return response.json();
}

export async function updateTransitionRulesEnforcement(tracker_id, are_transition_rules_enforced) {
    const query = JSON.stringify({
        workflow: {
            set_transitions_rules: {
                is_used: are_transition_rules_enforced,
            },
        },
    });
    const response = await patch(`/api/trackers/${tracker_id}?query=${encodeURIComponent(query)}`);
    return response.json();
}

export async function createTransition(tracker_id, from_id, to_id) {
    const body = JSON.stringify({ tracker_id, from_id: from_id || 0, to_id });

    const response = await post("/api/tracker_workflow_transitions", {
        headers: JSON_HEADERS,
        body,
    });
    return response.json();
}

export async function getTransition(transition_id) {
    const response = await get(`/api/tracker_workflow_transitions/${transition_id}`);
    return response.json();
}

export function deleteTransition(transition_id) {
    return del(`/api/tracker_workflow_transitions/${transition_id}`);
}

export async function getUserGroups(project_id) {
    const query = JSON.stringify({ with_system_user_groups: true });
    const response = await get(
        `/api/projects/${project_id}/user_groups?query=${encodeURIComponent(query)}`,
    );
    return response.json();
}

export function patchTransition({
    id,
    authorized_user_group_ids,
    not_empty_field_ids,
    is_comment_required,
}) {
    const normalized_authorized_user_group_ids = !authorized_user_group_ids
        ? []
        : authorized_user_group_ids;
    const normalized_not_empty_field_ids = !not_empty_field_ids ? [] : not_empty_field_ids;

    return patch(`/api/tracker_workflow_transitions/${id}`, {
        headers: JSON_HEADERS,
        body: JSON.stringify({
            authorized_user_group_ids: normalized_authorized_user_group_ids,
            not_empty_field_ids: normalized_not_empty_field_ids,
            is_comment_required,
        }),
    });
}

export async function getPostActions(transition_id) {
    const response = await get(`/api/tracker_workflow_transitions/${transition_id}/actions`);
    return response.json();
}

export function putPostActions(transition_id, presented_post_actions) {
    const post_actions = presented_post_actions.map((presented_post_action) => ({
        ...presented_post_action,
        unique_id: undefined,
    }));
    return put(`/api/tracker_workflow_transitions/${transition_id}/actions`, {
        headers: JSON_HEADERS,
        body: JSON.stringify({ post_actions }),
    });
}

export async function deactivateLegacyTransitions(tracker_id) {
    const query = JSON.stringify({
        workflow: {
            is_legacy: false,
        },
    });
    const response = await patch(`/api/trackers/${tracker_id}?query=${encodeURIComponent(query)}`);
    return response.json();
}

export async function changeWorkflowMode(tracker_id, is_workflow_advanced) {
    const query = JSON.stringify({
        workflow: {
            is_advanced: is_workflow_advanced,
        },
    });
    const response = await patch(`/api/trackers/${tracker_id}?query=${encodeURIComponent(query)}`);
    return response.json();
}
