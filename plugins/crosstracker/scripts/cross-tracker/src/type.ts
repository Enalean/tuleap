/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type { TrackerResponseNoInstance } from "@tuleap/plugin-tracker-rest-api-types";
import type { TrackerForInit } from "./backend-cross-tracker-report";

export type InvalidTracker = Pick<TrackerResponseNoInstance, "id" | "label" | "project">;
export type TrackerInfo = Pick<TrackerResponseNoInstance, "id" | "label">;
export type ProjectInfo = Pick<ProjectReference, "id" | "uri" | "label">;

export type State = {
    error_message: string | null;
    success_message: string | null;
    invalid_trackers: InvalidTracker[];
    reading_mode: boolean;
    is_report_saved: boolean;
    is_user_admin: boolean;
    report_id: number;
};

export type SelectedTracker = {
    readonly tracker_id: number;
};

export type TrackerAndProject = {
    project: Pick<ProjectInfo, "id" | "label">;
    tracker: TrackerInfo;
};

export type TrackerToUpdate = {
    tracker_id: number;
    tracker_label: string;
    project_label: string;
};

export type Report = {
    trackers: Map<number, TrackerForInit>;
    expert_query: string;
    invalid_trackers: InvalidTracker[];
};

export type ArtifactsCollection = {
    artifacts: Artifact[];
    total: string;
};

export type Artifact = {
    id: number;
    title: string;
    badge: {
        uri: string;
        cross_ref: string;
        color: string;
    };
    formatted_last_update_date: string;
    last_update_date: string;
    status: string;
    submitted_by: User;
    assigned_to: User[];
    project: ProjectReference;
};

export type User = {
    id: number;
    display_name: string;
    user_url: string;
};
