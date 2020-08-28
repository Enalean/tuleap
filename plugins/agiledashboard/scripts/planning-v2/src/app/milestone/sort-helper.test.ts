/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { sortByStartDateDescending } from "./sort-helper";

describe(`sort-helper`, () => {
    describe(`sortByStartDateDescending`, () => {
        it(`when both start dates are null, it will return 0`, () => {
            const A = { start_date: null };
            const B = { start_date: null };

            expect(sortByStartDateDescending(A, B)).toBe(0);
        });

        it(`when both start dates are the same string, it will return 0`, () => {
            const A = { start_date: "2016-12-04T01:00:00+01:00" };
            const B = { start_date: "2016-12-04T01:00:00+01:00" };

            expect(sortByStartDateDescending(A, B)).toBe(0);
        });

        it(`when item A's start date is null, it will return -1`, () => {
            const A = { start_date: null };
            const B = { start_date: "2016-12-04T01:00:00+01:00" };

            expect(sortByStartDateDescending(A, B)).toBe(-1);
        });

        it(`when item B's start date is null, it will return 1`, () => {
            const A = { start_date: "2016-12-04T01:00:00+01:00" };
            const B = { start_date: null };

            expect(sortByStartDateDescending(A, B)).toBe(1);
        });

        it(`when item A's start date is > (as a string) to item B's start date, it will return -1`, () => {
            const A = { start_date: "2016-12-05T01:00:00+01:00" };
            const B = { start_date: "2016-12-04T01:00:00+01:00" };

            expect(sortByStartDateDescending(A, B)).toBe(-1);
        });

        it(`when item A's start date is < (as a string) to item B's start date, it will return 1`, () => {
            const A = { start_date: "2016-12-04T01:00:00+01:00" };
            const B = { start_date: "2017-01-01T01:00:00+01:00" };

            expect(sortByStartDateDescending(A, B)).toBe(1);
        });
    });
});
