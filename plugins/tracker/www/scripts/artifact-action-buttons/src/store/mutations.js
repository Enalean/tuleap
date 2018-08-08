/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

export default {
    setIsLoadingInitial(state, is_loading_initial) {
        state.is_loading_initial = is_loading_initial;
    },
    saveProjects(state, projects) {
        state.projects = projects;
    },
    saveSelectedProjectId(state, project_id) {
        state.selected_project_id = project_id;
    },
    saveTrackers(state, trackers) {
        state.trackers = trackers;
    },
    setErrorMessage(state, error_message) {
        state.error_message = error_message;
    },
    resetState(state) {
        state.is_loading_initial = true;
        state.are_trackers_loading = false;
        state.error_message = "";
        state.projects = [];
        state.trackers = [];
        state.selected_project_id = null;

        state.selected_tracker = {
            tracker_id: null
        };
    },
    setAreTrackerLoading(state, status) {
        state.are_trackers_loading = status;
    },
    setSelectedTracker(state, tracker) {
        state.selected_tracker = tracker;
    },
    resetSelectedTracker(state) {
        state.selected_tracker = {
            tracker_id: null
        };
    }
};
