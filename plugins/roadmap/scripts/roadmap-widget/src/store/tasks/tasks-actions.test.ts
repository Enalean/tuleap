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
import * as actions from "./tasks-actions";
import type { ActionContext } from "vuex";
import type { TasksState } from "./type";
import type { RootState } from "../type";
import type { Task } from "../../type";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

jest.mock("tlp");

describe("tasks-actions", () => {
    let context: ActionContext<TasksState, RootState>;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            root_state: {} as RootState,
        } as unknown) as ActionContext<TasksState, RootState>;
    });

    describe("loadTasks", () => {
        it("should display an empty state if there is no tasks", async () => {
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([]);

            await actions.loadTasks(context, 123);

            expect(context.commit).toHaveBeenCalledWith("setShouldDisplayEmptyState", true);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });

        it("should display an error state for a 400", async () => {
            const recursive_get = jest.spyOn(tlp, "recursiveGet");
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
            const recursive_get = jest.spyOn(tlp, "recursiveGet");
            mockFetchError(recursive_get, {
                status,
            });

            await actions.loadTasks(context, 123);

            expect(context.commit).toHaveBeenCalledWith("setShouldDisplayEmptyState", true);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });

        it("should display a generic error state for a 500", async () => {
            const recursive_get = jest.spyOn(tlp, "recursiveGet");
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
            jest.spyOn(tlp, "recursiveGet").mockResolvedValue([task_1, task_2]);

            await actions.loadTasks(context, 123);

            expect(context.commit).toHaveBeenCalledWith("setRows", [
                { task: { ...task_1, has_subtasks: false, is_loading_subtasks: false } },
                { task: { ...task_2, has_subtasks: false, is_loading_subtasks: false } },
            ]);
            expect(context.commit).toHaveBeenCalledWith("setIsLoading", false);
        });
    });

    describe("toggleSubtasks", () => {
        describe("when task is not loading subtasks", () => {
            it("informs that we are loading subtasks", () => {
                const task = { is_loading_subtasks: false } as Task;

                actions.toggleSubtasks(context, task);

                expect(context.commit).toHaveBeenCalledWith("activateIsLoadingSubtasks", task);
            });
        });

        describe("when task is already loading subtasks", () => {
            it("informs that we are loading subtasks", () => {
                const task = { is_loading_subtasks: true } as Task;

                actions.toggleSubtasks(context, task);

                expect(context.commit).toHaveBeenCalledWith("deactivateIsLoadingSubtasks", task);
            });
        });
    });
});
