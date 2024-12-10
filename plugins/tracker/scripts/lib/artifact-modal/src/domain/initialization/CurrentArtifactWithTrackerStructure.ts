/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { ColorName } from "@tuleap/core-constants";
import type { BindValueId } from "../fields/select-box-field/BindValueId";

export type FieldDependenciesRule = {
    readonly source_field_id: number;
    readonly source_value_id: BindValueId;
    readonly target_field_id: number;
    readonly target_value_id: BindValueId;
};

interface Workflow {
    readonly rules: {
        readonly lists: ReadonlyArray<FieldDependenciesRule>;
    };
}

interface TrackerProject {
    readonly id: number;
}

interface ParentTracker {
    readonly id: number;
}

export interface TrackerStructure {
    readonly id: number;
    readonly item_name: string;
    readonly color_name: ColorName;
    readonly project: TrackerProject;
    readonly parent: ParentTracker | null;
    readonly workflow: Workflow;
    readonly fields: ReadonlyArray<unknown>;
    readonly are_mentions_effective: boolean;
}

export type ETagValue = string | null;
export type LastModifiedTimestamp = string | null;

export interface CurrentArtifactWithTrackerStructure {
    readonly id: number;
    readonly title: string | null;
    readonly tracker: TrackerStructure;
    readonly values: ReadonlyArray<unknown>;
    readonly etag: ETagValue;
    readonly last_modified: LastModifiedTimestamp;
}
