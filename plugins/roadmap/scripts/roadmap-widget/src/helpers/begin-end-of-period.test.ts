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

import { getBeginningOfPeriod, getEndOfPeriod } from "./begin-end-of-period";

describe("begin-end-of-period", () => {
    let start: Date | null;
    let end: Date | null;
    let now: Date;

    it("Returns now if no start and no end", () => {
        start = null;
        end = null;
        now = new Date(2020, 3, 15);

        expect(getBeginningOfPeriod(start, end, now)).toBe(now);
        expect(getEndOfPeriod(start, end, now)).toBe(now);
    });

    describe("when there is no start", () => {
        beforeEach(() => {
            start = null;
        });

        it("now < end", () => {
            now = new Date(2020, 2, 15);
            end = new Date(2020, 3, 15);

            expect(getBeginningOfPeriod(start, end, now)).toBe(now);
            expect(getEndOfPeriod(start, end, now)).toBe(end);
        });

        it("now > end", () => {
            now = new Date(2020, 3, 15);
            end = new Date(2020, 2, 15);

            expect(getBeginningOfPeriod(start, end, now)).toBe(end);
            expect(getEndOfPeriod(start, end, now)).toBe(now);
        });
    });

    describe("when there is no end", () => {
        beforeEach(() => {
            end = null;
        });

        it("now < start", () => {
            now = new Date(2020, 2, 15);
            start = new Date(2020, 3, 15);

            expect(getBeginningOfPeriod(start, end, now)).toBe(now);
            expect(getEndOfPeriod(start, end, now)).toBe(start);
        });

        it("start < now", () => {
            now = new Date(2020, 3, 15);
            start = new Date(2020, 2, 15);

            expect(getBeginningOfPeriod(start, end, now)).toBe(start);
            expect(getEndOfPeriod(start, end, now)).toBe(now);
        });
    });

    describe("when start is lesser than end", () => {
        beforeEach(() => {
            start = new Date(2020, 3, 15);
            end = new Date(2020, 4, 15);
        });

        it("nom < start", () => {
            now = new Date(2020, 1, 15);

            expect(getBeginningOfPeriod(start, end, now)).toBe(now);
            expect(getEndOfPeriod(start, end, now)).toBe(end);
        });

        it("start < now < end", () => {
            now = new Date(2020, 4, 1);

            expect(getBeginningOfPeriod(start, end, now)).toBe(start);
            expect(getEndOfPeriod(start, end, now)).toBe(end);
        });

        it("end < now", () => {
            now = new Date(2020, 6, 15);

            expect(getBeginningOfPeriod(start, end, now)).toBe(start);
            expect(getEndOfPeriod(start, end, now)).toBe(now);
        });
    });

    describe("when start is greater than end", () => {
        beforeEach(() => {
            start = new Date(2020, 3, 15);
            end = new Date(2020, 2, 15);
        });

        it("nom < end", () => {
            now = new Date(2020, 1, 15);

            expect(getBeginningOfPeriod(start, end, now)).toBe(now);
            expect(getEndOfPeriod(start, end, now)).toBe(start);
        });

        it("end < now < start", () => {
            now = new Date(2020, 3, 1);

            expect(getBeginningOfPeriod(start, end, now)).toBe(end);
            expect(getEndOfPeriod(start, end, now)).toBe(start);
        });

        it("start < now", () => {
            now = new Date(2020, 6, 15);

            expect(getBeginningOfPeriod(start, end, now)).toBe(end);
            expect(getEndOfPeriod(start, end, now)).toBe(now);
        });
    });
});
