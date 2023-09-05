/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import {
    addTime as addTimeQuerrier,
    deleteTime as deleteTimeQuerrier,
    getTrackedTimes,
    updateTime as updateTimeQuerrier,
} from "../api/rest-querier.js";
import {
    ERROR_OCCURRED,
    REST_FEEDBACK_ADD,
    REST_FEEDBACK_EDIT,
    REST_FEEDBACK_DELETE,
} from "../../../constants.js";
import { updateEvent } from "../../../TimetrackingEvents.js";

export function setDatesAndReload(context, [start_date, end_date]) {
    context.commit("setParametersForNewQuery", [start_date, end_date]);
    return loadFirstBatchOfTimes(context);
}

export async function getTimes(context) {
    try {
        context.commit("resetErrorMessage");
        const { times, total } = await getTrackedTimes(
            context.state.user_id,
            context.state.start_date,
            context.state.end_date,
            context.state.pagination_limit,
            context.state.pagination_offset,
        );
        return context.commit("loadAChunkOfTimes", [times, total]);
    } catch (error) {
        return showErrorMessage(context, error);
    }
}

export async function addTime(context, [date, artifact, time_value, step]) {
    try {
        const response = await addTimeQuerrier(date, artifact, time_value, step);
        context.commit("pushCurrentTimes", [[response], REST_FEEDBACK_ADD]);
        updateEvent();
        return loadFirstBatchOfTimes(context);
    } catch (rest_error) {
        return showRestError(context, rest_error);
    }
}

export async function updateTime(context, [date, time_id, time_value, step]) {
    try {
        const response = await updateTimeQuerrier(date, time_id, time_value, step);
        context.commit("replaceInCurrentTimes", [response, REST_FEEDBACK_EDIT]);
        updateEvent();
        return loadFirstBatchOfTimes(context);
    } catch (rest_error) {
        return showRestError(context, rest_error);
    }
}

export async function deleteTime(context, time_id) {
    try {
        await deleteTimeQuerrier(time_id);
        context.commit("deleteInCurrentTimes", [time_id, REST_FEEDBACK_DELETE]);
        updateEvent();
        return loadFirstBatchOfTimes(context);
    } catch (rest_error) {
        return showRestError(context, rest_error);
    }
}

export async function loadFirstBatchOfTimes(context) {
    context.commit("setIsLoading", true);
    await getTimes(context);
    context.commit("setIsLoading", false);
}

export async function reloadTimes(context) {
    context.commit("resetTimes");
    await getTimes(context).finally(function () {
        context.commit("setIsLoading", false);
    });
}

async function showErrorMessage(context, rest_error) {
    try {
        const { error } = await rest_error.response.json();
        context.commit("setErrorMessage", error.code + " " + error.message);
    } catch (error) {
        context.commit("setErrorMessage", ERROR_OCCURRED);
    }
}

async function showRestError(context, rest_error) {
    try {
        const { error } = await rest_error.response.json();
        return context.commit("setRestFeedback", [error.code + " " + error.message, "danger"]);
    } catch (error) {
        return context.commit("setRestFeedback", [ERROR_OCCURRED, "danger"]);
    }
}
