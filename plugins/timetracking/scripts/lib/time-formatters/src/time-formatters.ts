/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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

function padTimeNumber(number: number): string {
    if (number < 10) {
        return "0" + number;
    }
    return String(number);
}

export function formatMinutes(minutes: number): string {
    const remaining_minutes: number = minutes % 60;
    const hours: number = (minutes - remaining_minutes) / 60;

    return `${padTimeNumber(hours)}:${padTimeNumber(remaining_minutes)}`;
}

export function formatDatetimeToISO(string_date: string): string {
    const date: Date = new Date(string_date);

    return (
        date.getUTCFullYear() +
        "-" +
        padTimeNumber(date.getUTCMonth() + 1) +
        "-" +
        padTimeNumber(date.getUTCDate()) +
        "T" +
        padTimeNumber(date.getUTCHours()) +
        ":" +
        padTimeNumber(date.getUTCMinutes()) +
        ":" +
        padTimeNumber(date.getUTCSeconds()) +
        "Z"
    );
}

export function formatDatetimeToYearMonthDay(date: Date): string {
    return `${date.getUTCFullYear()}-${padTimeNumber(date.getUTCMonth() + 1)}-${padTimeNumber(
        date.getUTCDate(),
    )}`;
}

export function formatDateUsingPreferredUserFormat(date: Date, user_locale: string): string {
    return new Intl.DateTimeFormat(user_locale).format(new Date(date));
}
