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
    getProjectList,
    getTrackerList,
    moveDryRunArtifact,
    moveArtifact,
} from "../api/rest-querier.js";
import { redirectTo } from "../window-helper.js";

export async function loadTrackerList(context, project_id) {
    try {
        context.commit("loadingTrackersAfterProjectSelected", project_id);
        const tracker_list = await getTrackerList(context.state.selected_project_id);
        context.commit("saveTrackers", tracker_list);
    } catch (e) {
        return handleError(context, e);
    } finally {
        context.commit("resetTrackersLoading");
    }
}

export async function loadProjectList(context) {
    try {
        const project_list = await getProjectList();
        context.commit("saveProjects", project_list);
    } catch (e) {
        return handleError(context, e);
    } finally {
        context.commit("resetProjectLoading");
    }
}

export async function moveDryRun(context, artifact_id) {
    context.commit("switchToProcessingMove");

    try {
        const response = await moveDryRunArtifact(
            artifact_id,
            context.state.selected_tracker.tracker_id
        );
        const result = await response.json();

        const { fields_partially_migrated, fields_not_migrated } = result.dry_run.fields;

        if (fields_partially_migrated.length === 0 && fields_not_migrated.length === 0) {
            return await move(context, artifact_id);
        }

        context.commit("hasProcessedDryRun", result.dry_run.fields);
    } catch (e) {
        return handleError(context, e);
    } finally {
        context.commit("resetProcessingMove");
    }
}

export async function move(context, artifact_id) {
    context.commit("switchToProcessingMove");

    try {
        await moveArtifact(artifact_id, context.state.selected_tracker.tracker_id);
        redirectTo("/plugins/tracker/?aid=" + artifact_id);
    } catch (e) {
        context.commit("resetProcessingMove");
        return handleError(context, e);
    }
}

async function handleError(context, e) {
    const { error } = await e.response.json();
    context.commit("setErrorMessage", error.message);
}
