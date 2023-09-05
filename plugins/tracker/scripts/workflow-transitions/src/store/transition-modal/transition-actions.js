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

import { getErrorMessage } from "../exception-handler.js";
import {
    getTransition,
    getUserGroups,
    patchTransition,
    getPostActions,
    putPostActions,
} from "../../api/rest-querier.js";
import { animateUpdated } from "../../helpers/item-animator.js";

export {
    showTransitionConfigurationModal,
    loadTransition,
    loadUserGroupsIfNotCached,
    loadPostActions,
    saveTransitionRules,
    setUsedServiceName,
    setIsSplitFeatureFlagEnabled,
};

async function showTransitionConfigurationModal({ dispatch, commit }, transition) {
    commit("showModal");
    try {
        await Promise.all([
            dispatch("loadTransition", transition.id),
            dispatch("loadUserGroupsIfNotCached"),
            dispatch("loadPostActions", transition.id),
        ]);
    } catch (error) {
        const error_message = await getErrorMessage(error);
        commit("failModalOperation", error_message);
    } finally {
        commit("endLoadingModal");
    }
}

async function loadTransition({ commit }, transition_id) {
    const transition = await getTransition(transition_id);
    commit("saveCurrentTransition", transition);
}

async function loadUserGroupsIfNotCached({ state, commit, rootGetters }) {
    if (state.user_groups !== null) {
        return;
    }

    const user_groups = await getUserGroups(rootGetters.current_project_id);
    commit("initUserGroups", user_groups);
}

async function loadPostActions({ commit }, transition_id) {
    const actions = await getPostActions(transition_id);
    commit("savePostActions", actions);
}

async function saveTransitionRules({ commit, state, getters }) {
    try {
        commit("beginModalSave");
        await Promise.all([
            patchTransition(state.current_transition),
            putPostActions(state.current_transition.id, getters.post_actions),
        ]);
        const transition = { ...state.current_transition };
        animateUpdated(
            () => {
                commit("markTransitionUpdated", transition, { root: true });
            },
            () => {
                commit("hideTransitionUpdated", transition, { root: true });
            },
        );
        commit("clearModalShown");
    } catch (error) {
        const error_message = await getErrorMessage(error);
        commit("failModalOperation", error_message);
    } finally {
        commit("endModalSave");
    }
}

function setUsedServiceName({ commit }, used_service_name) {
    commit("setUsedServiceName", used_service_name);
}

function setIsSplitFeatureFlagEnabled({ commit }, is_split_feature_flag_enabled) {
    commit("setIsSplitFeatureFlagEnabled", is_split_feature_flag_enabled);
}
