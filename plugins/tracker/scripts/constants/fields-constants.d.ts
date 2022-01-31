/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

export type FieldSetIdentifier = "fieldset";

export const CONTAINER_FIELDSET: FieldSetIdentifier;

export type StructuralFieldIdentifier =
    | "column"
    | FieldSetIdentifier
    | "linebreak"
    | "separator"
    | "staticrichtext";

export const STRUCTURAL_FIELDS: StructuralFieldIdentifier[];

export type ReadOnlyFieldIdentifier =
    | ArtifactIdFieldIdentifier
    | ArtifactIdInTrackerFieldIdentifier
    | "burndown"
    | "cross"
    | "luby"
    | "lud"
    | "priority"
    | "subby"
    | "subon";

export const READ_ONLY_FIELDS: ReadOnlyFieldIdentifier[];

export type ComputedFieldIdentifier = "computed";
export type SelectBoxFieldIdentifier = "sb";
export type DateFieldIdentifier = "date";
export type IntFieldIdentifier = "int";
export type FloatFieldIdentifier = "float";
export type TextFieldIdentifier = "text";
export type FileFieldIdentifier = "file";
export type ArtifactIdFieldIdentifier = "aid";
export type ArtifactIdInTrackerFieldIdentifier = "atid";

export type FieldTypeIdentifier =
    | StructuralFieldIdentifier
    | ReadOnlyFieldIdentifier
    | ComputedFieldIdentifier
    | SelectBoxFieldIdentifier
    | DateFieldIdentifier
    | IntFieldIdentifier
    | FloatFieldIdentifier
    | TextFieldIdentifier
    | FileFieldIdentifier;

export const COMPUTED_FIELD: ComputedFieldIdentifier;
export const SELECTBOX_FIELD: SelectBoxFieldIdentifier;
export const DATE_FIELD: DateFieldIdentifier;
export const INT_FIELD: IntFieldIdentifier;
export const FLOAT_FIELD: FloatFieldIdentifier;
export const ARTIFACT_ID_FIELD: ArtifactIdFieldIdentifier;
export const ARTIFACT_ID_IN_TRACKER_FIELD: ArtifactIdInTrackerFieldIdentifier;

export type StaticBindIdentifier = "static";
export type UserGroupsBindIdentifier = "ugroups";
export type UsersBindIdentifier = "users";

export const LIST_BIND_STATIC: StaticBindIdentifier;
export const LIST_BIND_UGROUPS: UserGroupsBindIdentifier;
export const LIST_BIND_USERS: UsersBindIdentifier;

export type TextFormat = "text";
export type HTMLFormat = "html";
export type CommonMarkFormat = "commonmark";
export type TextFieldFormat = TextFormat | HTMLFormat | CommonMarkFormat;

export const TEXT_FIELD: TextFieldIdentifier;
export const TEXT_FORMAT_TEXT: TextFormat;
export const TEXT_FORMAT_HTML: HTMLFormat;
export const TEXT_FORMAT_COMMONMARK: CommonMarkFormat;

export const isValidTextFormat: (format: string) => format is TextFieldFormat;

export const FILE_FIELD: FileFieldIdentifier;

export type ReadPermission = "read";
export type CreatePermission = "create";
export type UpdatePermission = "update";
export type Permission = ReadPermission | CreatePermission | UpdatePermission;

export const FIELD_PERMISSION_READ: ReadPermission;
export const FIELD_PERMISSION_CREATE: CreatePermission;
export const FIELD_PERMISSION_UPDATE: UpdatePermission;
