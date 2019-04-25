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
 */

import { getBaselineArtifacts, getBaselineArtifactsByIds } from "../api/rest-querier";
import { compareArtifacts } from "../support/comparison";
import { ARTIFACTS_EXPLORATION_DEPTH_LIMIT } from "../constants";

export default {
    namespaced: true,

    state: {
        base_baseline_id: null,
        first_level_base_artifacts: null,
        base_artifacts_by_id: null,

        compared_to_baseline_id: null,
        first_level_compared_to_artifacts: null,
        compared_to_artifacts_by_id: null,

        added_artifacts_count: null,
        removed_artifacts_count: null,
        modified_artifacts_count: null,
        initial_effort_difference: null,

        artifact_exploration_depth: null
    },

    actions: {
        async load({ dispatch, commit }, { base_baseline_id, compared_to_baseline_id }) {
            commit("startNewComparison", { base_baseline_id, compared_to_baseline_id });

            await Promise.all([
                dispatch("loadBaseline", { baseline_id: base_baseline_id }, { root: true }),
                dispatch("loadBaseline", { baseline_id: compared_to_baseline_id }, { root: true }),
                dispatch("loadAllArtifacts")
            ]);
        },

        async loadAllArtifacts({ dispatch, state }) {
            const [base_artifacts, compared_to_artifacts] = await Promise.all([
                getBaselineArtifacts(state.base_baseline_id),
                getBaselineArtifacts(state.compared_to_baseline_id)
            ]);

            await dispatch("loadArtifacts", { base_artifacts, compared_to_artifacts });
        },

        async loadArtifacts({ commit, dispatch }, { base_artifacts, compared_to_artifacts }) {
            commit("addBaseArtifacts", base_artifacts);
            commit("addComparedToArtifacts", compared_to_artifacts);
            const artifacts_comparison = compareArtifacts(base_artifacts, compared_to_artifacts);
            commit("updateStatistics", artifacts_comparison);
            commit("updateArtifactExplorationDepth");

            await Promise.all(
                artifacts_comparison.identical_or_modified.map(({ base, compared_to }) =>
                    dispatch("loadLinkedArtifacts", {
                        base_artifact: base,
                        compared_to_artifact: compared_to
                    })
                )
            );
        },

        async loadLinkedArtifacts({ dispatch, state }, { base_artifact, compared_to_artifact }) {
            const [base_artifacts, compared_to_artifacts] = await Promise.all([
                getBaselineArtifactsByIds(
                    state.base_baseline_id,
                    base_artifact.linked_artifact_ids
                ),
                getBaselineArtifactsByIds(
                    state.compared_to_baseline_id,
                    compared_to_artifact.linked_artifact_ids
                )
            ]);

            await dispatch("loadArtifacts", {
                base_artifacts,
                compared_to_artifacts
            });
        }
    },

    mutations: {
        startNewComparison: (state, { base_baseline_id, compared_to_baseline_id }) => {
            state.base_baseline_id = base_baseline_id;
            state.first_level_base_artifacts = null;
            state.base_artifacts_by_id = {};

            state.compared_to_baseline_id = compared_to_baseline_id;
            state.first_level_compared_to_artifacts = null;
            state.compared_to_artifacts_by_id = {};

            state.added_artifacts_count = 0;
            state.removed_artifacts_count = 0;
            state.modified_artifacts_count = 0;
            state.initial_effort_difference = 0;

            state.artifact_exploration_depth = 0;
        },
        addBaseArtifacts: (state, artifacts) => {
            if (state.first_level_base_artifacts === null) {
                state.first_level_base_artifacts = artifacts;
            }
            artifacts.forEach(artifact => (state.base_artifacts_by_id[artifact.id] = artifact));
        },
        addComparedToArtifacts: (state, artifacts) => {
            if (state.first_level_compared_to_artifacts === null) {
                state.first_level_compared_to_artifacts = artifacts;
            }
            artifacts.forEach(
                artifact => (state.compared_to_artifacts_by_id[artifact.id] = artifact)
            );
        },
        updateStatistics: (state, artifacts_comparison) => {
            state.added_artifacts_count += artifacts_comparison.added.length;
            state.removed_artifacts_count += artifacts_comparison.removed.length;
            state.modified_artifacts_count += artifacts_comparison.modified.length;

            artifacts_comparison.identical_or_modified.forEach(({ base, compared_to }) => {
                state.initial_effort_difference +=
                    (compared_to.initial_effort || 0) - (base.initial_effort || 0);
            });
        },
        updateArtifactExplorationDepth: state => state.artifact_exploration_depth++
    },

    getters: {
        findBaseArtifactsByIds: state => ids => ids.map(id => state.base_artifacts_by_id[id]),
        findComparedToArtifactsByIds: state => ids =>
            ids.map(id => state.compared_to_artifacts_by_id[id]),
        is_depth_limit_reached(state) {
            return state.artifact_exploration_depth > ARTIFACTS_EXPLORATION_DEPTH_LIMIT;
        }
    }
};
