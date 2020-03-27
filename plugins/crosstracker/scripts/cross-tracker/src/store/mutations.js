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

export {
    initWithDataset,
    setErrorMessage,
    resetFeedbacks,
    switchToReadingMode,
    switchToWritingMode,
    switchReportToSaved,
    discardUnsavedReport,
    setInvalidTrackers,
    resetInvalidTrackerList,
};

function initWithDataset(state, dataset) {
    state.report_id = dataset.report_id;
    state.is_user_admin = dataset.is_widget_admin;
}

function setErrorMessage(state, message) {
    state.error_message = message;
}

function resetFeedbacks(state) {
    state.error_message = null;
    state.success_message = null;
}

function switchToReadingMode(state, { saved_state }) {
    resetFeedbacks(state);
    state.reading_mode = true;
    state.is_report_saved = saved_state;
}

function switchToWritingMode(state) {
    resetFeedbacks(state);
    state.reading_mode = false;
}

function switchReportToSaved(state, message) {
    state.success_message = message;
    state.error_message = null;
    state.is_report_saved = true;
}

function discardUnsavedReport(state) {
    resetFeedbacks(state);
    state.is_report_saved = true;
}

function setInvalidTrackers(state, invalid_trackers) {
    state.invalid_trackers = invalid_trackers;
}

function resetInvalidTrackerList(state) {
    state.invalid_trackers = [];
}
