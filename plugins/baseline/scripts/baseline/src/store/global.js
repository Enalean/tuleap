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

import Vue from "vue";
import ArrayUtils from "../support/array-utils";
import { getBaseline, getUser, getTracker, getArtifact } from "../api/rest-querier";

export default {
    state: {
        baselines_by_id: {},
        users_by_id: {},
        artifacts_by_id: {},
        trackers_by_id: {},
    },
    actions: {
        async loadBaselines({ dispatch, getters }, { baseline_ids }) {
            let unique_baseline_ids = ArrayUtils.unique(baseline_ids);
            let baselines_loading = unique_baseline_ids.map((baseline_id) =>
                dispatch("loadBaseline", { baseline_id }),
            );
            await Promise.all(baselines_loading);

            const artifact_ids = unique_baseline_ids
                .map((id) => getters.findBaselineById(id))
                .map((baseline) => baseline.artifact_id);
            await dispatch("loadArtifacts", { artifact_ids });
        },
        async loadBaselineWithAuthor({ commit }, { baseline_id }) {
            const baseline = await getBaseline(baseline_id);
            commit("addBaseline", baseline);
            const author = await getUser(baseline.author_id);
            commit("addUser", author);
        },
        async loadBaseline({ commit }, { baseline_id }) {
            const baseline = await getBaseline(baseline_id);
            commit("addBaseline", baseline);
        },
        async loadUsers({ dispatch }, { user_ids }) {
            let users_loading = ArrayUtils.unique(user_ids).map((user_id) =>
                dispatch("loadUser", { user_id }),
            );
            await Promise.all(users_loading);
        },
        async loadUser({ commit }, { user_id }) {
            const user = await getUser(user_id);
            commit("addUser", user);
        },
        async loadArtifacts({ dispatch, getters }, { artifact_ids }) {
            let unique_artifact_ids = ArrayUtils.unique(artifact_ids);
            let artifacts_loading = unique_artifact_ids.map((artifact_id) =>
                dispatch("loadArtifact", { artifact_id }),
            );
            await Promise.all(artifacts_loading);

            const tracker_ids = unique_artifact_ids
                .map((id) => getters.findArtifactById(id))
                .map((artifact) => artifact.tracker.id);
            await dispatch("loadTrackers", { tracker_ids });
        },
        async loadArtifact({ commit }, { artifact_id }) {
            const artifact = await getArtifact(artifact_id);
            commit("addArtifact", artifact);
        },
        async loadTrackers({ dispatch }, { tracker_ids }) {
            let trackers_loading = ArrayUtils.unique(tracker_ids).map((tracker_id) =>
                dispatch("loadTracker", { tracker_id }),
            );
            await Promise.all(trackers_loading);
        },
        async loadTracker({ commit }, { tracker_id }) {
            const tracker = await getTracker(tracker_id);
            commit("addTracker", tracker);
        },
    },
    mutations: {
        addBaseline: (state, baseline) => Vue.set(state.baselines_by_id, baseline.id, baseline),
        addUser: (state, user) => Vue.set(state.users_by_id, user.id, user),
        addArtifact: (state, artifact) => Vue.set(state.artifacts_by_id, artifact.id, artifact),
        addTracker: (state, tracker) => Vue.set(state.trackers_by_id, tracker.id, tracker),
    },
    getters: {
        findBaselineById: (state) => (id) => state.baselines_by_id[id],
        findUserById: (state) => (id) => state.users_by_id[id],
        findArtifactById: (state) => (id) => state.artifacts_by_id[id],
        findTrackerById: (state) => (id) => state.trackers_by_id[id],
    },
};
