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

function toDateString(collection: Date[]): string[] {
    return collection.map((date) => date.toDateString());
}

describe("months", () => {
    let start: Date | null;
    let end: Date | null;
    let now: Date;

    it("Returns current month if no start and no end", () => {
        start = null;
        end = null;
        now = new Date(2020, 3, 15);

        expect(toDateString(getMonths(start, end, now))).toStrictEqual([
            "Wed Apr 01 2020",
            "Fri May 01 2020",
        ]);
    });

    describe("when there is no start", () => {
        beforeEach(() => {
            start = null;
        });

        it("returns months between now and end", () => {
            now = new Date(2020, 2, 15);
            end = new Date(2020, 3, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
            ]);
        });

        it("returns months between end and now", () => {
            now = new Date(2020, 3, 15);
            end = new Date(2020, 2, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
            ]);
        });

        it("returns one month when end and now are in the same month", () => {
            now = new Date(2020, 2, 15);
            end = new Date(2020, 2, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
            ]);
        });
    });

    describe("when there is no end", () => {
        beforeEach(() => {
            end = null;
        });

        it("returns months between now and start", () => {
            now = new Date(2020, 2, 15);
            start = new Date(2020, 3, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
            ]);
        });

        it("returns months between start and now", () => {
            now = new Date(2020, 3, 15);
            start = new Date(2020, 2, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
            ]);
        });

        it("returns one month when start and now are in the same month", () => {
            now = new Date(2020, 2, 15);
            start = new Date(2020, 2, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
            ]);
        });
    });

    describe("when start is lesser than end", () => {
        beforeEach(() => {
            start = new Date(2020, 2, 15);
            end = new Date(2020, 3, 15);
        });

        it("returns months when now is lesser than start", () => {
            now = new Date(2020, 1, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sat Feb 01 2020",
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
            ]);
        });

        it("returns months when now is greater than end", () => {
            now = new Date(2020, 5, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
                "Mon Jun 01 2020",
                "Wed Jul 01 2020",
            ]);
        });
    });

    describe("when start is in the same month than end", () => {
        beforeEach(() => {
            start = new Date(2020, 2, 15);
            end = new Date(2020, 2, 15);
        });

        it("returns months when now is lesser than start", () => {
            now = new Date(2020, 1, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sat Feb 01 2020",
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
            ]);
        });

        it("returns months when now is greater than end", () => {
            now = new Date(2020, 5, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
                "Mon Jun 01 2020",
                "Wed Jul 01 2020",
            ]);
        });
    });

    describe("when start is greater than end", () => {
        beforeEach(() => {
            start = new Date(2020, 3, 15);
            end = new Date(2020, 2, 15);
        });

        it("returns months when now is lesser than start", () => {
            now = new Date(2020, 1, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sat Feb 01 2020",
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
            ]);
        });

        it("returns months when now is greater than end", () => {
            now = new Date(2020, 5, 15);

            expect(toDateString(getMonths(start, end, now))).toStrictEqual([
                "Sun Mar 01 2020",
                "Wed Apr 01 2020",
                "Fri May 01 2020",
                "Mon Jun 01 2020",
                "Wed Jul 01 2020",
            ]);
        });
    });
});
