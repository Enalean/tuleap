/**
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
import moment from "moment";

export { getDaysToDisplay, getGranularity, getDifference, getFormattedDates };

function getDaysToDisplay({ opening_days, duration, points_with_date, start_date }) {
    const dates = [];
    const start = points_with_date.length ? points_with_date[0].date : start_date;
    const moment_iterator = moment(start, moment.ISO_8601);

    let i = 0;

    do {
        if (opening_days.includes(moment_iterator.isoWeekday())) {
            dates.push(moment_iterator.utc().format("YYYY-MM-DD"));
            i++;
        }

        moment_iterator.add(1, "day");
    } while (i <= duration);

    return dates;
}

function getGranularity(start_date, end_date) {
    const DAY = "day",
        WEEK = "week",
        MONTH = "month";

    const { weeks, months } = getDifference(start_date, end_date);

    switch (true) {
        case months >= 3:
            return MONTH;
        case weeks >= 2:
            return WEEK;
        default:
            return DAY;
    }
}

function getDifference(start_date, end_date) {
    const start = moment(start_date, moment.ISO_8601);
    const end = moment(end_date, moment.ISO_8601);
    const difference = moment.duration(end.diff(start, "days"), "days");

    return {
        days: Math.trunc(difference.as("days")),
        weeks: Math.trunc(difference.as("weeks")),
        months: Math.trunc(difference.as("months")),
    };
}

function getFormattedDates(dataset) {
    return dataset.map((data) => {
        const new_data = Object.assign({}, data);
        new_data.date = moment(data.date, moment.ISO_8601).utc().format("YYYY-MM-DD");
        return new_data;
    });
}
