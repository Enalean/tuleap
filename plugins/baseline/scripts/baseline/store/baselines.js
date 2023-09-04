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

import { getBaselines } from "../api/rest-querier";

export default {
    namespaced: true,

    state: {
        baselines: null,
        are_baselines_loading: false,
        is_baselines_loading_failed: false,
    },

    actions: {
        async load({ dispatch, commit }, { project_id }) {
            commit("startBaselinesLoading");
            try {
                const baselines = await getBaselines(project_id);

                const user_ids = baselines.map((baseline) => baseline.author_id);
                const users_loading = dispatch("loadUsers", { user_ids }, { root: true });

                const artifact_ids = baselines.map((baseline) => baseline.artifact_id);
                const artifacts_loading = dispatch(
                    "loadArtifacts",
                    { artifact_ids },
                    { root: true },
                );

                await users_loading;
                await artifacts_loading;
                commit("updateBaselines", baselines);
            } catch (e) {
                commit("failBaselinesLoading");
            } finally {
                commit("stopBaselinesLoading");
            }
        },
    },

    mutations: {
        startBaselinesLoading: (state) => {
            state.is_baselines_loading_failed = false;
            state.are_baselines_loading = true;
        },
        failBaselinesLoading: (state) => {
            state.is_baselines_loading_failed = true;
            state.are_baselines_loading = false;
        },
        stopBaselinesLoading: (state) => {
            state.is_baselines_loading_failed = false;
            state.are_baselines_loading = false;
        },
        updateBaselines: (state, baselines) => {
            state.baselines = baselines;
        },
        delete: (state, baseline_to_delete) => {
            state.baselines = state.baselines.filter(
                (baseline) => baseline.id !== baseline_to_delete.id,
            );
        },
    },

    getters: {
        are_baselines_available: (state) => state.baselines !== null && state.baselines.length > 0,
    },
};
