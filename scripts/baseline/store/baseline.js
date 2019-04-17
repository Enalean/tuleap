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
        baseline: null,
        is_baseline_loading: false,
        is_baseline_loading_failed: false
    },

    actions: {
        async load({ commit }, baseline_id) {
            commit("startBaselineLoading");
            try {
                const presented_baseline_as_graph = await presentBaselineWithArtifactsAsGraph(
                    baseline_id
                );
                commit("updateBaseline", presented_baseline_as_graph);
            } catch (e) {
                commit("failBaselineLoading");
            } finally {
                commit("stopBaselineLoading");
            }
        }
    },

    mutations: {
        startBaselineLoading: state => {
            state.is_baseline_loading_failed = false;
            state.is_baseline_loading = true;
        },
        failBaselineLoading: state => {
            state.is_baseline_loading_failed = true;
        },
        stopBaselineLoading: state => {
            state.is_baseline_loading = false;
        },
        updateBaseline: (state, baseline) => {
            state.baseline = baseline;
        }
    }
};
