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

import Vue from "vue";
import { FLOAT_FIELD, INT_FIELD } from "@tuleap/plugin-tracker-constants";

export {
    clearModalShown,
    showModal,
    saveCurrentTransition,
    initUserGroups,
    endLoadingModal,
    failModalOperation,
    beginModalSave,
    endModalSave,
    updateIsCommentRequired,
    updateNotEmptyFieldIds,
    updateAuthorizedUserGroupIds,
    savePostActions,
    updateSetValuePostActionField,
    updateSetValuePostActionValue,
    updateRunJobPostActionJobUrl,
    addPostAction,
    deletePostAction,
    updatePostActionType,
    updateFrozenFieldsPostActionFieldIds,
    updateHiddenFieldsetsPostActionFieldsetIds,
    setUsedServiceName,
    setIsSplitFeatureFlagEnabled,
};

function showModal(state) {
    state.is_modal_shown = true;
    state.is_loading_modal = true;
    state.current_transition = null;
}

function clearModalShown(state) {
    state.is_modal_shown = false;
    state.is_modal_operation_failed = false;
    state.modal_operation_failure_message = null;
}

function saveCurrentTransition(state, transition) {
    state.current_transition = transition;
}

function initUserGroups(state, user_groups) {
    state.user_groups = user_groups;
}

function endLoadingModal(state) {
    state.is_loading_modal = false;
}
function failModalOperation(state, message) {
    state.is_modal_operation_failed = true;
    state.modal_operation_failure_message = message;
}
function beginModalSave(state) {
    state.is_modal_save_running = true;
}
function endModalSave(state) {
    state.is_modal_save_running = false;
}

function updateIsCommentRequired(state, is_comment_required) {
    if (!state.current_transition) {
        return;
    }
    state.current_transition.is_comment_required = is_comment_required;
}
function updateNotEmptyFieldIds(state, not_empty_field_ids) {
    if (!state.current_transition) {
        return;
    }
    state.current_transition.not_empty_field_ids = not_empty_field_ids;
}
function updateAuthorizedUserGroupIds(state, authorized_user_group_ids) {
    if (!state.current_transition) {
        return;
    }
    state.current_transition.authorized_user_group_ids = authorized_user_group_ids;
}

function savePostActions(state, actions) {
    const post_actions = {};
    actions.forEach((post_action) => {
        const presented_post_action = presentPostAction(post_action);
        post_actions[presented_post_action.unique_id] = presented_post_action;
    });
    state.post_actions_by_unique_id = post_actions;
}

function presentPostAction(post_action) {
    let unique_id;
    if (post_action.type === "set_field_value") {
        unique_id = `${post_action.type}_${post_action.field_type}_${post_action.id}`;
    } else {
        unique_id = `${post_action.type}_${post_action.id}`;
    }

    return { ...post_action, unique_id };
}

function updatePostActionType(state, { post_action, type }) {
    updatePostAction(state, {
        unique_id: post_action.unique_id,
        type,
    });
}

function updateSetValuePostActionField(state, { post_action, new_field }) {
    const new_post_action = {
        ...post_action,
        field_id: new_field.field_id,
    };

    if (new_field.type !== new_post_action.field_type) {
        new_post_action.id = null;
    }
    new_post_action.field_type = new_field.type;

    if (post_action.field_type === INT_FIELD && new_field.type === FLOAT_FIELD) {
        new_post_action.value = post_action.value;
    } else if (post_action.field_type === FLOAT_FIELD && new_field.type === INT_FIELD) {
        new_post_action.value = parseInt(post_action.value, 10) || null;
    } else {
        new_post_action.value = null;
    }

    updatePostAction(state, new_post_action);
}

function updateSetValuePostActionValue(state, { post_action, value }) {
    updatePostAction(state, {
        ...post_action,
        value,
    });
}

function updateRunJobPostActionJobUrl(state, { post_action, job_url }) {
    updatePostAction(state, {
        ...post_action,
        job_url,
    });
}

function updateFrozenFieldsPostActionFieldIds(state, { post_action, field_ids }) {
    updatePostAction(state, {
        ...post_action,
        field_ids,
    });
}

function updateHiddenFieldsetsPostActionFieldsetIds(state, { post_action, fieldset_ids }) {
    updatePostAction(state, {
        ...post_action,
        fieldset_ids,
    });
}

function updatePostAction(state, new_action) {
    const post_actions = { ...state.post_actions_by_unique_id };
    post_actions[new_action.unique_id] = new_action;
    state.post_actions_by_unique_id = post_actions;
}

function addPostAction(state) {
    state.new_post_action_unique_id_index += 1;
    updatePostAction(state, {
        unique_id: `new_${state.new_post_action_unique_id_index}`,
        type: "run_job",
    });
}

function deletePostAction(state, post_action) {
    Vue.delete(state.post_actions_by_unique_id, post_action.unique_id);
}

function setUsedServiceName(state, used_service_name) {
    state.used_services_names = used_service_name;
}

function setIsSplitFeatureFlagEnabled(state, is_split_feature_flag_enabled) {
    state.is_split_feature_flag_enabled = is_split_feature_flag_enabled;
}
