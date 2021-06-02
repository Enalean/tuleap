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
import { SUBTASKS_ARE_LOADED, SUBTASKS_WAITING_TO_BE_LOADED } from "../../type";
import * as TaskRetriever from "../../helpers/task-retriever";

describe("tasks-actions", () => {
    let context: ActionContext<TasksState, RootState>;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            root_state: {} as RootState,
        } as unknown as ActionContext<TasksState, RootState>;
        jest.clearAllMocks();
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
                jest.spyOn(TaskRetriever, "retrieveAllSubtasks").mockResolvedValue(subtasks);

                await actions.toggleSubtasks(context, task);

                expect(context.commit).toHaveBeenCalledWith("expandTask", task);
                expect(context.commit).toHaveBeenCalledWith("startLoadingSubtasks", task);
                expect(context.commit).toHaveBeenCalledWith("setSubtasks", {
                    task,
                    subtasks,
                });
                expect(context.commit).toHaveBeenCalledWith("finishLoadingSubtasks", task);
            });

            it("should marks the sutbasks status as error if retrieval failed", async () => {
                const task = {
                    is_expanded: false,
                    subtasks_loading_status: SUBTASKS_WAITING_TO_BE_LOADED,
                } as Task;

                jest.spyOn(TaskRetriever, "retrieveAllTasks").mockRejectedValue({
                    status: 400,
                    error_json: {
                        error: {
                            i18n_error_message: "Missing timeframe",
                        },
                    },
                });

                await actions.toggleSubtasks(context, task);

                expect(context.commit).toHaveBeenCalledWith("expandTask", task);
                expect(context.commit).toHaveBeenCalledWith("startLoadingSubtasks", task);
                expect(context.commit).toHaveBeenCalledWith("markSubtasksAsError", task);
                expect(context.commit).not.toHaveBeenCalledWith("setSubtasks");
                expect(context.commit).not.toHaveBeenCalledWith("finishLoadingSubtasks", task);
            });
        });
    });
});
