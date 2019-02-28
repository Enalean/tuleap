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
import { DateTime } from "luxon";

const state = {
    report_id: null,
    start_date: DateTime.local()
        .minus({ months: 1 })
        .toISODate(),
    end_date: DateTime.local().toISODate(),
    error_message: null,
    selected_trackers: [],
    trackers_times: [],
    is_loading: false,
    reading_mode: true
};

export default state;
