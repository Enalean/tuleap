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

import { getBaselineArtifacts, getBaselineArtifactsByIds } from "../../api/rest-querier";
import { ARTIFACTS_EXPLORATION_DEPTH_LIMIT } from "../../constants";
import ArrayUtils from "../../support/array-utils";

export default {
    namespaced: true,

    state: {
        baseline_id: null,
        first_depth_artifacts: null,
        artifacts_by_id: null,

        loaded_depths_count: null
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
        }
    },

    mutations: {
        reset: (state, { baseline_id }) => {
            state.baseline_id = baseline_id;
            state.first_depth_artifacts = null;
            state.artifacts_by_id = {};
        },
        updateFirstLevelArtifacts: (state, artifacts) => {
            state.first_depth_artifacts = artifacts;
        },
        addArtifacts: (state, artifacts) => {
            artifacts.forEach(artifact => (state.artifacts_by_id[artifact.id] = artifact));
        },
        incrementLoadedDepthsCount: state => state.loaded_depths_count++
    },

    getters: {
        findArtifactsByIds: state => ids =>
            ids.map(id => state.artifacts_by_id[id]).filter(artifact => artifact !== undefined)
    }
};
