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
    ColorName,
    ColumnIdentifier,
    ComputedFieldIdentifier,
    CreatePermission,
    DateFieldIdentifier,
    FieldSetIdentifier,
    FloatFieldIdentifier,
    IntFieldIdentifier,
    LastUpdateByFieldIdentifier,
    LastUpdateDateFieldIdentifier,
    PermissionFieldIdentifier,
    PriorityFieldIdentifier,
    ReadPermission,
    StringFieldIdentifier,
    SubmissionDateFieldIdentifier,
    SubmittedByFieldIdentifier,
    TextFieldIdentifier,
    UpdatePermission,
    LineBreak,
    Separator,
    StaticRichText,
    DefaultValueDateType,
    ShouldDisplayTime,
    CrossReferenceFieldIdentifier,
    FileFieldIdentifier,
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

export interface LabelDecorator {
    readonly icon?: string;
    readonly url?: string;
    readonly description: string;
    readonly label: string;
}

export interface BaseFieldStructure {
    readonly field_id: number;
    readonly name: string;
    readonly required: boolean;
    readonly has_notifications: boolean;
    readonly label: string;
    readonly label_decorators: ReadonlyArray<LabelDecorator>;
}

export interface UnknownFieldStructure extends BaseFieldStructure {
    readonly type: never;
}

export interface StringFieldStructure extends BaseFieldStructure {
    readonly type: StringFieldIdentifier;
    readonly specific_properties: {
        readonly maxchars: number;
        readonly size: number;
        readonly default_value: string;
    };
}

export interface TextFieldStructure extends BaseFieldStructure {
    readonly type: TextFieldIdentifier;
    readonly specific_properties: {
        readonly rows: number;
        readonly default_value: string;
    };
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
    readonly specific_properties: {
        readonly default_value_type: DefaultValueDateType;
        readonly default_value: number;
        readonly display_time: ShouldDisplayTime;
    };
}

export type DateFieldStructure = ReadonlyDateFieldStructure | EditableDateFieldStructure;

export interface ContainerFieldStructure extends BaseFieldStructure {
    readonly type: ColumnIdentifier | FieldSetIdentifier;
    readonly label: string;
}

export interface PermissionsOnArtifactFieldStructure extends BaseFieldStructure {
    readonly type: PermissionFieldIdentifier;
    readonly values: {
        readonly is_used_by_default: boolean;
        readonly ugroup_representations: ReadonlyArray<UserGroupRepresentation>;
    };
}

export interface NumericFieldStructure extends BaseFieldStructure {
    readonly type:
        | ArtifactIdFieldIdentifier
        | ArtifactIdInTrackerFieldIdentifier
        | FloatFieldIdentifier
        | IntFieldIdentifier
        | PriorityFieldIdentifier
        | ComputedFieldIdentifier;
}

export interface IntFieldStructure extends NumericFieldStructure {
    readonly type: IntFieldIdentifier;
    readonly specific_properties: {
        readonly maxchars: number;
        readonly size: number;
        readonly default_value: string;
    };
}

export interface FloatFieldStructure extends NumericFieldStructure {
    readonly type: FloatFieldIdentifier;
    readonly specific_properties: {
        readonly maxchars: number;
        readonly size: number;
        readonly default_value: string;
    };
}

export interface ComputedFieldStructure extends NumericFieldStructure {
    readonly type: ComputedFieldIdentifier;
    readonly specific_properties: {
        default_value: number;
    };
}
export interface UserFieldStructure extends BaseFieldStructure {
    readonly type: SubmittedByFieldIdentifier | LastUpdateByFieldIdentifier;
}

export interface LineBreakStructure extends BaseFieldStructure {
    readonly type: LineBreak;
}

export interface SeparatorStructure extends BaseFieldStructure {
    readonly type: Separator;
}

export interface StaticRichTextStructure extends BaseFieldStructure {
    readonly type: StaticRichText;
    readonly default_value: string;
}

export interface CrossReferenceStructure extends BaseFieldStructure {
    readonly type: CrossReferenceFieldIdentifier;
}

export interface FileFieldStructure extends BaseFieldStructure {
    readonly type: FileFieldIdentifier;
}

type StaticField = LineBreakStructure | SeparatorStructure | StaticRichTextStructure;

export type StructureFields =
    | UnknownFieldStructure
    | ArtifactLinkFieldStructure
    | ContainerFieldStructure
    | DateFieldStructure
    | ListFieldStructure
    | OpenListFieldStructure
    | PermissionsOnArtifactFieldStructure
    | StringFieldStructure
    | TextFieldStructure
    | NumericFieldStructure
    | StaticField
    | UserFieldStructure
    | CrossReferenceStructure
    | FileFieldStructure;

export interface StructureFormat {
    readonly id: number;
    readonly content: null | ReadonlyArray<this>;
}

export type TrackerProjectRepresentation = ProjectReference;

export type SemanticsRepresentation = {
    readonly title?: {
        readonly field_id: number;
    };
    readonly description?: {
        readonly field_id: number;
    };
    readonly status?: {
        readonly field_id: number;
    };
    readonly contributor?: {
        readonly field_id: number;
    };
    readonly initial_effort?: {
        readonly field_id: number;
    };
    readonly progress?:
        | {
              readonly artifact_link_type: "_is_child";
          }
        | {
              readonly total_effort_field_id: number;
              readonly remaining_effort_field_id: number;
          };
    readonly timeframe?:
        | {
              readonly start_date_field_id: number;
              readonly end_date_field_id: number;
          }
        | {
              readonly start_date_field_id: number;
              readonly duration_field_id: number;
          }
        | {
              readonly implied_from_tracker: {
                  readonly id: number;
                  readonly uri: string;
              };
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
