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

import * as tlp from "tlp";
import { retrieveAllTasks, retrieveAllSubtasks } from "./task-retriever";
import type { Task } from "../type";
import { SUBTASKS_WAITING_TO_BE_LOADED } from "../type";

jest.mock("tlp");

describe("task-retriever", () => {
    describe.each([["retrieveAllTasks"], ["retrieveAllSubasks"]])("%s", (method_to_test) => {
        let retrieveTasks: () => Promise<Task[]>;
        beforeEach(() => {
            retrieveTasks =
                method_to_test === "retrieveAllTasks"
                    ? (): Promise<Task[]> => retrieveAllTasks(123)
                    : (): Promise<Task[]> =>
                          retrieveAllSubtasks({ subtasks_uri: "uri/1234" } as Task);
        });

        it("Retrieves tasks and transform their dates from string to Date object", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].id).toBe(6422);
            expect(tasks[0].start?.toDateString()).toBe("Sun Mar 01 2020");
            expect(tasks[0].end?.toDateString()).toBe("Sat Mar 14 2020");
        });

        it("Removes tasks that don't have start and end dates because we don't know how to display them yet", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: null,
                    end: null,
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks.length).toBe(0);
        });

        it("Removes tasks that have end date lesser than start date because we don't know how to display them yet", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2019-03-14T10:00:00+01:00",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks.length).toBe(0);
        });

        it("Marks a task as a milestone if it does not have a start date", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: null,
                    end: "2020-03-14T10:00:00+01:00",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].is_milestone).toBe(true);
        });

        it("Marks a task as a milestone if it does not have an end date", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: null,
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].is_milestone).toBe(true);
        });

        it("Marks a task as a milestone if it start date = end date", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-01T10:00:00+01:00",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].is_milestone).toBe(true);
        });

        it("Does not mark a task as a milestone if start date < end date", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].is_milestone).toBe(false);
        });

        it("should consider a task without _is_child dependencies as not having any sub tasks", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { depends_on: [124] },
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].has_subtasks).toBe(false);
        });

        it("should consider a task with empty _is_child dependencies not having any sub tasks", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { _is_child: [] },
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].has_subtasks).toBe(false);
        });

        it("should consider a task with filled _is_child dependencies as having sub tasks", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { _is_child: [124] },
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].has_subtasks).toBe(true);
        });

        it("should init the task as not showing subtasks", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { _is_child: [124] },
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].subtasks_loading_status).toBe(SUBTASKS_WAITING_TO_BE_LOADED);
            expect(tasks[0].subtasks).toStrictEqual([]);
            expect(tasks[0].is_expanded).toBe(false);
        });

        describe("Sort tasks", () => {
            it("should sort tasks by start date", async () => {
                jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                    {
                        id: 1231,
                        start: "2020-03-02T10:00:00+01:00",
                        end: "2020-03-14T10:00:00+01:00",
                    },
                    {
                        id: 1232,
                        start: "2020-03-01T10:00:00+01:00",
                        end: "2020-03-14T10:00:00+01:00",
                    },
                ]);

                const tasks = await retrieveTasks();

                expect(tasks[0].id).toBe(1232);
                expect(tasks[1].id).toBe(1231);
            });

            it("should use the end date if there is no start date (milestone)", async () => {
                jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
                    {
                        id: 1231,
                        start: null,
                        end: "2020-03-15T10:00:00+01:00",
                    },
                    {
                        id: 1232,
                        start: null,
                        end: "2020-03-14T10:00:00+01:00",
                    },
                ]);

                const tasks = await retrieveTasks();

                expect(tasks[0].id).toBe(1232);
                expect(tasks[1].id).toBe(1231);
            });
        });
    });
});
