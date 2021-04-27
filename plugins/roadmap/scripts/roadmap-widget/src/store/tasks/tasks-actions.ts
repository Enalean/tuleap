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
import type { FetchWrapperError } from "@tuleap/tlp-fetch";
import { retrieveAllTasks } from "../../helpers/task-retriever";

export async function loadTasks(
    context: ActionContext<TasksState, RootState>,
    roadmap_id: number
): Promise<void> {
    try {
        const tasks = await retrieveAllTasks(roadmap_id);
        if (tasks.length === 0) {
            context.commit("setShouldDisplayEmptyState", true);
        } else {
            context.commit("setTasks", tasks);
        }
    } catch (e) {
        if (isFetchWrapperError(e)) {
            await handleRestError(context, e);
        } else {
            throw e;
        }
    } finally {
        context.commit("setIsLoading", false);
    }
}

async function handleRestError(
    context: ActionContext<TasksState, RootState>,
    rest_error: FetchWrapperError
): Promise<void> {
    context.commit("setShouldDisplayErrorState", true);
    context.commit("setErrorMessage", "");

    if (rest_error.response.status === 404 || rest_error.response.status === 403) {
        context.commit("setShouldDisplayEmptyState", true);
        context.commit("setShouldDisplayErrorState", false);

        return;
    }

    if (rest_error.response.status === 400) {
        try {
            context.commit("setErrorMessage", await getMessageFromRestError(rest_error));
        } catch (error) {
            // no custom message if we are unable to parse the error response
            throw rest_error;
        }
    }
}

async function getMessageFromRestError(rest_error: FetchWrapperError): Promise<string> {
    const response = await rest_error.response.json();

    if (Object.prototype.hasOwnProperty.call(response, "error")) {
        if (Object.prototype.hasOwnProperty.call(response.error, "i18n_error_message")) {
            return response.error.i18n_error_message;
        }

        return response.error.message;
    }

    return "";
}

function isFetchWrapperError(error: Error): error is FetchWrapperError {
    return "response" in error;
}
