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

import type { Task } from "../type";
import { getTasksDependencies } from "./dependency-map-builder";
import { DateTime } from "luxon";

describe("dependency-map-builder", () => {
    it("Returns an empty map if no dependencies are set", () => {
        const task_1 = { id: 1, dependencies: {} } as Task;
        const task_2 = { id: 2, dependencies: {} } as Task;

        const map = getTasksDependencies([task_1, task_2]);

        expect(map.has(task_1)).toBe(false);
        expect(map.has(task_2)).toBe(false);
    });

    it("Returns a map of dependencies", () => {
        const task_1 = { id: 1, dependencies: {} } as Task;
        const task_2 = { id: 2, dependencies: { depends_on: [3, 4] } } as unknown as Task;
        const task_3 = { id: 3, dependencies: { "": [1, 5] } } as unknown as Task;
        const task_4 = {
            id: 4,
            dependencies: { "": [1], depends_on: [2, 3] },
        } as unknown as Task;

        const map = getTasksDependencies([task_1, task_2, task_3, task_4]);

        expect(map.has(task_1)).toBe(false);
        expect(map.has(task_2)).toBe(true);
        expect(map.get(task_2)?.get("depends_on")).toStrictEqual([task_3, task_4]);
        expect(map.has(task_3)).toBe(true);
        expect(map.get(task_3)?.get("")).toStrictEqual([task_1]);
        expect(map.has(task_4)).toBe(true);
        expect(map.get(task_4)?.get("")).toStrictEqual([task_1]);
        expect(map.get(task_4)?.get("depends_on")).toStrictEqual([task_2, task_3]);
    });

    it("Removes dependencies with end date < start date", () => {
        const task_1 = { id: 1, dependencies: { "": [2, 3] } } as unknown as Task;
        const task_2 = {
            id: 2,
            dependencies: {},
            start: DateTime.fromISO("2020-04-14T22:00:00.000Z"),
            end: DateTime.fromISO("2020-04-14T22:00:00.000Z"),
        } as Task;
        const task_3 = {
            id: 3,
            dependencies: {},
            start: DateTime.fromISO("2020-04-14T22:00:00.000Z"),
            end: DateTime.fromISO("2020-04-10T22:00:00.000Z"),
        } as Task;

        const map = getTasksDependencies([task_1, task_2, task_3]);

        expect(map.has(task_1)).toBe(true);
        expect(map.get(task_1)?.get("")).toStrictEqual([task_2]);
    });

    it("Does not compute dependencies for tasks with end date < start date", () => {
        const task_1 = {
            id: 1,
            dependencies: { "": [2, 3] },
            start: DateTime.fromISO("2020-04-14T22:00:00.000Z"),
            end: DateTime.fromISO("2020-04-10T22:00:00.000Z"),
        } as unknown as Task;
        const task_2 = {
            id: 2,
            dependencies: {},
        } as Task;
        const task_3 = {
            id: 3,
            dependencies: {},
        } as Task;

        const map = getTasksDependencies([task_1, task_2, task_3]);

        expect(map.has(task_1)).toBe(false);
    });
});
