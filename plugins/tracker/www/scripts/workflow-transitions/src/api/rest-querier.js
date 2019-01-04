/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { del, get, patch, post } from "tlp-fetch";

export {
    getTracker,
    createWorkflowTransitions,
    updateTransitionRulesEnforcement,
    resetWorkflowTransitions,
    createTransition,
    getTransition,
    deleteTransition,
    getUserGroups,
    patchTransition
};

const JSON_HEADERS = { "content-type": "application/json" };

async function getTracker(tracker_id) {
    const response = await get(`/api/trackers/${tracker_id}`);
    return response.json();
}

function createWorkflowTransitions(tracker_id, field_id) {
    const query = JSON.stringify({
        workflow: {
            set_transitions_rules: {
                field_id
            }
        }
    });
    return patch(`/api/trackers/${tracker_id}?query=${encodeURIComponent(query)}`);
}

async function resetWorkflowTransitions(tracker_id) {
    const query = JSON.stringify({
        workflow: {
            delete_transitions_rules: true
        }
    });

    const response = await patch(`/api/trackers/${tracker_id}?query=${encodeURIComponent(query)}`);
    return response.json();
}

async function updateTransitionRulesEnforcement(tracker_id, are_transition_rules_enforced) {
    const query = JSON.stringify({
        workflow: {
            set_transitions_rules: {
                is_used: are_transition_rules_enforced
            }
        }
    });
    const response = await patch(`/api/trackers/${tracker_id}?query=${encodeURIComponent(query)}`);
    return response.json();
}

async function createTransition(tracker_id, from_id, to_id) {
    const body = JSON.stringify({ tracker_id, from_id: from_id || 0, to_id });

    const response = await post("/api/tracker_workflow_transitions", {
        headers: JSON_HEADERS,
        body
    });
    return response.json();
}

async function getTransition(transition_id) {
    const response = await get(`/api/tracker_workflow_transitions/${transition_id}`);
    return response.json();
}

function deleteTransition(transition_id) {
    return del(`/api/tracker_workflow_transitions/${transition_id}`);
}

async function getUserGroups(project_id) {
    const response = await get(`/api/projects/${project_id}/user_groups`);
    return response.json();
}

function patchTransition({
    id,
    authorized_user_group_ids,
    not_empty_field_ids,
    is_comment_required
}) {
    if (!authorized_user_group_ids) {
        authorized_user_group_ids = [];
    }

    if (!not_empty_field_ids) {
        not_empty_field_ids = [];
    }

    return patch(`/api/tracker_workflow_transitions/${id}`, {
        headers: JSON_HEADERS,
        body: JSON.stringify({
            authorized_user_group_ids,
            not_empty_field_ids,
            is_comment_required
        })
    });
}
