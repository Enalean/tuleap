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
    ColumnIdentifier,
    DateFieldIdentifier,
    FieldSetIdentifier,
    LastUpdateDateFieldIdentifier,
    MultiSelectBoxFieldIdentifier,
    OpenListFieldIdentifier,
    PermissionFieldIdentifier,
    RadioButtonFieldIdentifier,
    SelectBoxFieldIdentifier,
    SubmissionDateFieldIdentifier,
} from "@tuleap/plugin-tracker-constants";

import type { UserGroupRepresentation } from "./artifacts";

export interface BaseFieldStructure {
    readonly field_id: number;
}

interface UnknownFieldStructure extends BaseFieldStructure {
    readonly type: never;
}

interface DateFieldStructure extends BaseFieldStructure {
    readonly type:
        | DateFieldIdentifier
        | LastUpdateDateFieldIdentifier
        | SubmissionDateFieldIdentifier;
    readonly is_time_displayed: boolean;
}

interface ContainerFieldStructure extends BaseFieldStructure {
    readonly type: ColumnIdentifier | FieldSetIdentifier;
    readonly label: string;
}

interface ListFieldStructure extends BaseFieldStructure {
    readonly type:
        | SelectBoxFieldIdentifier
        | RadioButtonFieldIdentifier
        | MultiSelectBoxFieldIdentifier
        | CheckBoxFieldIdentifier
        | OpenListFieldIdentifier;
}

interface PermissionsOnArtifactFieldStructure extends BaseFieldStructure {
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
    | ListFieldStructure
    | PermissionsOnArtifactFieldStructure
    | ArtifactLinkFieldStructure;

export interface StructureFormat {
    readonly id: number;
    readonly content: null | ReadonlyArray<this>;
}

export interface MinimalTrackerResponse {
    readonly id: number;
    readonly label: string;
}

export interface TrackerResponse extends MinimalTrackerResponse {
    readonly fields: ReadonlyArray<StructureFields>;
    readonly structure: ReadonlyArray<StructureFormat>;
}

export interface TrackerUsedArtifactLinkResponse {
    readonly shortname: string;
    readonly forward_label: string;
}
