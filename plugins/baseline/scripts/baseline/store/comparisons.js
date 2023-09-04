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

import { getComparisons } from "../api/rest-querier";

export default {
    namespaced: true,

    state: {
        comparisons: null,
        is_loading: false,
        is_loading_failed: false,
    },

    actions: {
        async load({ dispatch, commit }, { project_id }) {
            commit("startLoading");
            try {
                const comparisons = await getComparisons(project_id);

                const baseline_ids = comparisons
                    .map((comparison) => [
                        comparison.base_baseline_id,
                        comparison.compared_to_baseline_id,
                    ])
                    .flat();
                const baselines_loading = dispatch(
                    "loadBaselines",
                    { baseline_ids },
                    { root: true },
                );

                await baselines_loading;

                commit("updateComparisons", comparisons);
            } catch (e) {
                commit("failLoading");
            } finally {
                commit("stopLoading");
            }
        },
    },

    mutations: {
        startLoading: (state) => {
            state.is_loading_failed = false;
            state.is_loading = true;
        },
        failLoading: (state) => {
            state.is_loading_failed = true;
        },
        stopLoading: (state) => {
            state.is_loading_failed = false;
            state.is_loading = false;
        },
        updateComparisons: (state, comparisons) => {
            state.comparisons = comparisons;
        },
        delete: (state, comparison_to_delete) => {
            state.comparisons = state.comparisons.filter(
                (comparison) => comparison.id !== comparison_to_delete.id,
            );
        },
    },
    getters: {
        are_some_available: (state) => state.comparisons !== null && state.comparisons.length > 0,
    },
};
