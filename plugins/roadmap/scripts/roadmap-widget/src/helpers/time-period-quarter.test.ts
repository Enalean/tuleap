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
    let start: Date | null;
    let end: Date | null;
    let now: Date;

    it("Returns current quarter if no start and no end", () => {
        start = null;
        end = null;
        now = new Date(2020, 3, 15);

        const period = new TimePeriodQuarter(
            start,
            end,
            now,
            createVueGettextProviderPassthrough()
        );
        expect(toDateString(period.units)).toStrictEqual(["Wed Apr 01 2020", "Wed Jul 01 2020"]);
    });

    describe("when there is no start", () => {
        beforeEach(() => {
            start = null;
        });

        it("returns quarters between now and end", () => {
            now = new Date(2020, 2, 15);
            end = new Date(2020, 3, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
            ]);
        });

        it("returns quarters between end and now", () => {
            now = new Date(2020, 3, 15);
            end = new Date(2020, 2, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
            ]);
        });

        it("returns one quarter when end and now are in the same quarter", () => {
            now = new Date(2020, 2, 15);
            end = new Date(2020, 2, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
            ]);
        });
    });

    describe("when there is no end", () => {
        beforeEach(() => {
            end = null;
        });

        it("returns quarters between now and start", () => {
            now = new Date(2020, 2, 15);
            start = new Date(2020, 3, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
            ]);
        });

        it("returns quarters between start and now", () => {
            now = new Date(2020, 3, 15);
            start = new Date(2020, 2, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
            ]);
        });

        it("returns one quarter when start and now are in the same quarter", () => {
            now = new Date(2020, 2, 15);
            start = new Date(2020, 2, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
            ]);
        });
    });

    describe("when start is lesser than end", () => {
        beforeEach(() => {
            start = new Date(2020, 3, 15);
            end = new Date(2020, 4, 15);
        });

        it("returns quarters when now is lesser than start", () => {
            now = new Date(2020, 1, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
            ]);
        });

        it("returns quarters when now is greater than end", () => {
            now = new Date(2020, 6, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
                "Thu Oct 01 2020",
            ]);
        });
    });

    describe("when start is in the same quarter than end", () => {
        beforeEach(() => {
            start = new Date(2020, 2, 15);
            end = new Date(2020, 2, 15);
        });

        it("returns quarters when now is lesser than start", () => {
            now = new Date(2020, 1, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
            ]);
        });

        it("returns quarters when now is greater than end", () => {
            now = new Date(2020, 5, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
            ]);
        });
    });

    describe("when start is greater than end", () => {
        beforeEach(() => {
            start = new Date(2020, 3, 15);
            end = new Date(2020, 2, 15);
        });

        it("returns quarters when now is lesser than start", () => {
            now = new Date(2020, 1, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
            ]);
        });

        it("returns quarters when now is greater than end", () => {
            now = new Date(2020, 6, 15);

            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );
            expect(toDateString(period.units)).toStrictEqual([
                "Wed Jan 01 2020",
                "Wed Apr 01 2020",
                "Wed Jul 01 2020",
                "Thu Oct 01 2020",
            ]);
        });
    });

    it("Format a unit", () => {
        start = new Date(2020, 2, 15);
        end = new Date(2020, 7, 15);
        now = new Date(2020, 5, 15);
        const period = new TimePeriodQuarter(
            start,
            end,
            now,
            createVueGettextProviderPassthrough()
        );

        expect(period.formatShort(new Date(2020, 0, 15))).toStrictEqual("Q1");
        expect(period.formatShort(new Date(2020, 1, 15))).toStrictEqual("Q1");
        expect(period.formatShort(new Date(2020, 2, 15))).toStrictEqual("Q1");
        expect(period.formatShort(new Date(2020, 3, 15))).toStrictEqual("Q2");
        expect(period.formatShort(new Date(2020, 4, 15))).toStrictEqual("Q2");
        expect(period.formatShort(new Date(2020, 5, 15))).toStrictEqual("Q2");
        expect(period.formatShort(new Date(2020, 6, 15))).toStrictEqual("Q3");
        expect(period.formatShort(new Date(2020, 7, 15))).toStrictEqual("Q3");
        expect(period.formatShort(new Date(2020, 8, 15))).toStrictEqual("Q3");
        expect(period.formatShort(new Date(2020, 9, 15))).toStrictEqual("Q4");
        expect(period.formatShort(new Date(2020, 10, 15))).toStrictEqual("Q4");
        expect(period.formatShort(new Date(2020, 11, 15))).toStrictEqual("Q4");

        expect(period.formatLong(new Date(2020, 0, 15))).toStrictEqual("Quarter 1 of 2020");
        expect(period.formatLong(new Date(2020, 1, 15))).toStrictEqual("Quarter 1 of 2020");
        expect(period.formatLong(new Date(2020, 2, 15))).toStrictEqual("Quarter 1 of 2020");
        expect(period.formatLong(new Date(2020, 3, 15))).toStrictEqual("Quarter 2 of 2020");
        expect(period.formatLong(new Date(2020, 4, 15))).toStrictEqual("Quarter 2 of 2020");
        expect(period.formatLong(new Date(2020, 5, 15))).toStrictEqual("Quarter 2 of 2020");
        expect(period.formatLong(new Date(2020, 6, 15))).toStrictEqual("Quarter 3 of 2020");
        expect(period.formatLong(new Date(2020, 7, 15))).toStrictEqual("Quarter 3 of 2020");
        expect(period.formatLong(new Date(2020, 8, 15))).toStrictEqual("Quarter 3 of 2020");
        expect(period.formatLong(new Date(2020, 9, 15))).toStrictEqual("Quarter 4 of 2020");
        expect(period.formatLong(new Date(2020, 10, 15))).toStrictEqual("Quarter 4 of 2020");
        expect(period.formatLong(new Date(2020, 11, 15))).toStrictEqual("Quarter 4 of 2020");
    });

    it.each([[-1], [0]])(
        "Returns empty array for additional units when nb is lesser than 0",
        (nb_missing_quarters) => {
            start = new Date(2020, 2, 15);
            end = new Date(2020, 7, 15);
            now = new Date(2020, 5, 15);
            const period = new TimePeriodQuarter(
                start,
                end,
                now,
                createVueGettextProviderPassthrough()
            );

            expect(period.additionalUnits(nb_missing_quarters)).toStrictEqual([]);
        }
    );

    it("Returns an array of additional quarters", () => {
        start = new Date(2020, 2, 15);
        end = new Date(2020, 3, 15);
        now = new Date(2020, 2, 15);
        const period = new TimePeriodQuarter(
            start,
            end,
            now,
            createVueGettextProviderPassthrough()
        );

        expect(toDateString(period.additionalUnits(3))).toStrictEqual([
            "Thu Oct 01 2020",
            "Fri Jan 01 2021",
            "Thu Apr 01 2021",
        ]);
    });
});
