/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { getMonths } from "./months";

describe("months", () => {
    it("Returns empty array if no start", () => {
        const start = null;
        const end = new Date(2020, 2, 15);

        expect(getMonths(start, end)).toStrictEqual([]);
    });

    it("Returns empty array if no end", () => {
        const start = new Date(2020, 2, 15);
        const end = null;

        expect(getMonths(start, end)).toStrictEqual([]);
    });

    it("Returns empty array if start is greater than end", () => {
        const start = new Date(2020, 5, 15);
        const end = new Date(2020, 2, 15);

        expect(getMonths(start, end)).toStrictEqual([]);
    });

    it("Returns an array of months that encloses the date range", () => {
        const start = new Date(2020, 2, 15);
        const end = new Date(2020, 5, 15);

        const months = getMonths(start, end);

        expect(months.map((month) => month.toDateString())).toStrictEqual([
            "Sun Mar 01 2020",
            "Wed Apr 01 2020",
            "Fri May 01 2020",
            "Mon Jun 01 2020",
            "Wed Jul 01 2020",
        ]);
    });

    it("Returns an array of months that encloses the date range even if the start and end date are in the same month", () => {
        const start = new Date(2020, 2, 15);
        const end = new Date(2020, 2, 15);

        const months = getMonths(start, end);

        expect(months.map((month) => month.toDateString())).toStrictEqual([
            "Sun Mar 01 2020",
            "Wed Apr 01 2020",
        ]);
    });
});
