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

export type RootActions = {
    loadTrackerList(context: Context, project_id: number): Promise<void>;
    loadProjectList(context: Context): Promise<void>;
    moveDryRun(context: Context, artifact_id: number): Promise<void>;
    move(context: Context, artifact_id: number): Promise<void>;
};

export function loadTrackerList(context: Context, project_id: number): Promise<void> {
    context.commit("loadingTrackersAfterProjectSelected", project_id);

    return getTrackerList(project_id)
        .match(
            (tracker_list) => {
                context.commit("saveTrackers", tracker_list);
            },
            (fault) => {
                context.commit("setErrorMessage", fault);
            }
        )
        .finally(() => context.commit("resetTrackersLoading"));
}

export function loadProjectList(context: Context): Promise<void> {
    return getProjectList()
        .match(
            (project_list) => {
                context.commit("saveProjects", project_list);
            },
            (fault) => {
                context.commit("setErrorMessage", fault);
            }
        )
        .finally(() => context.commit("resetProjectLoading"));
}

export function moveDryRun(context: Context, artifact_id: number): Promise<void> {
    const selected_tracker_id = context.state.selected_tracker_id;
    if (!selected_tracker_id) {
        return Promise.reject("Expected a tracker to be selected before calling MoveDryRun");
    }

    context.commit("switchToProcessingMove");

    return moveDryRunArtifact(artifact_id, selected_tracker_id)
        .match(
            (result) => {
                const { fields_migrated, fields_partially_migrated, fields_not_migrated } =
                    result.dry_run.fields;

                if (fields_partially_migrated.length === 0 && fields_not_migrated.length === 0) {
                    move(context, artifact_id);

                    return;
                }

                if (fields_migrated.length === 0 && fields_partially_migrated.length === 0) {
                    context.commit("blockArtifactMove");
                }

                context.commit("hasProcessedDryRun", result.dry_run.fields);
            },
            (fault) => {
                context.commit("setErrorMessage", fault);
            }
        )
        .finally(() => {
            context.commit("resetProcessingMove");
        });
}

export function move(context: Context, artifact_id: number): Promise<void> {
    const selected_tracker_id = context.state.selected_tracker_id;
    if (!selected_tracker_id) {
        return Promise.reject("Expected a tracker to be selected before calling move");
    }

    context.commit("switchToProcessingMove");

    return moveArtifact(artifact_id, selected_tracker_id).match(
        () => {
            redirectTo(`/plugins/tracker/?aid=${artifact_id}`);
        },
        (fault) => {
            context.commit("setErrorMessage", fault);
        }
    );
}
