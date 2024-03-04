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

import { TimePeriodQuarter } from "./time-period-quarter";
import { createVueGettextProviderPassthrough } from "./vue-gettext-provider-for-test";
import { DateTime } from "luxon";

function toDateString(collection: DateTime[]): string[] {
    return collection.map((date) => date.toJSDate().toDateString());
}

describe("TimePeriodQuarter", () => {
    let start!: DateTime;
    let end!: DateTime;

    it("returns quarters when start is lesser than end", () => {
        const start = DateTime.fromObject({ year: 2020, month: 2, day: 15 });
        const end = DateTime.fromObject({ year: 2020, month: 5, day: 15 });

        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());
        expect(toDateString(period.units)).toStrictEqual([
            "Wed Jan 01 2020",
            "Wed Apr 01 2020",
            "Wed Jul 01 2020",
        ]);
    });

    it("returns quarters when start is in the same quarter than end", () => {
        const start = DateTime.fromJSDate(new Date(2020, 2, 15));
        const end = DateTime.fromJSDate(new Date(2020, 2, 15));

        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());
        expect(toDateString(period.units)).toStrictEqual(["Wed Jan 01 2020", "Wed Apr 01 2020"]);
    });

    it("returns quarters when start is greater than end", () => {
        const start = DateTime.fromJSDate(new Date(2020, 3, 15));
        const end = DateTime.fromJSDate(new Date(2020, 1, 15));

        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());
        expect(toDateString(period.units)).toStrictEqual([
            "Wed Jan 01 2020",
            "Wed Apr 01 2020",
            "Wed Jul 01 2020",
        ]);
    });

    it.each([
        [1, "Q1", "Quarter 1 of 2020"],
        [2, "Q1", "Quarter 1 of 2020"],
        [3, "Q1", "Quarter 1 of 2020"],
        [4, "Q2", "Quarter 2 of 2020"],
        [5, "Q2", "Quarter 2 of 2020"],
        [6, "Q2", "Quarter 2 of 2020"],
        [7, "Q3", "Quarter 3 of 2020"],
        [8, "Q3", "Quarter 3 of 2020"],
        [9, "Q3", "Quarter 3 of 2020"],
        [10, "Q4", "Quarter 4 of 2020"],
        [11, "Q4", "Quarter 4 of 2020"],
        [12, "Q4", "Quarter 4 of 2020"],
    ])("Format month %s as short: %s and as long: %s", (month, expected_short, expected_long) => {
        start = DateTime.fromJSDate(new Date(2020, 2, 15));
        end = DateTime.fromJSDate(new Date(2020, 7, 15));
        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());

        expect(period.formatShort(DateTime.fromObject({ year: 2020, month, day: 15 }))).toBe(
            expected_short,
        );
        expect(period.formatLong(DateTime.fromObject({ year: 2020, month, day: 15 }))).toBe(
            expected_long,
        );
    });

    it.each([[-1], [0]])(
        "Returns empty array for additional units when nb is lesser than 0",
        (nb_missing_quarters) => {
            start = DateTime.fromJSDate(new Date(2020, 2, 15));
            end = DateTime.fromJSDate(new Date(2020, 7, 15));
            const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());

            expect(period.additionalUnits(nb_missing_quarters)).toStrictEqual([]);
        },
    );

    it("Returns an array of additional quarters", () => {
        start = DateTime.fromJSDate(new Date(2020, 2, 15));
        end = DateTime.fromJSDate(new Date(2020, 3, 15));
        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());

        expect(toDateString(period.additionalUnits(3))).toStrictEqual([
            "Thu Oct 01 2020",
            "Fri Jan 01 2021",
            "Thu Apr 01 2021",
        ]);
    });

    it("should return empty string for getEvenOddClass since we don't need special background alternance", () => {
        const start = DateTime.fromJSDate(new Date(2020, 2, 15));
        const end = DateTime.fromJSDate(new Date(2020, 3, 15));
        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());

        expect(period.getEvenOddClass()).toBe("");
    });
});
