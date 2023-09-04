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

import { getFirstDate } from "./first-date";
import type { Iteration, Task } from "../type";

describe("first-date", () => {
    it("Returns now if there isn't any tasks", () => {
        const now = new Date(2020, 3, 30);
        expect(getFirstDate([], now)).toBe(now);
    });

    it("Returns now if the task doesn't have start nor end dates", () => {
        const now = new Date(2020, 3, 30);
        const start = null;
        const end = null;
        expect(getFirstDate([{ start, end } as Task], now)).toBe(now);
    });

    it("Returns the start date if end date is null and start < now", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = null;
        expect(getFirstDate([{ start, end } as Task], now)).toBe(start);
    });

    it("Returns now if end date is null and now < start", () => {
        const now = new Date(2020, 3, 3);
        const start = new Date(2020, 3, 15);
        const end = null;
        expect(getFirstDate([{ start, end } as Task], now)).toBe(now);
    });

    it("Returns the start date if end date is lesser than start", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 10);
        expect(getFirstDate([{ start, end } as Task], now)).toBe(start);
    });

    it("Returns now if end date is lesser than start and now < start", () => {
        const now = new Date(2020, 3, 3);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 10);
        expect(getFirstDate([{ start, end } as Task], now)).toBe(now);
    });

    it("Returns the end date if start date is null and end < now", () => {
        const now = new Date(2020, 3, 30);
        const start = null;
        const end = new Date(2020, 3, 15);
        expect(getFirstDate([{ start, end } as Task], now)).toBe(end);
    });

    it("Returns now if start date is null and now < end", () => {
        const now = new Date(2020, 3, 3);
        const start = null;
        const end = new Date(2020, 3, 15);
        expect(getFirstDate([{ start, end } as Task], now)).toBe(now);
    });

    it("Returns the start date of the first task if the other has no dates", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = null;
        const other_end = null;
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(start);
    });

    it("Returns the start date of the first task if the other has no start date and end date greater than start", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = null;
        const other_end = new Date(2020, 3, 25);
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(start);
    });

    it("Returns the end date of the other task if the other has no start date and end date lesser than start", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = null;
        const other_end = new Date(2020, 3, 10);
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(other_end);
    });

    it("Returns the start date of the first task if the other has no end date and start date greater than start", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 25);
        const other_end = null;
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(start);
    });

    it("Returns the start date of the other task if the other has no end date and start date lesser than start", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 10);
        const other_end = null;
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(other_start);
    });

    it("Returns the start date of the first task if the other has start date greater than start", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 25);
        const other_end = new Date(2020, 3, 25);
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(start);
    });

    it("Returns the start date of the other task if the other has start date lesser than start", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 10);
        const other_end = new Date(2020, 3, 25);
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(other_start);
    });

    it("Returns the start date of the first task if the other has start date greater than start even if end date is lesser", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 25);
        const other_end = new Date(2020, 3, 5);
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(start);
    });

    it("Returns the end date of the other task if the other has start date lesser than start even if end date is lesser", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 10);
        const other_end = new Date(2020, 3, 5);
        expect(
            getFirstDate(
                [
                    { start, end },
                    { start: other_start, end: other_end },
                ] as Task[],
                now,
            ),
        ).toBe(other_start);
    });

    it("Accepts both tasks and iterations", () => {
        const now = new Date(2020, 3, 30);
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const iteration_start = new Date(2020, 3, 10);
        const iteration_end = new Date(2020, 3, 5);
        expect(
            getFirstDate(
                [
                    { start, end } as Task,
                    { start: iteration_start, end: iteration_end } as Iteration,
                ],
                now,
            ),
        ).toBe(iteration_start);
    });
});
