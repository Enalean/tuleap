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

import * as actions from "./tasks-actions";
import type { ActionContext } from "vuex";
import type { TasksState } from "./type";
import type { RootState } from "../type";
import type { Task } from "../../type";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { SUBTASKS_ARE_LOADED, SUBTASKS_WAITING_TO_BE_LOADED } from "../../type";
import * as TaskRetriever from "../../helpers/task-retriever";

describe("tasks-actions", () => {
    let context: ActionContext<TasksState, RootState>;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            root_state: {} as RootState,
        } as unknown) as ActionContext<TasksState, RootState>;
        jest.clearAllMocks();
    });

    describe("loadTasks", () => {
        it("should display an empty state if there is no tasks", async () => {
            jest.spyOn(TaskRetriever, "retrieveAllTasks").mockResolvedValue([]);

            await actions.loadTasks(context, 123);

            expect(context.commit).toHaveBeenCalledWith("setShouldDisplayEmptyState", true);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });

        it("should display an error state for a 400", async () => {
            const recursive_get = jest.spyOn(TaskRetriever, "retrieveAllTasks");
            mockFetchError(recursive_get, {
                status: 400,
                error_json: {
                    error: {
                        i18n_error_message: "Missing timeframe",
                    },
                },
            });

            await actions.loadTasks(context, 123);

            expect(context.commit).toHaveBeenCalledWith("setShouldDisplayErrorState", true);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "Missing timeframe");
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });

        it.each([[403], [404]])("should display an empty state for a %i", async (status) => {
            const recursive_get = jest.spyOn(TaskRetriever, "retrieveAllTasks");
            mockFetchError(recursive_get, {
                status,
            });

            await actions.loadTasks(context, 123);

            expect(context.commit).toHaveBeenCalledWith("setShouldDisplayEmptyState", true);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });

        it("should display a generic error state for a 500", async () => {
            const recursive_get = jest.spyOn(TaskRetriever, "retrieveAllTasks");
            mockFetchError(recursive_get, {
                status: 500,
                error_json: {
                    error: {
                        message: "Internal Server Error",
                    },
                },
            });

            await actions.loadTasks(context, 123);

            expect(context.commit).toHaveBeenCalledWith("setShouldDisplayErrorState", true);
            expect(context.commit).toHaveBeenCalledWith("setErrorMessage", "");
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });

        it("should store the tasks in the store", async () => {
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

            await actions.loadTasks(context, 123);

            expect(context.commit).toHaveBeenCalledWith("setTasks", [task_1, task_2]);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });
    });

    describe("toggleSubtasks", () => {
        describe("when task is expanded", () => {
            it("should collapse the task", () => {
                const task = { is_expanded: true } as Task;

                actions.toggleSubtasks(context, task);

                expect(context.commit).toHaveBeenCalledWith("collapseTask", task);
            });
        });

        describe("when task is collapsed", () => {
            it("should expand the task", () => {
                const task = {
                    is_expanded: false,
                    subtasks_loading_status: SUBTASKS_ARE_LOADED,
                } as Task;

                actions.toggleSubtasks(context, task);

                expect(context.commit).toHaveBeenCalledWith("expandTask", task);
            });

            it("should load the subtasks if they are waiting to be loaded", async () => {
                const task = {
                    is_expanded: false,
                    subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
                } as Task;

                const subtasks = [{ id: 42 }, { id: 66 }] as Task[];
                jest.spyOn(TaskRetriever, "retrieveAllSubtasks").mockReturnValue(
                    Promise.resolve(subtasks)
                );

                await actions.toggleSubtasks(context, task);

                expect(context.commit).toHaveBeenCalledWith("expandTask", task);
                expect(context.commit).toHaveBeenCalledWith("startLoadingSubtasks", task);
                expect(context.commit).toHaveBeenCalledWith("setSubtasks", {
                    task,
                    subtasks,
                });
                expect(context.commit).toHaveBeenCalledWith("finishLoadingSubtasks", task);
            });
        });
    });
});
