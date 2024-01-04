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
 */

import abstract_baseline_content from "./current_comparison/abstract_baseline_content";
import ArrayUtils from "../support/array-utils";

export default {
    namespaced: true,

    ...abstract_baseline_content,

    state: {
        ...abstract_baseline_content.state,
        hidden_tracker_ids: [],
    },

    actions: {
        ...abstract_baseline_content.actions,
        async load({ commit, dispatch }, baseline_id) {
            commit("reset", { baseline_id });
            commit("semantics/reset", null, { root: true });
            await Promise.all([
                dispatch("loadBaselineWithAuthor", { baseline_id }, { root: true }),
                dispatch("loadAllArtifacts"),
            ]);
        },
    },

    mutations: {
        ...abstract_baseline_content.mutations,
        filterTrackers: (state, hidden_trackers) =>
            (state.hidden_tracker_ids = ArrayUtils.mapAttribute(hidden_trackers, "id")),
    },

    getters: {
        ...abstract_baseline_content.getters,
        filterArtifacts: (state) => (artifacts) =>
            artifacts.filter(
                (artifact) => state.hidden_tracker_ids.indexOf(artifact.tracker_id) === -1,
            ),
    },
};
