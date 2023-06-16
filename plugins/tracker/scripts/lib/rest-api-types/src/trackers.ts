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
    ArtifactLinkFieldIdentifier,
    CheckBoxFieldIdentifier,
    ColorName,
    ColumnIdentifier,
    DateFieldIdentifier,
    FieldSetIdentifier,
    LastUpdateDateFieldIdentifier,
    MultiSelectBoxFieldIdentifier,
    PermissionFieldIdentifier,
    RadioButtonFieldIdentifier,
    SubmissionDateFieldIdentifier,
    Permission,
} from "@tuleap/plugin-tracker-constants";
import type { ProjectReference } from "@tuleap/core-rest-api-types";

import type { UserGroupRepresentation } from "./artifacts";
import type { OpenListFieldStructure } from "./open-list-field";
import type { ListFieldStructure } from "./list-field";

export * from "./open-list-field";
export * from "./list-field";
export * from "./file-field";

export interface BaseFieldStructure {
    readonly field_id: number;
    readonly name: string;
}

export interface UnknownFieldStructure extends BaseFieldStructure {
    readonly type: never;
}

export interface CommonDateFieldStructure extends BaseFieldStructure {
    readonly is_time_displayed: boolean;
}

export interface ReadonlyDateFieldStructure extends CommonDateFieldStructure {
    readonly type: LastUpdateDateFieldIdentifier | SubmissionDateFieldIdentifier;
}

export interface EditableDateFieldStructure extends CommonDateFieldStructure {
    readonly type: DateFieldIdentifier;
    readonly label: string;
    readonly permissions: ReadonlyArray<Permission>;
    readonly required: boolean;
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

export interface AllowedLinkTypeRepresentation {
    readonly shortname: string;
    readonly forward_label: string;
    readonly reverse_label: string;
}

export interface ArtifactLinkFieldStructure extends BaseFieldStructure {
    readonly type: ArtifactLinkFieldIdentifier;
    readonly label: string;
    readonly allowed_types: ReadonlyArray<AllowedLinkTypeRepresentation>;
}

export type StructureFields =
    | UnknownFieldStructure
    | DateFieldStructure
    | ContainerFieldStructure
    | ListLikeFieldStructure
    | ListFieldStructure
    | OpenListFieldStructure
    | PermissionsOnArtifactFieldStructure
    | ArtifactLinkFieldStructure;

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

/**
 * Do not use this type directly as it contains way too many things.
 * Instead, create your own type with Pick:
 * `type Subset = Pick<TrackerResponseNoInstance, "id" | "label" | "fields">;`
 */
export interface TrackerResponseNoInstance extends TrackerResponseWithColor {
    readonly _pick_what_you_need: never;
    readonly item_name: string;
    readonly fields: ReadonlyArray<StructureFields>;
    readonly structure: ReadonlyArray<StructureFormat>;
    readonly project: TrackerProjectRepresentation;
    readonly semantics: SemanticsRepresentation;
}

export interface TrackerUsedArtifactLinkResponse {
    readonly shortname: string;
    readonly forward_label: string;
}
