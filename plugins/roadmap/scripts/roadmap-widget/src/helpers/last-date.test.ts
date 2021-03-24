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

import { getLastDate } from "./last-date";
import type { Task } from "../type";

describe("last-date", () => {
    it("Returns null if there isn't any tasks", () => {
        expect(getLastDate([])).toBeNull();
    });

    it("Returns null if the task doesn't have start nor end dates", () => {
        const start = null;
        const end = null;
        expect(getLastDate([{ start, end } as Task])).toBeNull();
    });

    it("Returns the start date if end date is null", () => {
        const start = new Date(2020, 3, 15);
        const end = null;
        expect(getLastDate([{ start, end } as Task])).toBe(start);
    });

    it("Returns the start date even if end date is lesser than start", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 10);
        expect(getLastDate([{ start, end } as Task])).toBe(start);
    });

    it("Returns the end date if start date is null", () => {
        const start = null;
        const end = new Date(2020, 3, 15);
        expect(getLastDate([{ start, end } as Task])).toBe(end);
    });

    it("Returns the end date", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        expect(getLastDate([{ start, end } as Task])).toBe(end);
    });

    it("Returns the end date of the first task if the other has no dates", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = null;
        const other_end = null;
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(end);
    });

    it("Returns the end date of the first task if the other has no start date and end date lesser than end", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = null;
        const other_end = new Date(2020, 3, 18);
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(end);
    });

    it("Returns the end date of the other task if the other has no start date and end date greater than end", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = null;
        const other_end = new Date(2020, 3, 25);
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(other_end);
    });

    it("Returns the end date of the first task if the other has no end date and start date lesser than end", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 18);
        const other_end = null;
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(end);
    });

    it("Returns the start date of the other task if the other has no end date and start date greater than end", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 25);
        const other_end = null;
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(other_start);
    });

    it("Returns the end date of the first task if the other has end date lesser than end", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 10);
        const other_end = new Date(2020, 3, 18);
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(end);
    });

    it("Returns the end date of the other task if the other has end date greater than end", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 10);
        const other_end = new Date(2020, 3, 25);
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(other_end);
    });

    it("Returns the end date of the first task if the other has start date lesser than end even if end date is even lesser", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 18);
        const other_end = new Date(2020, 3, 5);
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(end);
    });

    it("Returns the start date of the other task if the other has start date greater than end even if end date is lesser", () => {
        const start = new Date(2020, 3, 15);
        const end = new Date(2020, 3, 20);
        const other_start = new Date(2020, 3, 25);
        const other_end = new Date(2020, 3, 5);
        expect(
            getLastDate([
                { start, end },
                { start: other_start, end: other_end },
            ] as Task[])
        ).toBe(other_start);
    });
});
