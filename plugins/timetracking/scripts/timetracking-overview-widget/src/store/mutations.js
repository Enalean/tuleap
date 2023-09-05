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

export default {
    setSelectedTrackers(state, trackers) {
        state.selected_trackers = trackers;
    },

    setTrackersTimes(state, times) {
        state.trackers_times = times;
        state.trackers_times.forEach(function (time) {
            setUsers(state, time);
        });
    },

    setDisplayVoidTrackers(state, are_void_trackers_hidden) {
        state.are_void_trackers_hidden = are_void_trackers_hidden;
    },

    initUserId(state, user_id) {
        state.user_id = user_id;
    },

    toggleDisplayVoidTrackers(state) {
        state.are_void_trackers_hidden = !state.are_void_trackers_hidden;
    },

    setLoadingTrackers(state, is_loading_trackers) {
        state.is_loading_trackers = is_loading_trackers;
    },

    setIsLoading(state, is_loading) {
        state.is_loading = is_loading;
    },

    resetMessages(state) {
        state.error_message = null;
        state.success_message = null;
    },

    setErrorMessage(state, error_message) {
        state.error_message = error_message;
    },

    setSuccessMessage(state, success_message) {
        state.success_message = success_message;
    },

    toggleReadingMode(state) {
        state.reading_mode = !state.reading_mode;
    },

    setProjects(state, projects) {
        state.projects = projects;
    },

    setStartDate(state, start_date) {
        state.start_date = start_date;
    },

    setEndDate(state, end_date) {
        state.end_date = end_date;
    },

    setTrackers(state, trackers) {
        trackers.forEach(function (tracker) {
            tracker.disabled = Boolean(
                state.selected_trackers.find(
                    (selected_tracker) => selected_tracker.id === tracker.id,
                ),
            );
        });
        state.trackers = trackers;
    },

    setTrackersIds(state) {
        state.trackers_ids = [];
        state.selected_trackers.forEach(function (tracker) {
            state.trackers_ids.push(tracker.id);
        });
    },

    setSelectedUser(state, user) {
        state.selected_user = user;
    },

    addSelectedTrackers(state, tracker_id) {
        state.is_added_tracker = false;
        state.trackers.forEach(function (tracker) {
            if (
                tracker.id === parseInt(tracker_id, 10) &&
                !state.selected_trackers.find(
                    (selected_tracker) => selected_tracker.id === tracker.id,
                )
            ) {
                state.selected_trackers.push(tracker);
                tracker.disabled = true;
            }
        });
        state.is_added_tracker = true;
    },

    setIsReportSave(state, is_report_saved) {
        state.is_report_saved = is_report_saved;
    },

    removeSelectedTracker(state, tracker) {
        state.selected_trackers.splice(state.selected_trackers.indexOf(tracker), 1);
    },

    setReportId(state, report_id) {
        state.report_id = report_id;
    },
};

function setUsers(state, time) {
    if (time.time_per_user.length > 0) {
        time.time_per_user.reduce(function (users, user_time) {
            if (!users.find((user) => user.user_id === user_time.user_id)) {
                const user = {
                    user_name: user_time.user_name,
                    user_id: user_time.user_id,
                };
                users.push(user);
            }
            return users;
        }, state.users);
    }
}
