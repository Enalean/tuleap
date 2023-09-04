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

function toDateString(collection: Date[]): string[] {
    return collection.map((date) => date.toDateString());
}

describe("TimePeriodQuarter", () => {
    let start!: Date;
    let end!: Date;

    it("returns quarters when start is lesser than end", () => {
        const start = new Date(2020, 1, 15);
        const end = new Date(2020, 4, 15);

        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());
        expect(toDateString(period.units)).toStrictEqual([
            "Wed Jan 01 2020",
            "Wed Apr 01 2020",
            "Wed Jul 01 2020",
        ]);
    });

    it("returns quarters when start is in the same quarter than end", () => {
        const start = new Date(2020, 2, 15);
        const end = new Date(2020, 2, 15);

        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());
        expect(toDateString(period.units)).toStrictEqual(["Wed Jan 01 2020", "Wed Apr 01 2020"]);
    });

    it("returns quarters when start is greater than end", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 1, 15);

        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());
        expect(toDateString(period.units)).toStrictEqual([
            "Wed Jan 01 2020",
            "Wed Apr 01 2020",
            "Wed Jul 01 2020",
        ]);
    });

    it("Format a unit", () => {
        start = new Date(2020, 2, 15);
        end = new Date(2020, 7, 15);
        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());

        expect(period.formatShort(new Date(2020, 0, 15))).toBe("Q1");
        expect(period.formatShort(new Date(2020, 1, 15))).toBe("Q1");
        expect(period.formatShort(new Date(2020, 2, 15))).toBe("Q1");
        expect(period.formatShort(new Date(2020, 3, 15))).toBe("Q2");
        expect(period.formatShort(new Date(2020, 4, 15))).toBe("Q2");
        expect(period.formatShort(new Date(2020, 5, 15))).toBe("Q2");
        expect(period.formatShort(new Date(2020, 6, 15))).toBe("Q3");
        expect(period.formatShort(new Date(2020, 7, 15))).toBe("Q3");
        expect(period.formatShort(new Date(2020, 8, 15))).toBe("Q3");
        expect(period.formatShort(new Date(2020, 9, 15))).toBe("Q4");
        expect(period.formatShort(new Date(2020, 10, 15))).toBe("Q4");
        expect(period.formatShort(new Date(2020, 11, 15))).toBe("Q4");

        expect(period.formatLong(new Date(2020, 0, 15))).toBe("Quarter 1 of 2020");
        expect(period.formatLong(new Date(2020, 1, 15))).toBe("Quarter 1 of 2020");
        expect(period.formatLong(new Date(2020, 2, 15))).toBe("Quarter 1 of 2020");
        expect(period.formatLong(new Date(2020, 3, 15))).toBe("Quarter 2 of 2020");
        expect(period.formatLong(new Date(2020, 4, 15))).toBe("Quarter 2 of 2020");
        expect(period.formatLong(new Date(2020, 5, 15))).toBe("Quarter 2 of 2020");
        expect(period.formatLong(new Date(2020, 6, 15))).toBe("Quarter 3 of 2020");
        expect(period.formatLong(new Date(2020, 7, 15))).toBe("Quarter 3 of 2020");
        expect(period.formatLong(new Date(2020, 8, 15))).toBe("Quarter 3 of 2020");
        expect(period.formatLong(new Date(2020, 9, 15))).toBe("Quarter 4 of 2020");
        expect(period.formatLong(new Date(2020, 10, 15))).toBe("Quarter 4 of 2020");
        expect(period.formatLong(new Date(2020, 11, 15))).toBe("Quarter 4 of 2020");
    });

    it.each([[-1], [0]])(
        "Returns empty array for additional units when nb is lesser than 0",
        (nb_missing_quarters) => {
            start = new Date(2020, 2, 15);
            end = new Date(2020, 7, 15);
            const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());

            expect(period.additionalUnits(nb_missing_quarters)).toStrictEqual([]);
        },
    );

    it("Returns an array of additional quarters", () => {
        start = new Date(2020, 2, 15);
        end = new Date(2020, 3, 15);
        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());

        expect(toDateString(period.additionalUnits(3))).toStrictEqual([
            "Thu Oct 01 2020",
            "Fri Jan 01 2021",
            "Thu Apr 01 2021",
        ]);
    });

    it("should return empty string for getEvenOddClass since we don't need special background alternance", () => {
        const start = new Date(2020, 2, 15);
        const end = new Date(2020, 3, 15);
        const period = new TimePeriodQuarter(start, end, createVueGettextProviderPassthrough());

        expect(period.getEvenOddClass()).toBe("");
    });
});
