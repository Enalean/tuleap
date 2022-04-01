/*
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

export type ArtifactIdFieldIdentifier = "aid";
export type ArtifactIdInTrackerFieldIdentifier = "atid";
export type ComputedFieldIdentifier = "computed";
export type DateFieldIdentifier = "date";
export type FieldSetIdentifier = "fieldset";
export type FileFieldIdentifier = "file";
export type FloatFieldIdentifier = "float";
export type IntFieldIdentifier = "int";
export type SelectBoxFieldIdentifier = "sb";
export type TextFieldIdentifier = "text";

export const ARTIFACT_ID_FIELD: ArtifactIdFieldIdentifier = "aid";
export const ARTIFACT_ID_IN_TRACKER_FIELD: ArtifactIdInTrackerFieldIdentifier = "atid";
export const COMPUTED_FIELD: ComputedFieldIdentifier = "computed";
export const CONTAINER_FIELDSET: FieldSetIdentifier = "fieldset";
export const DATE_FIELD: DateFieldIdentifier = "date";
export const FILE_FIELD: FileFieldIdentifier = "file";
export const FLOAT_FIELD: FloatFieldIdentifier = "float";
export const INT_FIELD: IntFieldIdentifier = "int";
export const SELECTBOX_FIELD: SelectBoxFieldIdentifier = "sb";
export const TEXT_FIELD: TextFieldIdentifier = "text";

export type StructuralFieldIdentifier =
    | "column"
    | FieldSetIdentifier
    | "linebreak"
    | "separator"
    | "staticrichtext";

export const STRUCTURAL_FIELDS: StructuralFieldIdentifier[] = [
    "column",
    CONTAINER_FIELDSET,
    "linebreak",
    "separator",
    "staticrichtext",
];

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

export const READ_ONLY_FIELDS: ReadOnlyFieldIdentifier[] = [
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    "burndown",
    "cross",
    "luby",
    "lud",
    "priority",
    "subby",
    "subon",
];

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

export type StaticBindIdentifier = "static";
export type UserGroupsBindIdentifier = "ugroups";
export type UsersBindIdentifier = "users";

export const LIST_BIND_STATIC: StaticBindIdentifier = "static";
export const LIST_BIND_UGROUPS: UserGroupsBindIdentifier = "ugroups";
export const LIST_BIND_USERS: UsersBindIdentifier = "users";

export type TextFormat = "text";
export type HTMLFormat = "html";
export type CommonMarkFormat = "commonmark";
export type TextFieldFormat = TextFormat | HTMLFormat | CommonMarkFormat;

export const TEXT_FORMAT_TEXT: TextFormat = "text";
export const TEXT_FORMAT_HTML: HTMLFormat = "html";
export const TEXT_FORMAT_COMMONMARK: CommonMarkFormat = "commonmark";

export const isValidTextFormat = (format: string): format is TextFieldFormat =>
    format === TEXT_FORMAT_TEXT || format === TEXT_FORMAT_HTML || format === TEXT_FORMAT_COMMONMARK;

export type ReadPermission = "read";
export type CreatePermission = "create";
export type UpdatePermission = "update";
export type Permission = ReadPermission | CreatePermission | UpdatePermission;

export const FIELD_PERMISSION_READ: ReadPermission = "read";
export const FIELD_PERMISSION_CREATE: CreatePermission = "create";
export const FIELD_PERMISSION_UPDATE: UpdatePermission = "update";
