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

    setDates(state, [start_date, end_date]) {
        state.start_date = start_date;
        state.end_date = end_date;
        state.reading_mode = !state.reading_mode;
        state.query_has_changed = true;
    }
};
