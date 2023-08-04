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
} from "../api/rest-querier";
import { redirectTo } from "../window-helper";

import type { Context } from "./types";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

export type RootActions = {
    loadTrackerList(context: Context, project_id: number): Promise<void>;
    loadProjectList(context: Context): Promise<void>;
    moveDryRun(context: Context, artifact_id: number): Promise<void>;
    move(context: Context, artifact_id: number): Promise<void>;
};

export async function loadTrackerList(context: Context, project_id: number): Promise<void> {
    try {
        context.commit("loadingTrackersAfterProjectSelected", project_id);
        const tracker_list = await getTrackerList(project_id);
        context.commit("saveTrackers", tracker_list);
    } catch (e: FetchWrapperError | unknown) {
        return handleError(context, e);
    } finally {
        context.commit("resetTrackersLoading");
    }
}

export async function loadProjectList(context: Context): Promise<void> {
    try {
        const project_list = await getProjectList();
        context.commit("saveProjects", project_list);
    } catch (e: FetchWrapperError | unknown) {
        return handleError(context, e);
    } finally {
        context.commit("resetProjectLoading");
    }
}

export async function moveDryRun(context: Context, artifact_id: number): Promise<void> {
    const selected_tracker_id = context.state.selected_tracker_id;
    if (!selected_tracker_id) {
        return;
    }

    context.commit("switchToProcessingMove");

    try {
        const response = await moveDryRunArtifact(artifact_id, selected_tracker_id);
        const result = await response.json();

        const { fields_migrated, fields_partially_migrated, fields_not_migrated } =
            result.dry_run.fields;

        if (fields_partially_migrated.length === 0 && fields_not_migrated.length === 0) {
            return await move(context, artifact_id);
        }

        if (fields_migrated.length === 0 && fields_partially_migrated.length === 0) {
            context.commit("blockArtifactMove");
        }

        context.commit("hasProcessedDryRun", result.dry_run.fields);
    } catch (e) {
        return handleError(context, e);
    } finally {
        context.commit("resetProcessingMove");
    }
}

export async function move(context: Context, artifact_id: number): Promise<void> {
    const selected_tracker_id = context.state.selected_tracker_id;
    if (!selected_tracker_id) {
        return;
    }

    context.commit("switchToProcessingMove");

    try {
        await moveArtifact(artifact_id, selected_tracker_id);
        redirectTo("/plugins/tracker/?aid=" + artifact_id);
    } catch (e: FetchWrapperError | unknown) {
        context.commit("resetProcessingMove");
        return handleError(context, e);
    }
}

async function handleError(context: Context, e: FetchWrapperError | unknown): Promise<void> {
    if (!(e instanceof FetchWrapperError)) {
        context.commit("setErrorMessage", "Unknown error");
        return;
    }

    const { error } = await e.response.json();
    context.commit("setErrorMessage", error.message);
}
