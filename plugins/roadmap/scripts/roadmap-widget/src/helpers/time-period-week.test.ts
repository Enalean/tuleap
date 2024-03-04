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

import { TimePeriodWeek } from "./time-period-week";
import { createVueGettextProviderPassthrough } from "./vue-gettext-provider-for-test";
import { DateTime } from "luxon";

function toDateString(collection: DateTime[]): string[] {
    return collection.map((date) => date.toJSDate().toDateString());
}

describe("time-period-week", () => {
    describe("formatting", () => {
        it("should format the time in a long format", () => {
            const period = new TimePeriodWeek(
                DateTime.fromJSDate(new Date("2021-04-07")),
                DateTime.fromJSDate(new Date("2021-04-08")),
                createVueGettextProviderPassthrough(),
            );

            expect(period.formatLong(DateTime.fromJSDate(new Date("2021-04-08")))).toBe(
                "Week 14 of 2021",
            );
        });

        it("should format the time in a short format", () => {
            const period = new TimePeriodWeek(
                DateTime.fromJSDate(new Date("2021-04-07")),
                DateTime.fromJSDate(new Date("2021-04-08")),
                createVueGettextProviderPassthrough(),
            );

            expect(period.formatShort(DateTime.fromJSDate(new Date("2021-04-08")))).toBe("W14");
        });
    });

    describe("generate weeks in period", () => {
        it("should generate a collection containing the start dates of weeks inside the given period", () => {
            const period = new TimePeriodWeek(
                DateTime.fromJSDate(new Date("2021-04-01")),
                DateTime.fromJSDate(new Date("2021-04-30")),
                createVueGettextProviderPassthrough(),
            );

            expect(toDateString(period.units)).toStrictEqual([
                "Mon Mar 29 2021",
                "Mon Apr 05 2021",
                "Mon Apr 12 2021",
                "Mon Apr 19 2021",
                "Mon Apr 26 2021",
                "Mon May 03 2021",
            ]);
        });

        it("should handle properly years transitions", () => {
            const period = new TimePeriodWeek(
                DateTime.fromJSDate(new Date("2021-12-15")),
                DateTime.fromJSDate(new Date("2022-01-15")),
                createVueGettextProviderPassthrough(),
            );

            expect(toDateString(period.units)).toStrictEqual([
                "Mon Dec 13 2021",
                "Mon Dec 20 2021",
                "Mon Dec 27 2021",
                "Mon Jan 03 2022",
                "Mon Jan 10 2022",
                "Mon Jan 17 2022",
            ]);
        });

        it("should generate additional weeks", () => {
            const period = new TimePeriodWeek(
                DateTime.fromJSDate(new Date("2021-12-15")),
                DateTime.fromJSDate(new Date("2022-01-15")),
                createVueGettextProviderPassthrough(),
            );

            const additional_weeks = period.additionalUnits(6);
            expect(toDateString(additional_weeks)).toStrictEqual([
                "Mon Jan 24 2022",
                "Mon Jan 31 2022",
                "Mon Feb 07 2022",
                "Mon Feb 14 2022",
                "Mon Feb 21 2022",
                "Mon Feb 28 2022",
            ]);
        });

        it.each([[-1], [0]])(
            "Returns empty array for additional units when nb is lesser than 0",
            (nb_missing_weeks) => {
                const period = new TimePeriodWeek(
                    DateTime.fromJSDate(new Date("2021-01-01")),
                    DateTime.fromJSDate(new Date("2021-01-31")),
                    createVueGettextProviderPassthrough(),
                );

                expect(period.additionalUnits(nb_missing_weeks)).toStrictEqual([]);
            },
        );
    });

    it("should return even/odd according to the week's month so that background alternance offers a visual grouping of weeks", () => {
        const period = new TimePeriodWeek(
            DateTime.fromJSDate(new Date("2020-01-01")),
            DateTime.fromJSDate(new Date("2021-01-31")),
            createVueGettextProviderPassthrough(),
        );

        expect(period.getEvenOddClass(DateTime.fromJSDate(new Date("2020-01-01")))).toBe("even");
        expect(period.getEvenOddClass(DateTime.fromJSDate(new Date("2020-01-08")))).toBe("even");
        expect(period.getEvenOddClass(DateTime.fromJSDate(new Date("2020-01-15")))).toBe("even");
        expect(period.getEvenOddClass(DateTime.fromJSDate(new Date("2020-01-22")))).toBe("even");
        expect(period.getEvenOddClass(DateTime.fromJSDate(new Date("2020-01-29")))).toBe("even");
        expect(period.getEvenOddClass(DateTime.fromJSDate(new Date("2020-02-05")))).toBe("odd");
        expect(period.getEvenOddClass(DateTime.fromJSDate(new Date("2020-02-13")))).toBe("odd");
    });
});
