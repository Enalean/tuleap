/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    ComputedFieldIdentifier,
    CrossReferenceFieldIdentifier,
    DateFieldIdentifier,
    FloatFieldIdentifier,
    IntFieldIdentifier,
    LastUpdateByFieldIdentifier,
    LastUpdateDateFieldIdentifier,
    ListBindType,
    MultiSelectBoxFieldIdentifier,
    OpenListFieldIdentifier,
    PriorityFieldIdentifier,
    RadioButtonFieldIdentifier,
    PermissionFieldIdentifier,
    SelectBoxFieldIdentifier,
    StaticBindIdentifier,
    StringFieldIdentifier,
    SubmissionDateFieldIdentifier,
    SubmittedByFieldIdentifier,
    TextFieldIdentifier,
    UserGroupsBindIdentifier,
    UsersBindIdentifier,
} from "@tuleap/plugin-tracker-constants";

export type OtherFieldIdentifier = "other";
export type FieldType =
    | StringFieldIdentifier
    | TextFieldIdentifier
    | IntFieldIdentifier
    | FloatFieldIdentifier
    | ArtifactIdFieldIdentifier
    | ArtifactIdInTrackerFieldIdentifier
    | PriorityFieldIdentifier
    | ComputedFieldIdentifier
    | DateFieldIdentifier
    | LastUpdateDateFieldIdentifier
    | SubmissionDateFieldIdentifier
    | SelectBoxFieldIdentifier
    | MultiSelectBoxFieldIdentifier
    | RadioButtonFieldIdentifier
    | CheckBoxFieldIdentifier
    | OpenListFieldIdentifier
    | SubmittedByFieldIdentifier
    | LastUpdateByFieldIdentifier
    | ArtifactLinkFieldIdentifier
    | CrossReferenceFieldIdentifier
    | PermissionFieldIdentifier
    | OtherFieldIdentifier;

export const OTHER_FIELD: OtherFieldIdentifier = "other";

export interface ReportData {
    readonly first_level: ReportLevel;
    readonly second_level?: ReportLevel;
    readonly third_level?: ReportLevel;
}

export interface ReportLevel {
    readonly artifacts: ReadonlyArray<ArtifactValue>;
}

export interface ArtifactValue {
    readonly id: number;
    readonly values: ReadonlyArray<FieldValue>;
}

export interface BaseFieldValue {
    readonly type: FieldType;
    readonly label: string;
    readonly name: string;
}

export interface StringFieldValue extends BaseFieldValue {
    readonly type: StringFieldIdentifier;
    readonly value: string | null;
}

export interface TextFieldValue extends BaseFieldValue {
    readonly type: TextFieldIdentifier;
    readonly value: string | null;
    readonly commonmark?: string;
}

export interface IntFieldValue extends BaseFieldValue {
    readonly type: IntFieldIdentifier;
    readonly value: number | null;
}

export interface FloatFieldValue extends BaseFieldValue {
    readonly type: FloatFieldIdentifier;
    readonly value: number | null;
}

export interface ArtifactIdFieldValue extends BaseFieldValue {
    readonly type: ArtifactIdFieldIdentifier;
    readonly value: number | null;
}

export interface ArtifactIdPerTrackerFieldValue extends BaseFieldValue {
    readonly type: ArtifactIdInTrackerFieldIdentifier;
    readonly value: number | null;
}

export interface PriorityFieldValue extends BaseFieldValue {
    readonly type: PriorityFieldIdentifier;
    readonly value: number | null;
}

export interface ComputedFieldValue extends BaseFieldValue {
    readonly type: ComputedFieldIdentifier;
    readonly value: number | null;
}

export interface DateFieldValue extends BaseFieldValue {
    readonly type: DateFieldIdentifier;
    readonly with_time: boolean;
    readonly value: Date | null;
}

export interface LastUpdateDateFieldValue extends BaseFieldValue {
    readonly type: LastUpdateDateFieldIdentifier;
    readonly value: Date | null;
}

