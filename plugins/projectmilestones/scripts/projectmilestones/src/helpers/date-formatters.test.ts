/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { formatDateYearMonthDay } from "./date-formatters";
import { setUserLocale } from "./user-locale-helper";

describe("Date formatters", () => {
    describe("formatDateYearMonthDay", () => {
        it("Given date, When I call this function with an ISO date, then it should return date at good format", () => {
            setUserLocale("en-US");
            const date_iso = new Date("2017-01-22T13:42:08+02:00");
            expect(formatDateYearMonthDay(date_iso.toDateString())).toEqual("Jan 22, 2017");
        });

        it("Given empty string, When I call this function with date null, then it should return empty string", () => {
            expect(formatDateYearMonthDay(null)).toEqual("");
        });

        it("Given empty string, When I call this function with an empty string, then it should return empty string", () => {
            expect(formatDateYearMonthDay("")).toEqual("");
        });
    });
});
