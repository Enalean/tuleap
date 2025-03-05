/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type {
    CheckBoxFieldIdentifier,
    ColorName,
    ColumnIdentifier,
    CreatePermission,
    DateFieldIdentifier,
    FieldSetIdentifier,
    LastUpdateDateFieldIdentifier,
    MultiSelectBoxFieldIdentifier,
    PermissionFieldIdentifier,
    RadioButtonFieldIdentifier,
    ReadPermission,
    StringFieldIdentifier,
    SubmissionDateFieldIdentifier,
    UpdatePermission,
} from "@tuleap/plugin-tracker-constants";
import type { ProjectReference } from "@tuleap/core-rest-api-types";

import type { UserGroupRepresentation } from "./artifacts";
import type { OpenListFieldStructure } from "./open-list-field";
import type { ListFieldStructure } from "./list-field";
import type { ArtifactLinkFieldStructure } from "./link-field";

export * from "./open-list-field";
export * from "./list-field";
export * from "./file-field";
export * from "./link-field";

export type PermissionsArray = readonly [ReadPermission, CreatePermission?, UpdatePermission?];

export interface BaseFieldStructure {
    readonly field_id: number;
    readonly name: string;
    readonly required: boolean;
    readonly label: string;
}

export interface UnknownFieldStructure extends BaseFieldStructure {
    readonly type: never;
}

interface StringFieldStructure extends BaseFieldStructure {
    readonly type: StringFieldIdentifier;
}

export interface CommonDateFieldStructure extends BaseFieldStructure {
    readonly is_time_displayed: boolean;
}

export interface ReadonlyDateFieldStructure extends CommonDateFieldStructure {
    readonly type: LastUpdateDateFieldIdentifier | SubmissionDateFieldIdentifier;
}

export interface EditableDateFieldStructure extends CommonDateFieldStructure {
    readonly type: DateFieldIdentifier;
    readonly permissions: PermissionsArray;
}

export type DateFieldStructure = ReadonlyDateFieldStructure | EditableDateFieldStructure;

export interface ContainerFieldStructure extends BaseFieldStructure {
    readonly type: ColumnIdentifier | FieldSetIdentifier;
    readonly label: string;
}

export interface ListLikeFieldStructure extends BaseFieldStructure {
    readonly type:
        | RadioButtonFieldIdentifier
        | MultiSelectBoxFieldIdentifier
        | CheckBoxFieldIdentifier;
}

export interface PermissionsOnArtifactFieldStructure extends BaseFieldStructure {
    readonly type: PermissionFieldIdentifier;
    readonly values: {
        readonly is_used_by_default: boolean;
        readonly ugroup_representations: ReadonlyArray<UserGroupRepresentation>;
    };
}

export type StructureFields =
    | UnknownFieldStructure
    | ArtifactLinkFieldStructure
    | ContainerFieldStructure
    | DateFieldStructure
    | ListFieldStructure
    | ListLikeFieldStructure
    | OpenListFieldStructure
    | PermissionsOnArtifactFieldStructure
    | StringFieldStructure;

export interface StructureFormat {
    readonly id: number;
    readonly content: null | ReadonlyArray<this>;
}

export type TrackerProjectRepresentation = ProjectReference;

export type SemanticsRepresentation = {
    readonly title: {
        readonly field_id: number;
    };
};

interface NotificationsRepresentation {
    readonly enabled: boolean;
}

interface ListDependencyRule {
    readonly source_field_id: number;
    readonly source_value_id: number;
    readonly target_field_id: number;
    readonly target_value_id: number;
}

interface WorkflowTransition {
    readonly id: number;
    readonly from_id: number;
    readonly to_id: number;
}

interface WorkflowRepresentation {
    readonly field_id: number;
    readonly is_advanced: boolean;
    readonly is_legacy: boolean;
    readonly is_used: "1" | "";
    readonly rules: {
        readonly dates: ReadonlyArray<unknown>;
        readonly lists: ReadonlyArray<ListDependencyRule>;
    };
    readonly transitions: ReadonlyArray<WorkflowTransition>;
}

export interface MinimalTrackerResponse {
    readonly id: number;
    readonly label: string;
}

export interface TrackerResponseWithColor extends MinimalTrackerResponse {
    readonly color_name: ColorName;
}

export interface TrackerResponseWithCannotCreateReason extends TrackerResponseWithColor {
    readonly cannot_create_reasons: ReadonlyArray<string>;
}

export interface TrackerResponseWithProject extends MinimalTrackerResponse {
    readonly uri: string;
    readonly project: TrackerProjectRepresentation;
}

export interface TrackerReference extends MinimalTrackerResponse {
    readonly color: ColorName;
    readonly uri: string;
    readonly project: TrackerProjectRepresentation;
}

export type TrackerWithProjectAndColor = TrackerResponseWithProject & TrackerResponseWithColor;

/**
 * Do not use this type directly as it contains way too many things.
 * Instead, create your own type with Pick:
 * `type Subset = Pick<TrackerResponseNoInstance, "id" | "label" | "fields">;`
 */
export interface TrackerResponseNoInstance extends TrackerWithProjectAndColor {
    readonly _pick_what_you_need: never;
    readonly item_name: string;
    readonly fields: ReadonlyArray<StructureFields>;
    readonly structure: ReadonlyArray<StructureFormat>;
    readonly semantics: SemanticsRepresentation;
    readonly workflow: WorkflowRepresentation;
    readonly notifications: NotificationsRepresentation;
    readonly parent: TrackerReference | null;
}