export interface SubmittedOnFieldValue extends BaseFieldValue {
    readonly type: SubmissionDateFieldIdentifier;
    readonly value: Date | null;
}

export interface BaseListValue {
    readonly type: ListBindType;
}

export interface StaticValue {
    readonly label: string;
}

export interface StaticListValue extends BaseListValue {
    readonly type: StaticBindIdentifier;
    readonly value: ReadonlyArray<StaticValue>;
}

export interface UserValue {
    readonly display_name: string;
    readonly username: string | null;
}

export interface UsersListValue extends BaseListValue {
    readonly type: UsersBindIdentifier;
    readonly value: ReadonlyArray<UserValue>;
}

export interface UGroupValue {
    readonly label: string;
    readonly key: string;
}

export interface UGroupsListValue extends BaseListValue {
    readonly type: UserGroupsBindIdentifier;
    readonly value: ReadonlyArray<UGroupValue>;
}

export type ListValue = StaticListValue | UsersListValue | UGroupsListValue;

export interface SelectboxFieldValue extends BaseFieldValue {
    readonly type: SelectBoxFieldIdentifier;
    readonly value: ListValue;
}

export interface MultiSelectboxFieldValue extends BaseFieldValue {
    readonly type: MultiSelectBoxFieldIdentifier;
    readonly value: ListValue;
}

export interface RadioButtonFieldValue extends BaseFieldValue {
    readonly type: RadioButtonFieldIdentifier;
    readonly value: ListValue;
}

export interface CheckboxFieldValue extends BaseFieldValue {
    readonly type: CheckBoxFieldIdentifier;
    readonly value: ListValue;
}

export interface OpenListFieldValue extends BaseFieldValue {
    readonly type: OpenListFieldIdentifier;
    readonly value: ListValue;
}

export interface SubmittedByFieldValue extends BaseFieldValue {
    readonly type: SubmittedByFieldIdentifier;
    readonly value: UserValue;
}

export interface LastUpdateByFieldValue extends BaseFieldValue {
    readonly type: LastUpdateByFieldIdentifier;
    readonly value: UserValue;
}

export interface ArtifactLinkValue {
    readonly nature: string | null;
    readonly target: number;
}

export interface ArtifactLinksFieldValue extends BaseFieldValue {
    readonly type: ArtifactLinkFieldIdentifier;
    readonly forward: ReadonlyArray<ArtifactLinkValue>;
    readonly reverse: ReadonlyArray<ArtifactLinkValue>;
}

export interface CrossReferenceValue {
    readonly reference: string;
    readonly url: string;
}

export interface CrossReferenceFieldValue extends BaseFieldValue {
    readonly type: CrossReferenceFieldIdentifier;
    readonly value: ReadonlyArray<CrossReferenceValue>;
}

export interface PermissionsFieldValue extends BaseFieldValue {
    readonly type: PermissionFieldIdentifier;
    readonly value: ReadonlyArray<string>;
}

export interface OtherFieldValue extends BaseFieldValue {
    readonly type: OtherFieldIdentifier;
    readonly value: string;
}

export type FieldValue =
    | StringFieldValue
    | TextFieldValue
    | IntFieldValue
    | FloatFieldValue
    | ArtifactIdFieldValue
    | ArtifactIdPerTrackerFieldValue
    | PriorityFieldValue
    | ComputedFieldValue
    | DateFieldValue
    | LastUpdateDateFieldValue
    | SubmittedOnFieldValue
    | SelectboxFieldValue
    | MultiSelectboxFieldValue
    | RadioButtonFieldValue
    | CheckboxFieldValue
    | OpenListFieldValue
    | SubmittedByFieldValue
    | LastUpdateByFieldValue
    | ArtifactLinksFieldValue
    | CrossReferenceFieldValue
    | PermissionsFieldValue
    | OtherFieldValue;
