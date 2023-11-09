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
    ArtifactIdFieldIdentifier,
    ArtifactIdInTrackerFieldIdentifier,
    ArtifactLinkFieldIdentifier,
    CheckBoxFieldIdentifier,
    ColorName,
    ComputedFieldIdentifier,
    CrossReferenceFieldIdentifier,
    DateFieldIdentifier,
    FileFieldIdentifier,
    FloatFieldIdentifier,
    HTMLFormat,
    IntFieldIdentifier,
    LastUpdateByFieldIdentifier,
    LastUpdateDateFieldIdentifier,
    MultiSelectBoxFieldIdentifier,
    OpenListFieldIdentifier,
    PermissionFieldIdentifier,
    PriorityFieldIdentifier,
    RadioButtonFieldIdentifier,
    SelectBoxFieldIdentifier,
    StringFieldIdentifier,
    SubmissionDateFieldIdentifier,
    SubmittedByFieldIdentifier,
    TextFieldIdentifier,
    TextFormat,
} from "@tuleap/plugin-tracker-constants";
import type { TrackerResponseWithColor } from "./trackers";
import type { UserWithEmailAndStatus } from "./users";

type RedGreenBlueColor = {
    readonly r: number;
    readonly g: number;
    readonly b: number;
};

export interface StaticValueRepresentation {
    readonly id: string;
    readonly label: string;
    readonly color: RedGreenBlueColor | null;
    readonly tlp_color: ColorName | null;
}

export interface UserGroupRepresentation {
    readonly id: string;
    readonly uri: string;
    readonly label: string;
    readonly users_uri: string;
    readonly short_name: string;
    readonly key: string;
}

interface BaseChangesetValue {
    readonly field_id: number;
    readonly label: string;
}

export interface UnknownChangesetValue extends BaseChangesetValue {
    readonly type: never;
    readonly value: never;
}

export interface ArtifactLink {
    readonly id: number;
    readonly type: string | null;
}

export interface ArtifactLinkChangesetValue extends BaseChangesetValue {
    readonly type: ArtifactLinkFieldIdentifier;
    readonly links: ReadonlyArray<ArtifactLink>;
    readonly reverse_links: ReadonlyArray<ArtifactLink>;
}

export interface NumericChangesetValue extends BaseChangesetValue {
    readonly type:
        | ArtifactIdFieldIdentifier
        | ArtifactIdInTrackerFieldIdentifier
        | IntFieldIdentifier
        | FloatFieldIdentifier
        | PriorityFieldIdentifier;
    readonly value: number | null;
}

export interface StringChangesetValue extends BaseChangesetValue {
    readonly type: StringFieldIdentifier;
    readonly value: string | null;
}

export interface TextChangesetValue extends BaseChangesetValue {
    readonly type: TextFieldIdentifier;
    readonly value: string | null;
    readonly format: TextFormat | HTMLFormat;
    readonly commonmark?: string;
}

export interface DateChangesetValue extends BaseChangesetValue {
    readonly type:
        | DateFieldIdentifier
        | LastUpdateDateFieldIdentifier
        | SubmissionDateFieldIdentifier;
    readonly value: string | null;
}

export interface ComputedChangesetValue extends BaseChangesetValue {
    readonly type: ComputedFieldIdentifier;
    readonly value: number | null;
    readonly manual_value: number | null;
    readonly is_autocomputed: boolean;
}

interface FileDescription {
    readonly id: number;
    readonly submitted_by: number;
    readonly description: string;
    readonly name: string;
    readonly size: number;
    readonly type: string;
    readonly html_url: string;
    readonly html_preview_url: string;
    readonly uri: string;
}

export interface FileChangesetValue extends BaseChangesetValue {
    readonly type: FileFieldIdentifier;
    readonly file_descriptions: ReadonlyArray<FileDescription>;
}

export interface SubmittedByChangesetValue extends BaseChangesetValue {
    readonly type: SubmittedByFieldIdentifier;
    readonly value: UserWithEmailAndStatus;
}

export interface LastUpdateByChangesetValue extends BaseChangesetValue {
    readonly type: LastUpdateByFieldIdentifier;
    readonly value: UserWithEmailAndStatus;
}

export interface SimpleListChangesetValue extends BaseChangesetValue {
    readonly type:
        | SelectBoxFieldIdentifier
        | RadioButtonFieldIdentifier
        | MultiSelectBoxFieldIdentifier
        | CheckBoxFieldIdentifier;
    readonly values:
        | ReadonlyArray<UserWithEmailAndStatus>
        | ReadonlyArray<StaticValueRepresentation>
        | ReadonlyArray<UserGroupRepresentation>;
}

export interface OpenListValueRepresentation {
    readonly id: string;
    readonly label: string;
}

export interface OpenListChangesetValue extends BaseChangesetValue {
    readonly type: OpenListFieldIdentifier;
    readonly bind_value_objects:
        | ReadonlyArray<UserWithEmailAndStatus>
        | ReadonlyArray<OpenListValueRepresentation | StaticValueRepresentation>
        | ReadonlyArray<UserGroupRepresentation>;
}

export interface PermissionChangesetValue extends BaseChangesetValue {
    readonly type: PermissionFieldIdentifier;
    readonly granted_groups: string[];
    readonly granted_groups_ids: string[];
}

export interface CrossReferenceChangesetValue extends BaseChangesetValue {
    readonly type: CrossReferenceFieldIdentifier;
    readonly value: ReadonlyArray<{
        readonly ref: string;
        readonly url: string;
        readonly direction: string;
    }>;
}

export interface Status {
    value: string;
    color: ColorName | null;
}

export type ChangesetValue =
    | UnknownChangesetValue
    | NumericChangesetValue
    | StringChangesetValue
    | TextChangesetValue
    | DateChangesetValue
    | ComputedChangesetValue
    | FileChangesetValue
    | SubmittedByChangesetValue
    | LastUpdateByChangesetValue
    | SimpleListChangesetValue
    | OpenListChangesetValue
    | PermissionChangesetValue
    | CrossReferenceChangesetValue
    | ArtifactLinkChangesetValue;

/**
 * Do not use this type directly as it contains way too many things.
 * Instead, create your own type with Pick:
 * `type Subset = Pick<ArtifactResponseNoInstance, "id" | "title" | "xref">;`
 */
export interface ArtifactResponseNoInstance {
    readonly _pick_what_you_need: never;
    readonly id: number;
    readonly title: string | null;
    readonly xref: string;
    readonly tracker: TrackerResponseWithColor;
    readonly html_url: string;
    readonly status: string;
    readonly is_open: boolean;
    readonly values: ReadonlyArray<ChangesetValue>;
    readonly full_status: Status | null;
    readonly uri: string;
    readonly submitted_on: string;
}
