/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { ActionContext } from "vuex";

export type Context = ActionContext<RootState, RootState>;

export type DryRunStateType = "fully-migrated" | "partially-migrated" | "not-migrated";
export const TYPE_FULLY_MIGRATED: DryRunStateType = "fully-migrated";
export const TYPE_PARTIALLY_MIGRATED: DryRunStateType = "partially-migrated";
export const TYPE_NOT_MIGRATED: DryRunStateType = "not-migrated";

export type ArtifactField = {
    readonly field_id: number;
    readonly label: string;
    readonly name: string;
};

export type DryRunState = {
    readonly fields_not_migrated: ArtifactField[];
    readonly fields_partially_migrated: ArtifactField[];
    readonly fields_migrated: ArtifactField[];
};

export type Tracker = {
    readonly id: number;
    readonly label: string;
};

export type Project = {
    readonly id: number;
    readonly label: string;
};

export type RootState = {
    dry_run_fields: DryRunState;
    is_loading_initial: boolean;
    is_processing_move: boolean;
    is_move_possible: boolean;
    are_trackers_loading: boolean;
    has_processed_dry_run: boolean;
    error_message: string;
    projects: Project[];
    trackers: Tracker[];
    selected_tracker_id: number | null;
    selected_project_id: number | null;
};
