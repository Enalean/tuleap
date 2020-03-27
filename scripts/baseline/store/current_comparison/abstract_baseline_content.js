/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import Vue from "vue";
import { getBaselineArtifacts, getBaselineArtifactsByIds } from "../../api/rest-querier";
import { ARTIFACTS_EXPLORATION_DEPTH_LIMIT } from "../../constants";
import ArrayUtils from "../../support/array-utils";

export default {
    namespaced: true,

    state: {
        baseline_id: null,
        first_depth_artifacts: null,
        artifacts_where_depth_limit_reached: null,
        artifacts_by_id: {},

        loaded_depths_count: null,
    },

    actions: {
        async loadAllArtifacts({ commit, dispatch, state }) {
            const artifacts = await getBaselineArtifacts(state.baseline_id);
            commit("updateFirstLevelArtifacts", artifacts);
            await dispatch("addArtifacts", artifacts);
        },

        async addArtifacts({ commit, dispatch, state }, artifacts) {
            commit("addArtifacts", artifacts);

            commit("incrementLoadedDepthsCount");
            if (state.loaded_depths_count > ARTIFACTS_EXPLORATION_DEPTH_LIMIT) {
                commit("updateArtifactsWhereDepthLimitReached", artifacts);
                return;
            }

            const linked_artifact_ids = ArrayUtils.unique(
                ArrayUtils.mapAttribute(artifacts, "linked_artifact_ids").flat()
            );
            if (linked_artifact_ids.length === 0) {
                return;
            }
            const linked_artifacts = await getBaselineArtifactsByIds(
                state.baseline_id,
                linked_artifact_ids
            );
            await dispatch("addArtifacts", linked_artifacts);
        },
    },

    mutations: {
        reset: (state, { baseline_id }) => {
            state.baseline_id = baseline_id;
            state.first_depth_artifacts = null;
            state.artifacts_where_depth_limit_reached = null;
            state.artifacts_by_id = {};
        },
        updateFirstLevelArtifacts: (state, artifacts) => {
            state.first_depth_artifacts = artifacts;
        },
        updateArtifactsWhereDepthLimitReached: (state, artifacts) => {
            state.artifacts_where_depth_limit_reached = artifacts;
        },
        addArtifacts: (state, artifacts) => {
            artifacts.forEach((artifact) => Vue.set(state.artifacts_by_id, artifact.id, artifact));
        },
        incrementLoadedDepthsCount: (state) => state.loaded_depths_count++,
    },

    getters: {
        findArtifactsByIds: (state) => (ids) =>
            ids.map((id) => state.artifacts_by_id[id]).filter((artifact) => artifact !== undefined),
        is_depth_limit_reached: (state) =>
            state.artifacts_where_depth_limit_reached !== null &&
            state.artifacts_where_depth_limit_reached.length > 0,
        isLimitReachedOnArtifact: (state, getters) => (artifact) =>
            getters.is_depth_limit_reached &&
            state.artifacts_where_depth_limit_reached.indexOf(artifact) !== -1,
        all_trackers: (state) =>
            ArrayUtils.uniqueByAttribute(
                Object.values(state.artifacts_by_id).map((artifact) => ({
                    id: artifact.tracker_id,
                    name: artifact.tracker_name,
                })),
                "id"
            ),
    },
};
