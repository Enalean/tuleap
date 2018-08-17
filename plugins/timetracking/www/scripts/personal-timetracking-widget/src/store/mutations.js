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
    setStartDate(state, date) {
        state.start_date = date;
    },

    setEndDate(state, date) {
        state.end_date = date;
    },

    toggleReadingMode(state) {
        state.reading_mode = !state.reading_mode;
    },

    setQueryHasChanged(state, has_changed) {
        state.query_has_changed = has_changed;
    },

    setParametersForNewQuery(state, [start_date, end_date]) {
        state.start_date = start_date;
        state.end_date = end_date;
        state.reading_mode = !state.reading_mode;
        state.times = [];
        state.pagination_offset = 0;
    },

    setIsLoaded(state, is_loaded) {
        state.is_loaded = is_loaded;
    },

    setTotalTimes(state, total_times) {
        state.total_times = total_times;
    },

    setPaginationOffset(state, pagination_offset) {
        state.pagination_offset = pagination_offset;
    },

    setPaginationLimit(state, pagination_limit) {
        state.pagination_limit = pagination_limit;
    },

    loadAChunkOfTimes(state, [times, total]) {
        state.times = state.times.concat(Object.values(times));
        state.pagination_offset += state.pagination_limit;
        state.total_times = total;
        state.is_loaded = true;
    },

    resetErrorMessage(state) {
        state.error_message = "";
    },

    setErrorMessage(state, error_message) {
        state.error_message = error_message;
    },

    setTimes(state, times) {
        state.times = state.times.concat(Object.values(times));
    },

    setIsLoading(state, isLoading) {
        state.is_loading = isLoading;
    }
};
