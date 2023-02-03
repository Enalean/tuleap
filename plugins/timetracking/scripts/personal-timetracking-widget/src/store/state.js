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

const a_week_ago = new Date();
a_week_ago.setDate(a_week_ago.getDate() - 7);

const state = {
    user_id: null,
    start_date: a_week_ago.toISOString().split("T")[0],
    end_date: new Date().toISOString().split("T")[0],
    reading_mode: true,
    total_times: 0,
    user_locale: null,
    pagination_offset: 0,
    pagination_limit: 50,
    is_loaded: false,
    times: [],
    error_message: "",
    current_times: [],
    is_add_mode: false,
    rest_feedback: {
        message: null,
        type: null,
    },
    is_loading: false,
};

export default state;
