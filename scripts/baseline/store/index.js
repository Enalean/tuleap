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
import { getTracker } from "../api/rest-querier";

export default {
    state: {
        semantic_fields_by_tracker_id: {},
        is_semantic_fields_by_tracker_id_loading: {}
    },
    actions: {
        async loadSemanticFields({ state, commit }, tracker_id) {
            if (state.is_semantic_fields_by_tracker_id_loading[tracker_id] === true) {
                return;
            }
            try {
                commit("startSemanticFieldsLoading", tracker_id);
                const tracker = await getTracker(tracker_id);
                commit("updateSemanticFields", tracker);
            } finally {
                commit("stopSemanticFieldsLoading", tracker_id);
            }
        }
    },
    mutations: {
        resetSemanticFields(state) {
            state.semantic_fields_by_tracker_id = {};
        },
        startSemanticFieldsLoading(state, tracker_id) {
            state.is_semantic_fields_by_tracker_id_loading = {
                ...state.is_semantic_fields_by_tracker_id_loading,
                [tracker_id]: true
            };
        },
        updateSemanticFields(state, tracker) {
            const semantic_fields = { ...state.semantic_fields_by_tracker_id };

            if (!semantic_fields.hasOwnProperty(tracker.id)) {
                semantic_fields[tracker.id] = {};
            }

            for (var semantic in tracker.semantics) {
                const semantic_field_id = tracker.semantics[semantic].field_id;
                if (semantic_field_id === null) {
                    continue;
                }
                const matching_semantic_field = tracker.fields.filter(
                    field => field.field_id === semantic_field_id
                );
                if (matching_semantic_field.length === 0) {
                    continue;
                }
                semantic_fields[tracker.id][semantic] = matching_semantic_field[0];
            }

            state.semantic_fields_by_tracker_id = semantic_fields;
        },
        stopSemanticFieldsLoading(state, tracker_id) {
            state.is_semantic_fields_by_tracker_id_loading = {
                ...state.is_semantic_fields_by_tracker_id_loading,
                [tracker_id]: false
            };
        }
    }
};
