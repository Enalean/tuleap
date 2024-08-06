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

import type { TrackerResponseWithProject } from "@tuleap/plugin-tracker-rest-api-types";
import type { Artifact } from "../type";
import type { ColorName } from "@tuleap/core-constants";

export type TrackerReference = Pick<TrackerResponseWithProject, "id" | "label" | "project">;

export type ReportRepresentation = {
    readonly trackers: ReadonlyArray<TrackerReference>;
    readonly expert_query: string;
    readonly invalid_trackers: ReadonlyArray<TrackerReference>;
};

export type ReportContentRepresentation = {
    readonly artifacts: ReadonlyArray<Artifact>;
};

export const DATE_SELECTABLE_TYPE = "date";
export const NUMERIC_SELECTABLE_TYPE = "numeric";
export const TEXT_SELECTABLE_TYPE = "text";
export const PROJECT_SELECTABLE_TYPE = "project";
export const TRACKER_SELECTABLE_TYPE = "tracker";
export const PRETTY_TITLE_SELECTABLE_TYPE = "pretty_title";
export const ARTIFACT_SELECTABLE_TYPE = "artifact";

type UnsupportedSelectableRepresentation = Record<string, unknown>;

export type DateSelectableRepresentation = {
    readonly value: string | null;
    readonly with_time: boolean;
};

export type NumericSelectableRepresentation = {
    readonly value: number | null;
};

export type TextSelectableRepresentation = {
    readonly value: string;
};

export type ProjectSelectableRepresentation = {
    readonly name: string;
    readonly icon: string;
};

export type TrackerSelectableRepresentation = {
    readonly name: string;
    readonly color: ColorName;
};

export type PrettyTitleSelectableRepresentation = {
    readonly tracker_name: string;
    readonly color: ColorName;
    readonly artifact_id: number;
    readonly title: string;
};

export type ArtifactSelectableRepresentation = {
    readonly uri: string;
};

export type SelectableRepresentation =
    | DateSelectableRepresentation
    | NumericSelectableRepresentation
    | TextSelectableRepresentation
    | ProjectSelectableRepresentation
    | TrackerSelectableRepresentation
    | PrettyTitleSelectableRepresentation
    | ArtifactSelectableRepresentation
    | UnsupportedSelectableRepresentation;

export type ArtifactRepresentation = Record<string, SelectableRepresentation>;

interface BaseSelectable {
    readonly name: string;
}

interface UnsupportedSelectable extends BaseSelectable {
    readonly type: string;
}

interface DateSelectable extends BaseSelectable {
    readonly type: typeof DATE_SELECTABLE_TYPE;
}

interface NumericSelectable extends BaseSelectable {
    readonly type: typeof NUMERIC_SELECTABLE_TYPE;
}

interface TextSelectable extends BaseSelectable {
    readonly type: typeof TEXT_SELECTABLE_TYPE;
}

interface ProjectSelectable extends BaseSelectable {
    readonly type: typeof PROJECT_SELECTABLE_TYPE;
}

interface TrackerSelectable extends BaseSelectable {
    readonly type: typeof TRACKER_SELECTABLE_TYPE;
}

interface PrettyTitleSelectable extends BaseSelectable {
    readonly type: typeof PRETTY_TITLE_SELECTABLE_TYPE;
}

export interface ArtifactSelectable extends BaseSelectable {
    readonly type: typeof ARTIFACT_SELECTABLE_TYPE;
}

export type Selectable =
    | DateSelectable
    | NumericSelectable
    | TextSelectable
    | ProjectSelectable
    | TrackerSelectable
    | PrettyTitleSelectable
    | ArtifactSelectable
    | UnsupportedSelectable;

export type SelectableReportContentRepresentation = {
    readonly artifacts: ReadonlyArray<ArtifactRepresentation>;
    readonly selected: ReadonlyArray<Selectable>;
};
