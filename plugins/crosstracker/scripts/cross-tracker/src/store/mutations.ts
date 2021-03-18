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

import type { State, Tracker } from "../type";

export {
    setErrorMessage,
    resetFeedbacks,
    switchToReadingMode,
    switchToWritingMode,
    switchReportToSaved,
    discardUnsavedReport,
    setInvalidTrackers,
    resetInvalidTrackerList,
};

function setErrorMessage(state: State, message: string): void {
    state.error_message = message;
}

function resetFeedbacks(state: State): void {
    state.error_message = null;
    state.success_message = null;
}

function switchToReadingMode(state: State, saved_state: boolean): void {
    resetFeedbacks(state);
    state.reading_mode = true;
    state.is_report_saved = saved_state;
}

function switchToWritingMode(state: State): void {
    resetFeedbacks(state);
    state.reading_mode = false;
}

function switchReportToSaved(state: State, message: string): void {
    state.success_message = message;
    state.error_message = null;
    state.is_report_saved = true;
}

function discardUnsavedReport(state: State): void {
    resetFeedbacks(state);
    state.is_report_saved = true;
}

function setInvalidTrackers(state: State, invalid_trackers: Array<Tracker>): void {
    state.invalid_trackers = invalid_trackers;
}

function resetInvalidTrackerList(state: State): void {
    state.invalid_trackers = [];
}
