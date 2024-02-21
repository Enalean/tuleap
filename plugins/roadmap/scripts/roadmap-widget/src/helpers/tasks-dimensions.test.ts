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

import { getDimensions, getDimensionsMap } from "./tasks-dimensions";
import { TimePeriodMonth } from "./time-period-month";
import type { Task, TaskDimension } from "../type";
import { Styles } from "./styles";
import { TaskDimensionMap } from "../type";
import { DateTime } from "luxon";

describe("tasks-dimensions", () => {
    describe("getDimensionsMap", () => {
        it("Returns milestone dimensions, not positioned, for task without start nor end", () => {
            const task = { start: null, end: null } as Task;
            const time_period = new TimePeriodMonth(
                DateTime.fromJSDate(new Date(2020, 3, 10)),
                DateTime.fromJSDate(new Date(2020, 3, 20)),
                "en-US",
            );

            expect(
                getDimensionsMap([{ task, is_shown: true }], time_period).get(task),
            ).toStrictEqual({
                index: 0,
                left: 0,
                width: Styles.MILESTONE_WIDTH_IN_PX,
            });
        });

        it("Returns milestone dimensions, positioned at start date", () => {
            const task = { start: DateTime.fromJSDate(new Date(2020, 3, 20)), end: null } as Task;
            const time_period = new TimePeriodMonth(
                DateTime.fromJSDate(new Date(2020, 3, 10)),
                DateTime.fromJSDate(new Date(2020, 3, 20)),
                "en-US",
            );

            expect(
                getDimensionsMap([{ task, is_shown: true }], time_period).get(task),
            ).toStrictEqual({
                index: 0,
                left: 63,
                width: Styles.MILESTONE_WIDTH_IN_PX,
            });
        });

        it("Returns milestone dimensions, positioned at end date", () => {
            const task = { start: null, end: DateTime.fromJSDate(new Date(2020, 3, 20)) } as Task;
            const time_period = new TimePeriodMonth(
                DateTime.fromJSDate(new Date(2020, 3, 10)),
                DateTime.fromJSDate(new Date(2020, 3, 20)),
                "en-US",
            );

            expect(
                getDimensionsMap([{ task, is_shown: true }], time_period).get(task),
            ).toStrictEqual({
                index: 0,
                left: 63,
                width: Styles.MILESTONE_WIDTH_IN_PX,
            });
        });

        it("Returns milestone dimensions, positioned at start date, when start == end", () => {
            const task = {
                start: DateTime.fromJSDate(new Date(2020, 3, 20)),
                end: DateTime.fromJSDate(new Date(2020, 3, 20)),
            } as Task;
            const time_period = new TimePeriodMonth(
                DateTime.fromJSDate(new Date(2020, 3, 10)),
                DateTime.fromJSDate(new Date(2020, 3, 20)),
                "en-US",
            );

            expect(
                getDimensionsMap([{ task, is_shown: true }], time_period).get(task),
            ).toStrictEqual({
                index: 0,
                left: 63,
                width: Styles.MILESTONE_WIDTH_IN_PX,
            });
        });

        it("Returns task dimensions, positioned at start date and ending at end date + 1 so that the end date is included in the bar", () => {
            const task = {
                start: DateTime.fromJSDate(new Date("2020-03-10T11:00:00.000Z")),
                end: DateTime.fromJSDate(new Date("2020-03-20T11:00:00.000Z")),
            } as Task;
            const time_period = new TimePeriodMonth(
                DateTime.fromJSDate(new Date("2020-03-10T00:00:00.000Z")),
                DateTime.fromJSDate(new Date("2020-03-30T00:00:00.000Z")),
                "en-US",
            );

            expect(
                getDimensionsMap([{ task, is_shown: true }], time_period).get(task),
            ).toStrictEqual({
                index: 0,
                left: 31,
                width: 35,
            });
        });

        it("Enusures that task width has a minimum width", () => {
            const task = {
                start: DateTime.fromJSDate(new Date(2020, 3, 10)),
                end: DateTime.fromJSDate(new Date(2020, 3, 11)),
            } as Task;
            const time_period = new TimePeriodMonth(
                DateTime.fromJSDate(new Date(2020, 3, 10)),
                DateTime.fromJSDate(new Date(2020, 3, 20)),
                "en-US",
            );

            expect(
                getDimensionsMap([{ task, is_shown: true }], time_period).get(task),
            ).toStrictEqual({
                index: 0,
                left: 30,
                width: Styles.TASK_BAR_MIN_WIDTH_IN_PX,
            });
        });

        it("Returns the index of the task, so that we can know if a task is before or after another one", () => {
            const task_1 = {
                start: DateTime.fromJSDate(new Date(2020, 3, 10)),
                end: DateTime.fromJSDate(new Date(2020, 3, 11)),
            } as Task;
            const task_2 = {
                start: DateTime.fromJSDate(new Date(2020, 3, 10)),
                end: DateTime.fromJSDate(new Date(2020, 3, 11)),
            } as Task;
            const subtask = {
                start: DateTime.fromJSDate(new Date(2020, 3, 10)),
                end: DateTime.fromJSDate(new Date(2020, 3, 11)),
            } as Task;
            const time_period = new TimePeriodMonth(
                DateTime.fromJSDate(new Date(2020, 3, 10)),
                DateTime.fromJSDate(new Date(2020, 3, 20)),
                "en-US",
            );

            const dimensions_map = getDimensionsMap(
                [
                    { task: task_1, is_shown: true },
                    { for_task: task_1, is_skeleton: true, is_last_one: false, is_shown: true },
                    { for_task: task_1, is_skeleton: true, is_last_one: true, is_shown: true },
                    { task: task_2, is_shown: true },
                    { parent: task_2, subtask, is_last_one: true, is_shown: true },
                ],
                time_period,
            );
            const dimensions_task_1 = getDimensions(task_1, dimensions_map);
            const dimensions_task_2 = getDimensions(task_2, dimensions_map);
            const dimensions_subtask = getDimensions(subtask, dimensions_map);

            expect(dimensions_task_1.index).toBe(0);
            expect(dimensions_task_2.index).toBe(3);
            expect(dimensions_subtask.index).toBe(4);
        });
    });

    describe("getDimensions", function () {
        it("Returns a TaskDimension of a task that is stored in the map", () => {
            const task = { id: 1 } as Task;
            const dimension: TaskDimension = { index: 0, left: 0, width: 10 };
            const map = new TaskDimensionMap([[task, dimension]]);

            expect(getDimensions(task, map)).toStrictEqual(dimension);
        });

        it("Throws an error if task is not part of the map", () => {
            const task = { id: 1 } as Task;
            const map = new TaskDimensionMap();

            expect(() => getDimensions(task, map)).toThrow();
        });
    });
});
