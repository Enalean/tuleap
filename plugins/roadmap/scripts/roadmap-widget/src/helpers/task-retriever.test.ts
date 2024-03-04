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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import { retrieveAllTasks, retrieveAllSubtasks } from "./task-retriever";
import type { Task } from "../type";
import { SUBTASKS_WAITING_TO_BE_LOADED } from "../type";

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
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].id).toBe(6422);
            expect(tasks[0].start?.toJSDate().toDateString()).toBe("Sun Mar 01 2020");
            expect(tasks[0].end?.toJSDate().toDateString()).toBe("Sat Mar 14 2020");
        });

        it("Removes tasks that don't have start and end dates because we don't know how to display them yet", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: null,
                    end: null,
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks).toHaveLength(0);
        });

        it("Keeps tasks with no start and end dates and a time period error message so we can display it", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: null,
                    end: null,
                    time_period_error_message: "The time period is fucked up",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks).toHaveLength(1);
        });

        it("Marks a task as a milestone if it does not have a start date", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: null,
                    end: "2020-03-14T10:00:00+01:00",
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].is_milestone).toBe(true);
        });

        it("Marks a task as a milestone if it does not have an end date", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: null,
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].is_milestone).toBe(true);
        });

        it("Marks a task as a milestone if it start date = end date", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-01T10:00:00+01:00",
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].is_milestone).toBe(true);
        });

        it("Does not mark a task as a milestone if start date < end date", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].is_milestone).toBe(false);
        });

        it("should consider a task without _is_child dependencies as not having any sub tasks", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { depends_on: [124] },
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].has_subtasks).toBe(false);
        });

        it("should consider a task with empty _is_child dependencies not having any sub tasks", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { _is_child: [] },
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].has_subtasks).toBe(false);
        });

        it("should consider a task with filled _is_child dependencies as having sub tasks", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { _is_child: [124] },
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].has_subtasks).toBe(true);
        });

        it("should consider a task with end date < start date as not having sub tasks", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-20T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { _is_child: [124] },
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].has_subtasks).toBe(false);
        });

        it("should init the task as not showing subtasks", async () => {
            jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                {
                    id: 6422,
                    xref: "epic #6422",
                    title: "Do this",
                    html_url: "/plugins/tracker/?aid=6422",
                    color_name: "panther-pink",
                    start: "2020-03-01T10:00:00+01:00",
                    end: "2020-03-14T10:00:00+01:00",
                    dependencies: { _is_child: [124] },
                    time_period_error_message: "",
                },
            ]);

            const tasks = await retrieveTasks();

            expect(tasks[0].subtasks_loading_status).toBe(SUBTASKS_WAITING_TO_BE_LOADED);
            expect(tasks[0].subtasks).toStrictEqual([]);
            expect(tasks[0].is_expanded).toBe(false);
        });

        describe("Sort tasks", () => {
            it("should sort tasks by start date", async () => {
                jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                    {
                        id: 1231,
                        start: "2020-03-02T10:00:00+01:00",
                        end: "2020-03-14T10:00:00+01:00",
                        time_period_error_message: "",
                    },
                    {
                        id: 1232,
                        start: "2020-03-01T10:00:00+01:00",
                        end: "2020-03-14T10:00:00+01:00",
                        time_period_error_message: "",
                    },
                ]);

                const tasks = await retrieveTasks();

                expect(tasks[0].id).toBe(1232);
                expect(tasks[1].id).toBe(1231);
            });

            it("should use the end date if there is no start date (milestone)", async () => {
                jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                    {
                        id: 1231,
                        start: null,
                        end: "2020-03-15T10:00:00+01:00",
                        time_period_error_message: "",
                    },
                    {
                        id: 1232,
                        start: null,
                        end: "2020-03-14T10:00:00+01:00",
                        time_period_error_message: "",
                    },
                ]);

                const tasks = await retrieveTasks();

                expect(tasks[0].id).toBe(1232);
                expect(tasks[1].id).toBe(1231);
            });

            it("should put tasks with end date < start date at the end", async () => {
                jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                    {
                        id: 1231,
                        title: "Do this",
                        start: "2020-03-16T10:00:00+01:00",
                        end: "2020-03-15T10:00:00+01:00",
                        time_period_error_message: "",
                    },
                    {
                        id: 1232,
                        title: "Do that",
                        start: "2020-03-16T10:00:00+01:00",
                        end: "2020-03-14T10:00:00+01:00",
                        time_period_error_message: "",
                    },
                    {
                        id: 1233,
                        title: "Do it",
                        start: "2020-03-14T10:00:00+01:00",
                        end: "2020-03-16T10:00:00+01:00",
                        time_period_error_message: "",
                    },
                ]);

                const tasks = await retrieveTasks();

                expect(tasks[0].id).toBe(1233);
                expect(tasks[1].id).toBe(1232);
                expect(tasks[2].id).toBe(1231);
            });

            it("should put tasks with time period error at the end", async () => {
                jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
                    {
                        id: 1231,
                        title: "Do this",
                        start: null,
                        end: null,
                        time_period_error_message: "This is fucked up",
                    },
                    {
                        id: 1232,
                        title: "Do that",
                        start: "2020-03-16T10:00:00+01:00",
                        end: "2020-03-14T10:00:00+01:00",
                        time_period_error_message: "",
                    },
                    {
                        id: 1233,
                        title: "Do it",
                        start: null,
                        end: null,
                        time_period_error_message: "This is fucked up too",
                    },
                ]);

                const tasks = await retrieveTasks();

                expect(tasks[0].id).toBe(1232);
                expect(tasks[1].id).toBe(1231);
                expect(tasks[2].id).toBe(1233);
            });
        });
    });

    it("retrieveAllSubasks should inject the parent in retrieved subtasks", async () => {
        const task = { subtasks_uri: "uri/1234" } as Task;

        jest.spyOn(tlp_fetch, "recursiveGet").mockResolvedValue([
            {
                id: 6422,
                xref: "epic #6422",
                title: "Do this",
                html_url: "/plugins/tracker/?aid=6422",
                color_name: "panther-pink",
                start: null,
                end: "2020-03-14T10:00:00+01:00",
                time_period_error_message: "",
            },
        ]);

        const subtasks = await retrieveAllSubtasks(task);

        expect(subtasks[0].parent).toBe(task);
    });
});
