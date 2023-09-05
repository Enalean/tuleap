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

import * as actions from "./root-actions";
import type { ActionContext } from "vuex";
import type { RootState } from "./type";
import * as TaskRetriever from "../helpers/task-retriever";
import * as IterationsRetriever from "../helpers/iterations-retriever";
import Vue from "vue";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { Iteration, Task } from "../type";

describe("root-actions", () => {
    let context: ActionContext<RootState, RootState>;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
        } as unknown as ActionContext<RootState, RootState>;
        jest.clearAllMocks();
    });

    describe("loadRoadmap", () => {
        it("should display an empty state if there is no tasks", async () => {
            context.state = {
                ...context.state,
                should_load_lvl1_iterations: false,
                should_load_lvl2_iterations: false,
            };

            jest.spyOn(TaskRetriever, "retrieveAllTasks").mockResolvedValue([]);

            actions.loadRoadmap(context, 42);
            await Vue.nextTick();
            await Vue.nextTick();

            expect(context.commit).toHaveBeenCalledWith("setApplicationInEmptyState");
        });

        it("should load tasks as well as iterations at both levels", async () => {
            context.state = {
                ...context.state,
                should_load_lvl1_iterations: true,
                should_load_lvl2_iterations: true,
            };

            const tasks_retrieval = jest
                .spyOn(TaskRetriever, "retrieveAllTasks")
                .mockResolvedValue([]);
            const iterations_retrieval = jest
                .spyOn(IterationsRetriever, "retrieveIterations")
                .mockResolvedValue([]);

            actions.loadRoadmap(context, 42);
            await Vue.nextTick();
            await Vue.nextTick();

            expect(tasks_retrieval.mock.calls).toHaveLength(1);
            expect(iterations_retrieval.mock.calls).toHaveLength(2);
        });

        it.each([[400], [500]])(
            "should display an error state for a %i while loading tasks",
            async (status) => {
                context.state = {
                    ...context.state,
                    should_load_lvl1_iterations: false,
                    should_load_lvl2_iterations: false,
                };

                const recursive_get = jest.spyOn(TaskRetriever, "retrieveAllTasks");
                mockFetchError(recursive_get, {
                    status,
                    error_json: {
                        error: {
                            i18n_error_message: "Error message",
                        },
                    },
                });

                actions.loadRoadmap(context, 42);
                await Vue.nextTick();
                await Vue.nextTick();

                expect(context.commit).toHaveBeenCalledWith(
                    "setApplicationInErrorStateDueToRestError",
                    expect.anything(),
                );
            },
        );

        it.each([[403], [404]])(
            "should display an empty state for a %i while loading tasks",
            async (status) => {
                context.state = {
                    ...context.state,
                    should_load_lvl1_iterations: false,
                    should_load_lvl2_iterations: false,
                };

                const recursive_get = jest.spyOn(TaskRetriever, "retrieveAllTasks");
                mockFetchError(recursive_get, {
                    status,
                });

                actions.loadRoadmap(context, 42);
                await Vue.nextTick();
                await Vue.nextTick();

                expect(context.commit).toHaveBeenCalledWith("setApplicationInEmptyState");
            },
        );

        it("should store the tasks in the store", async () => {
            context.state = {
                ...context.state,
                should_load_lvl1_iterations: false,
                should_load_lvl2_iterations: false,
            };

            const task_1 = {
                id: 1,
                start: new Date(2020, 3, 15),
                end: null,
                is_milestone: true,
                dependencies: {},
            } as Task;
            const task_2 = {
                id: 2,
                start: new Date(2020, 4, 15),
                end: null,
                is_milestone: true,
                dependencies: {},
            } as Task;
            jest.spyOn(TaskRetriever, "retrieveAllTasks").mockResolvedValue([task_1, task_2]);

            actions.loadRoadmap(context, 42);
            await Vue.nextTick();
            await Vue.nextTick();

            expect(context.commit).toHaveBeenCalledWith("tasks/setTasks", [task_1, task_2], {
                root: true,
            });
        });

        it.each([[1], [2]])(
            "should not store the tasks in the store if load of iterations at level %i failed",
            async (level) => {
                context.state = {
                    ...context.state,
                    should_load_lvl1_iterations: level === 1,
                    should_load_lvl2_iterations: level === 2,
                };

                const task_1 = {
                    id: 1,
                    start: new Date(2020, 3, 15),
                    end: null,
                    is_milestone: true,
                    dependencies: {},
                } as Task;
                const task_2 = {
                    id: 2,
                    start: new Date(2020, 4, 15),
                    end: null,
                    is_milestone: true,
                    dependencies: {},
                } as Task;
                jest.spyOn(TaskRetriever, "retrieveAllTasks").mockResolvedValue([task_1, task_2]);

                const iterations = jest.spyOn(IterationsRetriever, "retrieveIterations");
                mockFetchError(iterations, {
                    status: 500,
                });

                actions.loadRoadmap(context, 42);
                await Vue.nextTick();
                await Vue.nextTick();

                expect(context.commit).not.toHaveBeenCalledWith(
                    "tasks/setTasks",
                    [task_1, task_2],
                    {
                        root: true,
                    },
                );
                expect(context.commit).toHaveBeenCalledWith(
                    "setApplicationInErrorStateDueToRestError",
                    expect.anything(),
                );
            },
        );

        it.each([[1], [2]])("should store the level %i iterations in the store", async (level) => {
            context.state = {
                ...context.state,
                should_load_lvl1_iterations: level === 1,
                should_load_lvl2_iterations: level === 2,
            };

            jest.spyOn(TaskRetriever, "retrieveAllTasks").mockResolvedValue([]);

            const iterations: Iteration[] = [
                {
                    id: 1,
                } as Iteration,
                {
                    id: 2,
                } as Iteration,
            ];

            jest.spyOn(IterationsRetriever, "retrieveIterations").mockResolvedValue(iterations);

            actions.loadRoadmap(context, 42);
            await Vue.nextTick();
            await Vue.nextTick();

            expect(context.commit).toHaveBeenCalledWith(
                `iterations/setLvl${level}Iterations`,
                iterations,
                { root: true },
            );
        });
    });
});
