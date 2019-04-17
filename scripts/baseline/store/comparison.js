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

import { presentBaselineWithArtifactsAsGraph } from "../presenters/baseline";

export default {
    namespaced: true,

    state: {
        base_baseline: null,
        compared_to_baseline: null,
        is_comparison_loading: false,
        is_comparison_loading_failed: false
    },

    actions: {
        async load({ commit }, { base_baseline_id, compared_to_baseline_id }) {
            commit("startComparisonLoading");
            try {
                const presented_base_baseline_loading_as_graph = presentBaselineWithArtifactsAsGraph(
                    base_baseline_id
                );
                const presented_compared_to_baseline_loading_as_graph = presentBaselineWithArtifactsAsGraph(
                    compared_to_baseline_id
                );

                const presented_base_baseline_as_graph = await presented_base_baseline_loading_as_graph;
                const presented_compared_to_baseline_as_graph = await presented_compared_to_baseline_loading_as_graph;

                commit("updateComparison", {
                    base_baseline: presented_base_baseline_as_graph,
                    compared_to_baseline: presented_compared_to_baseline_as_graph
                });
            } catch (e) {
                commit("failComparisonLoading");
            } finally {
                commit("stopComparisonLoading");
            }
        }
    },

    mutations: {
        startComparisonLoading: state => {
            state.is_comparison_loading_failed = false;
            state.is_comparison_loading = true;
        },
        failComparisonLoading: state => {
            state.is_comparison_loading_failed = true;
        },
        stopComparisonLoading: state => {
            state.is_comparison_loading = false;
        },
        updateComparison: (state, { base_baseline, compared_to_baseline }) => {
            state.base_baseline = base_baseline;
            state.compared_to_baseline = compared_to_baseline;
        }
    }
};
