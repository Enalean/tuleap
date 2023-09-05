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

import type { RootState } from "./type";
import type { ActionContext } from "vuex";
import { retrieveIterations } from "../helpers/iterations-retriever";
import { retrieveAllTasks } from "../helpers/task-retriever";
import type { Task } from "../type";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";

export function loadRoadmap(
    context: ActionContext<RootState, RootState>,
    roadmap_id: number,
): void {
    Promise.all([
        retrieveAllTasks(roadmap_id),
        context.state.should_load_lvl1_iterations ? retrieveIterations(roadmap_id, 1) : null,
        context.state.should_load_lvl2_iterations ? retrieveIterations(roadmap_id, 2) : null,
    ])
        .then((values) => {
            const tasks: Task[] = values[0];
            if (tasks.length === 0) {
                context.commit("setApplicationInEmptyState");
            } else {
                context.commit("tasks/setTasks", tasks, { root: true });
            }

            const lvl1_iterations = values[1];
            if (lvl1_iterations !== null && lvl1_iterations.length > 0) {
                context.commit("iterations/setLvl1Iterations", lvl1_iterations, { root: true });
            }

            const lvl2_iterations = values[2];
            if (lvl2_iterations !== null && lvl2_iterations.length > 0) {
                context.commit("iterations/setLvl2Iterations", lvl2_iterations, { root: true });
            }

            context.commit("stopLoading");
        })
        .catch((rest_error: FetchWrapperError) => {
            if (rest_error.response.status === 404 || rest_error.response.status === 403) {
                context.commit("setApplicationInEmptyState");

                return;
            }

            context.commit("setApplicationInErrorStateDueToRestError", rest_error);
        });
}
