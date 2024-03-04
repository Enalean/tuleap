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

import { TimePeriodMonth } from "./time-period-month";
import { DateTime, Settings } from "luxon";

function toDateString(collection: DateTime[]): string[] {
    return collection.map((date) => date.toJSDate().toDateString());
}

Settings.defaultZone = "UTC";

describe("TimePeriodMonth", () => {
    it("returns months when start is lesser than end", () => {
        const start = DateTime.fromObject({ year: 2020, month: 2, day: 15 });
        const end = DateTime.fromObject({ year: 2020, month: 4, day: 15 });
        const period = new TimePeriodMonth(start, end, "en-US");

        expect(toDateString(period.units)).toStrictEqual([
            "Sat Feb 01 2020",
            "Sun Mar 01 2020",
            "Wed Apr 01 2020",
            "Fri May 01 2020",
        ]);
    });

    it("returns months when start is in the same month than end", () => {
        const start = DateTime.fromObject({ year: 2020, month: 4, day: 10 });
        const end = DateTime.fromObject({ year: 2020, month: 4, day: 15 });
        const period = new TimePeriodMonth(start, end, "en-US");
        expect(toDateString(period.units)).toStrictEqual(["Wed Apr 01 2020", "Fri May 01 2020"]);
    });

    it("returns months when start is greater than end", () => {
        const start = DateTime.fromObject({ year: 2020, month: 4, day: 15 });
        const end = DateTime.fromObject({ year: 2020, month: 2, day: 15 });
        const period = new TimePeriodMonth(start, end, "en-US");
        expect(toDateString(period.units)).toStrictEqual([
            "Sat Feb 01 2020",
            "Sun Mar 01 2020",
            "Wed Apr 01 2020",
            "Fri May 01 2020",
        ]);
    });

    it("does not mangle months with real user data", () => {
        const period = new TimePeriodMonth(
            DateTime.fromISO("2021-03-31T22:00:00.000Z"),
            DateTime.fromISO("2021-10-30T22:00:00.000Z"),
            "en-US",
        );
        expect(toDateString(period.units)).toStrictEqual([
            "Mon Mar 01 2021",
            "Thu Apr 01 2021",
            "Sat May 01 2021",
            "Tue Jun 01 2021",
            "Thu Jul 01 2021",
            "Sun Aug 01 2021",
            "Wed Sep 01 2021",
            "Fri Oct 01 2021",
            "Mon Nov 01 2021",
        ]);
    });

    it("Builds a dummy period that can be used for skeletons", () => {
        const period = TimePeriodMonth.getDummyTimePeriod(
            DateTime.fromObject({ year: 2020, month: 6, day: 15 }),
        );
        expect(toDateString(period.units)).toStrictEqual(["Mon Jun 01 2020", "Wed Jul 01 2020"]);
    });

    it("Format a unit", () => {
        const start = DateTime.fromObject({ year: 2020, month: 3, day: 15 });
        const end = DateTime.fromObject({ year: 2020, month: 8, day: 15 });
        const period = new TimePeriodMonth(start, end, "en-US");

        const a_date = DateTime.fromObject({ year: 2020, month: 6, day: 15 });
        expect(period.formatShort(a_date)).toBe("Jun");
        expect(period.formatLong(a_date)).toBe("June 2020");
    });

    it.each([[-1], [0]])(
        "Returns empty array for additional units when nb is lesser than 0",
        (nb_missing_months) => {
            const start = DateTime.fromObject({ year: 2020, month: 3, day: 15 });
            const end = DateTime.fromObject({ year: 2020, month: 8, day: 15 });
            const period = new TimePeriodMonth(start, end, "en-US");

            expect(period.additionalUnits(nb_missing_months)).toStrictEqual([]);
        },
    );

    it("Returns an array of additional months", () => {
        const start = DateTime.fromObject({ year: 2020, month: 3, day: 15 });
        const end = DateTime.fromObject({ year: 2020, month: 4, day: 15 });
        const period = new TimePeriodMonth(start, end, "en-US");

        expect(toDateString(period.additionalUnits(3))).toStrictEqual([
            "Mon Jun 01 2020",
            "Wed Jul 01 2020",
            "Sat Aug 01 2020",
        ]);
    });

    it("should return empty string for getEvenOddClass since we don't need special background alternance", () => {
        const start = DateTime.fromObject({ year: 2020, month: 3, day: 15 });
        const end = DateTime.fromObject({ year: 2020, month: 4, day: 15 });
        const period = new TimePeriodMonth(start, end, "en-US");

        expect(period.getEvenOddClass()).toBe("");
    });
});
