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

import { getNatureLabelsForTasks } from "./natures-labels-for-tasks";
import type { Task } from "../type";
import { NaturesLabels, TasksByNature, TasksDependencies } from "../type";

describe("getNatureLabelsForTasks", () => {
    it("should returns empty collection if no tasks", () => {
        const labels = getNatureLabelsForTasks(
            [] as Task[],
            new TasksDependencies(),
            new NaturesLabels([
                ["", "Linked to"],
                ["depends_on", "Depends on"],
            ]),
        );

        expect(labels.size).toBe(0);
    });

    it("should returns empty collection if no dependencies", () => {
        const task_1 = { id: 1 } as Task;
        const task_2 = { id: 2 } as Task;

        const labels = getNatureLabelsForTasks(
            [task_1, task_2],
            new TasksDependencies(),
            new NaturesLabels([
                ["", "Linked to"],
                ["depends_on", "Depends on"],
            ]),
        );

        expect(labels.size).toBe(0);
    });

    it("should returns empty collection if dependency nature is not visible", () => {
        const task_1 = { id: 1 } as Task;
        const task_2 = { id: 2 } as Task;

        const labels = getNatureLabelsForTasks(
            [task_1, task_2],
            new TasksDependencies([[task_1, new TasksByNature([["hidden_one", [task_2]]])]]),
            new NaturesLabels([
                ["", "Linked to"],
                ["depends_on", "Depends on"],
            ]),
        );

        expect(labels.size).toBe(0);
    });

    it("should returns label if there is a dependency with this nature and it is visible", () => {
        const task_1 = { id: 1 } as Task;
        const task_2 = { id: 2 } as Task;

        const labels = getNatureLabelsForTasks(
            [task_1, task_2],
            new TasksDependencies([[task_1, new TasksByNature([["depends_on", [task_2]]])]]),
            new NaturesLabels([
                ["", "Linked to"],
                ["depends_on", "Depends on"],
            ]),
        );

        expect(labels.get("depends_on")).toBe("Depends on");
    });
});
