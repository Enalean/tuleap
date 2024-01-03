/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { compareArtifacts } from "../../support/comparison";
import abstract_baseline_content from "./abstract_baseline_content";
import ArrayUtils from "../../support/array-utils";

export default {
    namespaced: true,

    state: {
        added_artifacts_count: null,
        removed_artifacts_count: null,
        modified_artifacts_count: null,
        initial_effort_difference: null,
        hidden_tracker_ids: [],
    },

    actions: {
        async load({ dispatch }, { base_baseline_id, compared_to_baseline_id }) {
            await dispatch("startNewComparison", { base_baseline_id, compared_to_baseline_id });

            await Promise.all([
                dispatch("loadBaseline", { baseline_id: base_baseline_id }, { root: true }),
                dispatch("loadBaseline", { baseline_id: compared_to_baseline_id }, { root: true }),
                dispatch("base/loadAllArtifacts"),
                dispatch("compared_to/loadAllArtifacts"),
            ]);

            await dispatch("computeStatistics");
        },

        startNewComparison({ commit }, { base_baseline_id, compared_to_baseline_id }) {
            commit("reset");
            commit("base/reset", { baseline_id: base_baseline_id });
            commit("compared_to/reset", { baseline_id: compared_to_baseline_id });
        },

        async computeStatistics({ dispatch, state }) {
            await dispatch("compareArtifacts", {
                base_artifacts: state.base.first_depth_artifacts,
                compared_to_artifacts: state.compared_to.first_depth_artifacts,
            });
        },

        async compareArtifacts(
            { commit, dispatch, getters },
            { base_artifacts, compared_to_artifacts },
        ) {
            const artifacts_comparison = compareArtifacts(base_artifacts, compared_to_artifacts);
            commit("incrementStatistics", artifacts_comparison);

            let comparisons = artifacts_comparison.identical_or_modified.map(
                async ({ base, compared_to }) => {
                    const linked_base_artifacts = getters["base/findArtifactsByIds"](
                        base.linked_artifact_ids,
                    );
                    const linked_compared_to_artifacts = getters["compared_to/findArtifactsByIds"](
                        compared_to.linked_artifact_ids,
                    );

                    await dispatch("compareArtifacts", {
                        base_artifacts: linked_base_artifacts,
                        compared_to_artifacts: linked_compared_to_artifacts,
                    });
                },
            );
            await Promise.all(comparisons);
        },
    },

    mutations: {
        reset: (state) => {
            state.added_artifacts_count = 0;
            state.removed_artifacts_count = 0;
            state.modified_artifacts_count = 0;
            state.initial_effort_difference = 0;
        },
        incrementStatistics: (state, artifacts_comparison) => {
            state.added_artifacts_count += artifacts_comparison.added.length;
            state.removed_artifacts_count += artifacts_comparison.removed.length;
            state.modified_artifacts_count += artifacts_comparison.modified.length;

            artifacts_comparison.identical_or_modified.forEach(({ base, compared_to }) => {
                state.initial_effort_difference +=
                    (compared_to.initial_effort || 0) - (base.initial_effort || 0);
            });
        },
        filterTrackers: (state, hidden_trackers) =>
            (state.hidden_tracker_ids = ArrayUtils.mapAttribute(hidden_trackers, "id")),
    },

    getters: {
        all_trackers: (state, getters) =>
            ArrayUtils.uniqueByAttribute(
                [...getters["base/all_trackers"], ...getters["compared_to/all_trackers"]],
                "id",
            ),
        filterArtifacts: (state) => (artifacts) =>
            artifacts.filter(
                (artifact) => state.hidden_tracker_ids.indexOf(artifact.tracker_id) === -1,
            ),
    },

    modules: {
        base: {
            ...abstract_baseline_content,
            // This is necessary because state is mutable (whereas others attributes of abstract_baseline_content), and
            // modules may not share the same state.
            state: { ...abstract_baseline_content.state },
        },
        compared_to: {
            ...abstract_baseline_content,
            state: { ...abstract_baseline_content.state },
        },
    },
};
