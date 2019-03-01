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
    setSelectedTrackers(state, trackers) {
        state.selected_trackers = trackers;
    },

    setTrackersTimes(state, times) {
        state.trackers_times = times;
    },

    setIsLoading(state, is_loading) {
        state.is_loading = is_loading;
    },

    resetErrorMessage(state) {
        state.error_message = null;
    },

    setErrorMessage(state, error_message) {
        state.error_message = error_message;
    },

    setReportId(state, report_id) {
        state.report_id = report_id;
    },

    toggleReadingMode(state) {
        state.reading_mode = !state.reading_mode;
    },

    setStartDate(state, start_date) {
        state.start_date = start_date;
    },

    setEndDate(state, end_date) {
        state.end_date = end_date;
    }
};
