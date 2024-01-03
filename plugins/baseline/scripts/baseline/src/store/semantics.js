/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { getTracker } from "../api/rest-querier";

export default {
    namespaced: true,

    state: {
        fields_by_tracker_id: {},
        is_field_by_tracker_id_loading: {},
    },

    actions: {
        async loadByTrackerId({ state, commit }, tracker_id) {
            if (state.is_field_by_tracker_id_loading[tracker_id] === true) {
                return;
            }
            try {
                commit("startLoading", tracker_id);
                const tracker = await getTracker(tracker_id);
                commit("update", tracker);
            } finally {
                commit("stopLoading", tracker_id);
            }
        },
    },

    mutations: {
        reset(state) {
            state.fields_by_tracker_id = {};
        },
        startLoading(state, tracker_id) {
            state.is_field_by_tracker_id_loading = {
                ...state.is_field_by_tracker_id_loading,
                [tracker_id]: true,
            };
        },
        update(state, tracker) {
            const semantic_fields = { ...state.fields_by_tracker_id };

            if (!Object.prototype.hasOwnProperty.call(semantic_fields, tracker.id)) {
                semantic_fields[tracker.id] = {};
            }

            for (var semantic in tracker.semantics) {
                const semantic_field_id = tracker.semantics[semantic].field_id;
                if (semantic_field_id === null) {
                    continue;
                }
                const matching_semantic_field = tracker.fields.filter(
                    (field) => field.field_id === semantic_field_id,
                );
                if (matching_semantic_field.length === 0) {
                    continue;
                }
                semantic_fields[tracker.id][semantic] = matching_semantic_field[0];
            }

            state.fields_by_tracker_id = semantic_fields;
        },
        stopLoading(state, tracker_id) {
            state.is_field_by_tracker_id_loading = {
                ...state.is_field_by_tracker_id_loading,
                [tracker_id]: false,
            };
        },
    },

    getters: {
        is_field_label_available: (state, getters) => (tracker_id, semantic) =>
            state.is_field_by_tracker_id_loading[tracker_id] === false &&
            getters.field_label(tracker_id, semantic) !== null,

        field_label: (state) => (tracker_id, semantic) => {
            if (
                !Object.prototype.hasOwnProperty.call(state.fields_by_tracker_id, tracker_id) ||
                !Object.prototype.hasOwnProperty.call(
                    state.fields_by_tracker_id[tracker_id],
                    semantic,
                )
            ) {
                return null;
            }
            return state.fields_by_tracker_id[tracker_id][semantic].label;
        },
    },
};
