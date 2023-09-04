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

import type { RootState } from "../type";
import type { TasksState } from "./type";
import type { ActionContext } from "vuex";
import { retrieveAllSubtasks } from "../../helpers/task-retriever";
import type { Task } from "../../type";
import { SUBTASKS_WAITING_TO_BE_LOADED } from "../../type";

export function toggleSubtasks(
    context: ActionContext<TasksState, RootState>,
    task: Task,
): Promise<void> {
    if (task.is_expanded) {
        context.commit("collapseTask", task);
        return Promise.resolve();
    }

    context.commit("expandTask", task);
    if (task.subtasks_loading_status === SUBTASKS_WAITING_TO_BE_LOADED) {
        return loadSubtasks(context, task);
    }

    return Promise.resolve();
}

function loadSubtasks(context: ActionContext<TasksState, RootState>, task: Task): Promise<void> {
    context.commit("startLoadingSubtasks", task);

    return retrieveAllSubtasks(task)
        .then((subtasks) => {
            if (subtasks.length > 0) {
                context.commit("setSubtasks", { task, subtasks });
                context.commit("finishLoadingSubtasks", task);
            } else {
                context.commit("markSubtasksAsEmpty", task);
            }
        })
        .catch(() => {
            context.commit("markSubtasksAsError", task);
        });
}
