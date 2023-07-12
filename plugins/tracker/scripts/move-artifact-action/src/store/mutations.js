/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import state from "./state.js";

const initial_state = { ...state };

export default {
    resetProjectLoading(state) {
        state.is_loading_initial = false;
    },
    saveSelectedProjectId(state, project_id) {
        state.selected_project_id = project_id;
    },
    loadingTrackersAfterProjectSelected(state, project_id) {
        state.are_trackers_loading = true;
        state.selected_project_id = project_id;
        state.trackers = [];
        state.selected_tracker = {
            tracker_id: null,
        };
        state.has_processed_dry_run = false;
    },
    resetTrackersLoading(state) {
        state.are_trackers_loading = false;
    },
    saveProjects(state, projects) {
        state.projects = projects;
    },
    saveTrackers(state, trackers) {
        state.trackers = trackers;
    },
    saveSelectedTracker(state, tracker) {
        state.selected_tracker = tracker;
        state.has_processed_dry_run = false;
        resetError(state);
    },
    hasProcessedDryRun(state, fields) {
        state.dry_run_fields = fields;
        state.has_processed_dry_run = true;
    },
    resetError,
    setErrorMessage(state, error_message) {
        state.error_message = error_message;
    },
    switchToProcessingMove(state) {
        state.is_processing_move = true;
    },
    resetProcessingMove(state) {
        state.is_processing_move = false;
    },
    resetState(state) {
        Object.assign(state, initial_state);
    },
    blockArtifactMove(state) {
        state.is_move_possible = false;
    },
};

function resetError(state) {
    state.error_message = "";
}
