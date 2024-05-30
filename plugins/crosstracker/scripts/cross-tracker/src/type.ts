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
    invalid_trackers: ReadonlyArray<InvalidTracker>;
    reading_mode: boolean;
    is_report_saved: boolean;
    is_user_admin: boolean;
    report_id: number;
};

export type SelectedTracker = {
    readonly tracker_id: number;
};

export type TrackerAndProject = {
    readonly project: Pick<ProjectInfo, "id" | "label">;
    readonly tracker: TrackerInfo;
};

export type TrackerToUpdate = {
    readonly tracker_id: number;
    readonly tracker_label: string;
    readonly project_label: string;
};

export type Report = {
    readonly trackers: Map<number, TrackerForInit>;
    readonly expert_query: string;
    readonly invalid_trackers: ReadonlyArray<InvalidTracker>;
};

export type ArtifactsCollection = {
    readonly artifacts: ReadonlyArray<Artifact>;
    readonly total: string;
};

export type Artifact = {
    readonly id: number;
    readonly title: string;
    readonly badge: {
        readonly uri: string;
        readonly cross_ref: string;
        readonly color: string;
    };
    formatted_last_update_date: string;
    readonly last_update_date: string;
    readonly status: string;
    readonly submitted_by: User;
    readonly assigned_to: ReadonlyArray<User>;
    readonly project: ProjectReference;
};

export type User = {
    readonly id: number;
    readonly display_name: string;
    readonly user_url: string;
};
