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
import { DateTime } from "luxon";

describe("begin-end-of-period", () => {
    describe("getBeginningOfPeriod", () => {
        it("should return start when start < end", () => {
            const start = DateTime.fromJSDate(new Date(2020, 3, 15));
            const end = DateTime.fromJSDate(new Date(2020, 4, 15));

            expect(getBeginningOfPeriod(start, end)).toBe(start);
        });

        it("should return end when end < start", () => {
            const start = DateTime.fromJSDate(new Date(2020, 4, 15));
            const end = DateTime.fromJSDate(new Date(2020, 3, 15));

            expect(getBeginningOfPeriod(start, end)).toBe(end);
        });
    });

    describe("getEndOfPeriod", () => {
        it("should return end when start < end", () => {
            const start = DateTime.fromJSDate(new Date(2020, 3, 15));
            const end = DateTime.fromJSDate(new Date(2020, 4, 15));

            expect(getEndOfPeriod(start, end)).toBe(end);
        });

        it("should return start when end < start", () => {
            const start = DateTime.fromJSDate(new Date(2020, 4, 15));
            const end = DateTime.fromJSDate(new Date(2020, 3, 15));

            expect(getEndOfPeriod(start, end)).toBe(start);
        });
    });
});
