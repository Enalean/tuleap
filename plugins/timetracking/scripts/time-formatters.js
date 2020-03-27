/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { Duration, DateTime } from "luxon";

export { formatMinutes, formatDatetimeToISO, formatDateDayMonthYear, sortTimesChronologically };

function formatMinutes(minutes) {
    return Duration.fromObject({ minutes }).toFormat("hh:mm");
}

function formatDatetimeToISO(string_date) {
    return DateTime.fromISO(string_date).toISO({
        suppressSeconds: false,
        suppressMilliseconds: true,
        includeOffset: true,
    });
}

function formatDateDayMonthYear(date) {
    return DateTime.fromISO(date).toLocaleString();
}

function sortTimesChronologically(times) {
    return times.sort((a, b) => {
        return new Date(b.date) - new Date(a.date);
    });
}
