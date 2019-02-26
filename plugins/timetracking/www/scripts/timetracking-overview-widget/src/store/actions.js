/*
 * Copyright Enalean (c) 2019. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
    getProjectsWithTimetracking,
    getTimesFromReport,
    getTrackersFromReport,
    getTrackersWithTimetracking,
    getTimes,
    saveNewReport
} from "../api/rest-querier.js";
import { ERROR_OCCURRED } from "../../../constants.js";

export async function initWidgetWithReport(context) {
    try {
        context.commit("resetMessages");

        const report = await getTrackersFromReport(context.state.report_id);
        context.commit("setSelectedTrackers", report.trackers);

        return await loadTimes(context);
    } catch (error) {
        return showRestError(context, error);
    }
}

export async function getProjects(context) {
    try {
        context.commit("resetMessages");
        const projects = await getProjectsWithTimetracking();
        return context.commit("setProjects", projects);
    } catch (error) {
        return showRestError(context, error);
    }
}

export async function saveReport(context, message) {
    try {
        context.commit("resetMessages");
        context.commit("setTrackersIds");
        const report = await saveNewReport(
            context.state.report_id,
            context.state.trackers_ids ? context.state.trackers_ids : []
        );
        context.commit("setSelectedTrackers", report.trackers);
        context.commit("setSuccessMessage", message);

        context.commit("setIsReportSave", true);

        return await loadTimes(context);
    } catch (error) {
        return showRestError(context, error);
    }
}

export async function getTrackers(context, project_id) {
    try {
        context.commit("resetMessages");
        const trackers = await getTrackersWithTimetracking(project_id);
        return context.commit("setTrackers", trackers);
    } catch (error) {
        return showRestError(context, error);
    }
}

async function showRestError(context, rest_error) {
    try {
        const { error } = await rest_error.response.json();
        context.commit("setErrorMessage", error.code + " " + error.message);
    } catch (error) {
        context.commit("setErrorMessage", ERROR_OCCURRED);
    }
}

export async function loadTimes(context) {
    context.commit("setIsLoading", true);

    const times = await getTimesFromReport(
        context.state.report_id,
        context.state.start_date,
        context.state.end_date
    );

    context.commit("setTrackersTimes", times);
    context.commit("setIsLoading", false);
}

export async function loadTimesWithNewParameters(context) {
    context.commit("setIsLoading", true);
    context.commit("setTrackersIds");

    const times = await getTimes(
        context.state.report_id,
        context.state.trackers_ids,
        context.state.start_date,
        context.state.end_date
    );

    context.commit("toggleReadingMode");
    context.commit("setIsReportSave", false);
    context.commit("setTrackersTimes", times);
    context.commit("setIsLoading", false);
}
