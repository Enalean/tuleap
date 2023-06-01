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

export type { ColorName } from "@tuleap/core-constants";
export { ARTIFACT_TYPE, KANBAN_TYPE } from "@tuleap/core-constants";

export type ArtifactIdFieldIdentifier = "aid";
export type ArtifactIdInTrackerFieldIdentifier = "atid";
export type ArtifactLinkFieldIdentifier = "art_link";
export type CheckBoxFieldIdentifier = "cb";
export type ColumnIdentifier = "column";
export type ComputedFieldIdentifier = "computed";
export type CrossReferenceFieldIdentifier = "cross";
export type DateFieldIdentifier = "date";
export type FieldSetIdentifier = "fieldset";
export type FileFieldIdentifier = "file";
export type FloatFieldIdentifier = "float";
export type IntFieldIdentifier = "int";
export type LastUpdateDateFieldIdentifier = "lud";
export type LastUpdateByFieldIdentifier = "luby";
export type MultiSelectBoxFieldIdentifier = "msb";
export type OpenListFieldIdentifier = "tbl";
export type PermissionFieldIdentifier = "perm";
export type PriorityFieldIdentifier = "priority";
export type RadioButtonFieldIdentifier = "rb";
export type SelectBoxFieldIdentifier = "sb";
export type StringFieldIdentifier = "string";
export type SubmissionDateFieldIdentifier = "subon";
export type SubmittedByFieldIdentifier = "subby";
export type TextFieldIdentifier = "text";

export const ARTIFACT_ID_FIELD: ArtifactIdFieldIdentifier = "aid";
export const ARTIFACT_ID_IN_TRACKER_FIELD: ArtifactIdInTrackerFieldIdentifier = "atid";
export const ARTIFACT_LINK_FIELD: ArtifactLinkFieldIdentifier = "art_link";
export const CHECKBOX_FIELD: CheckBoxFieldIdentifier = "cb";
export const COMPUTED_FIELD: ComputedFieldIdentifier = "computed";
export const CONTAINER_COLUMN: ColumnIdentifier = "column";
export const CONTAINER_FIELDSET: FieldSetIdentifier = "fieldset";
export const CROSS_REFERENCE_FIELD: CrossReferenceFieldIdentifier = "cross";
export const DATE_FIELD: DateFieldIdentifier = "date";
export const FILE_FIELD: FileFieldIdentifier = "file";
export const FLOAT_FIELD: FloatFieldIdentifier = "float";
export const INT_FIELD: IntFieldIdentifier = "int";
export const LAST_UPDATED_BY_FIELD: LastUpdateByFieldIdentifier = "luby";
export const LAST_UPDATE_DATE_FIELD: LastUpdateDateFieldIdentifier = "lud";
export const MULTI_SELECTBOX_FIELD: MultiSelectBoxFieldIdentifier = "msb";
export const OPEN_LIST_FIELD: OpenListFieldIdentifier = "tbl";
export const PERMISSION_FIELD: PermissionFieldIdentifier = "perm";
export const PRIORITY_FIELD: PriorityFieldIdentifier = "priority";
export const RADIO_BUTTON_FIELD: RadioButtonFieldIdentifier = "rb";
export const SELECTBOX_FIELD: SelectBoxFieldIdentifier = "sb";
export const STRING_FIELD: StringFieldIdentifier = "string";
export const SUBMISSION_DATE_FIELD: SubmissionDateFieldIdentifier = "subon";
export const SUBMITTED_BY_FIELD: SubmittedByFieldIdentifier = "subby";
export const TEXT_FIELD: TextFieldIdentifier = "text";

export type StructuralFieldIdentifier =
    | ColumnIdentifier
    | FieldSetIdentifier
    | "linebreak"
    | "separator"
    | "staticrichtext";

export const STRUCTURAL_FIELDS: ReadonlyArray<StructuralFieldIdentifier> = [
    CONTAINER_COLUMN,
    CONTAINER_FIELDSET,
    "linebreak",
    "separator",
    "staticrichtext",
];

export type ReadOnlyFieldIdentifier =
    | ArtifactIdFieldIdentifier
    | ArtifactIdInTrackerFieldIdentifier
    | "burndown"
    | CrossReferenceFieldIdentifier
    | LastUpdateDateFieldIdentifier
    | LastUpdateByFieldIdentifier
    | PriorityFieldIdentifier
    | SubmissionDateFieldIdentifier
    | SubmittedByFieldIdentifier;

export const READ_ONLY_FIELDS: ReadonlyArray<ReadOnlyFieldIdentifier> = [
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    "burndown",
    CROSS_REFERENCE_FIELD,
    LAST_UPDATED_BY_FIELD,
    LAST_UPDATE_DATE_FIELD,
    PRIORITY_FIELD,
    SUBMISSION_DATE_FIELD,
    SUBMITTED_BY_FIELD,
];

export type FieldTypeIdentifier =
    | StructuralFieldIdentifier
    | ReadOnlyFieldIdentifier
    | CheckBoxFieldIdentifier
    | ComputedFieldIdentifier
    | DateFieldIdentifier
    | FileFieldIdentifier
    | FloatFieldIdentifier
    | IntFieldIdentifier
    | MultiSelectBoxFieldIdentifier
    | OpenListFieldIdentifier
    | PermissionFieldIdentifier
    | RadioButtonFieldIdentifier
    | SelectBoxFieldIdentifier
    | StringFieldIdentifier
    | TextFieldIdentifier;

export type StaticBindIdentifier = "static";
export type UserGroupsBindIdentifier = "ugroups";
export type UsersBindIdentifier = "users";
export type ListBindType = StaticBindIdentifier | UserGroupsBindIdentifier | UsersBindIdentifier;

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

export type UntypedLink = "";
export const UNTYPED_LINK: UntypedLink = "";

export type IsChildLinkType = "_is_child";
export const IS_CHILD_LINK_TYPE: IsChildLinkType = "_is_child";

export type MirroredMilestoneLinkType = "_mirrored_milestone";
export const MIRRORED_MILESTONE_LINK_TYPE: MirroredMilestoneLinkType = "_mirrored_milestone";
