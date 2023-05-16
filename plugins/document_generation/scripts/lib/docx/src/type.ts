/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    ArtifactLink,
    ArtifactLinkFieldStructure,
    ArtifactResponseNoInstance as ArtifactResponseWithoutStepDefinition,
    BaseFieldStructure,
    ChangesetValue,
    ComputedChangesetValue,
    ContainerFieldStructure,
    CrossReferenceChangesetValue,
    DateChangesetValue,
    DateFieldStructure,
    FileChangesetValue,
    LastUpdateByChangesetValue,
    ListFieldStructure,
    ListLikeFieldStructure,
    NumericChangesetValue,
    OpenListChangesetValue,
    OpenListFieldStructure,
    PermissionChangesetValue,
    PermissionsOnArtifactFieldStructure,
    SimpleListChangesetValue,
    StringChangesetValue,
    StructureFormat,
    SubmittedByChangesetValue,
    TextChangesetValue,
    TrackerResponseNoInstance,
    UnknownChangesetValue,
    UnknownFieldStructure,
    UserWithEmailAndStatus,
} from "@tuleap/plugin-tracker-rest-api-types";
import type { ArtifactLinkFieldIdentifier } from "@tuleap/plugin-tracker-constants";
export type {
    StructureFormat,
    UserWithEmailAndStatus as ArtifactReportResponseUserRepresentation,
    UserGroupRepresentation as ArtifactReportResponseUserGroupRepresentation,
    UnknownChangesetValue as ArtifactReportResponseUnknownFieldValue,
    ArtifactLink,
} from "@tuleap/plugin-tracker-rest-api-types";

interface ArtifactFieldValueContent {
    readonly field_name: string;
    readonly field_value: string;
    readonly value_type: "string";
}

interface ArtifactFieldValueShort {
    readonly content_length: "short";
}

interface ArtifactFieldValueLong {
    readonly content_length: "long";
    readonly content_format: "plaintext" | "html";
}

interface ArtifactFieldValueLinksContent {
    readonly field_name: string;
    readonly field_value: Array<ArtifactFieldValueLink>;
    readonly value_type: "links";
    readonly content_length: "short";
}

interface ArtifactFieldValueLink {
    readonly link_label: string;
    readonly link_url: string;
}

export interface ArtifactFieldValueStepDefinitionContent {
    readonly field_name: string;
    readonly content_length: "blockttmstepdef";
    readonly value_type: "string";
    readonly steps: Array<ArtifactFieldValueStepDefinition>;
}

export type ArtifactFieldValueStatus = "notrun" | "passed" | "failed" | "blocked" | null;

interface ArtifactFieldValueStepExecutionContent {
    readonly field_name: string;
    readonly content_length: "blockttmstepexec";
    readonly value_type: "string";
    readonly steps: Array<ArtifactFieldValueStepDefinitionEnhanced>;
    readonly steps_values: ReadonlyArray<ArtifactFieldValueStatus>;
}

interface ArtifactFieldValueArtifactLinkContent {
    readonly field_name: string;
    readonly content_length: "artlinktable";
    readonly value_type: "string";
    readonly links: ReadonlyArray<ArtifactFieldValueArtifactLink>;
    readonly reverse_links: ReadonlyArray<ArtifactFieldValueArtifactLink>;
}

export interface ArtifactFieldValueArtifactLink {
    readonly artifact_id: number;
    readonly title: string;
    readonly type: string;
    readonly is_linked_artifact_part_of_document: boolean;
    readonly html_url: URL | null;
}

export interface ArtifactFieldValueStepDefinition {
    readonly description: string;
    readonly description_format: "plaintext" | "html";
    readonly expected_results: string;
    readonly expected_results_format: "plaintext" | "html";
    readonly rank: number;
}

export interface ArtifactFieldValueStepDefinitionEnhanced extends ArtifactFieldValueStepDefinition {
    readonly status: ArtifactFieldValueStatus;
}

export type TransformStepDefFieldValue<StepDefFieldValue> = (
    base_url: string,
    value: ArtifactReportResponseStepDefinitionFieldValue
) => StepDefFieldValue;

export type ArtifactFieldValue<StepDefFieldValue> =
    | (ArtifactFieldValueContent & (ArtifactFieldValueShort | ArtifactFieldValueLong))
    | ArtifactFieldValueLinksContent
    | StepDefFieldValue
    | ArtifactFieldValueStepExecutionContent
    | ArtifactFieldValueArtifactLinkContent;

export type ArtifactFieldShortValue =
    | (ArtifactFieldValueContent & ArtifactFieldValueShort)
    | ArtifactFieldValueLinksContent;

export interface ArtifactContainer<StepDefFieldValue> {
    readonly name: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue<StepDefFieldValue>>;
    readonly containers: ReadonlyArray<this>;
}
export interface FormattedArtifact<StepDefFieldValue> {
    readonly id: number;
    readonly title: string;
    readonly short_title: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue<StepDefFieldValue>>;
    readonly containers: ReadonlyArray<ArtifactContainer<StepDefFieldValue>>;
}

export interface ArtifactLinkType {
    readonly reverse_label: string;
    readonly forward_label: string;
    readonly shortname: string;
    readonly is_system: boolean;
    readonly is_visible: boolean;
}

export interface ArtifactFromReport {
    readonly id: number;
    readonly title: string | null;
    readonly xref: string;
    values: ReadonlyArray<ArtifactReportFieldValue>;
    containers: ReadonlyArray<ArtifactReportContainer>;
}

export interface ArtifactReportContainer {
    name: string;
    values: ReadonlyArray<ArtifactReportFieldValue>;
    containers: ReadonlyArray<this>;
}

export type ArtifactReportFieldValue =
    | UnknownChangesetValue
    | NumericChangesetValue
    | StringChangesetValue
    | TextChangesetValue
    | (DateChangesetValue & { is_time_displayed: boolean })
    | ComputedChangesetValue
    | FileChangesetValue
    | SubmittedByChangesetValue
    | LastUpdateByChangesetValue
    | (SimpleListChangesetValue & { formatted_values: string[] })
    | (OpenListChangesetValue & { formatted_open_values: string[] })
    | (PermissionChangesetValue & {
          formatted_granted_ugroups: string[];
      })
    | CrossReferenceChangesetValue
    | ArtifactReportResponseStepDefinitionFieldValue
    | ArtifactStepExecutionFieldValue
    | ArtifactReportArtifactLinksFieldValue;

export interface ArtifactReportResponseStepDefinitionFieldValue {
    field_id: number;
    type: "ttmstepdef";
    label: string;
    value: Array<ArtifactReportResponseStepRepresentation>;
}

export interface ArtifactLinkWithTitle extends ArtifactLink {
    title: string;
    is_linked_artifact_part_of_document?: boolean;
    html_url?: string;
}

interface ArtifactReportArtifactLinksFieldValue {
    field_id: number;
    type: ArtifactLinkFieldIdentifier;
    label: string;
    links: ReadonlyArray<ArtifactLinkWithTitle>;
    reverse_links: ReadonlyArray<ArtifactLinkWithTitle>;
}

export interface ArtifactReportResponseStepRepresentation {
    id: number;
    description: string;
    description_format: string;
    expected_results: string;
    expected_results_format: string;
    rank: number;
}

export type TestExecStatus = "notrun" | "passed" | "failed" | "blocked";

export interface ArtifactReportResponseStepRepresentationEnhanced
    extends ArtifactReportResponseStepRepresentation {
    status: TestExecStatus | null;
}

export interface ArtifactStepExecutionFieldValue {
    field_id: number;
    type: "ttmstepexec";
    label: string;
    value: null | {
        steps: Array<ArtifactReportResponseStepRepresentationEnhanced>;
        steps_values: Array<TestExecStatus | null>;
    };
}

export interface DateTimeLocaleInformation {
    readonly locale: string;
    readonly timezone: string;
}

export type ArtifactReportResponseFieldValue =
    | ChangesetValue
    | ArtifactReportResponseStepDefinitionFieldValue;

export interface ArtifactResponse
    extends Pick<
        ArtifactResponseWithoutStepDefinition,
        "id" | "title" | "xref" | "tracker" | "html_url" | "status" | "is_open"
    > {
    readonly values: ReadonlyArray<ArtifactReportResponseFieldValue>;
}

export interface TrackerStructure {
    fields: ReadonlyMap<number, FieldsStructure>;
    disposition: ReadonlyArray<StructureFormat>;
}

type StructureFieldsWithoutUnusedProperties =
    | UnknownFieldStructure
    | Pick<DateFieldStructure, "field_id" | "type" | "is_time_displayed">
    | Pick<ContainerFieldStructure, "field_id" | "type" | "label">
    | Pick<ListLikeFieldStructure, "field_id" | "type">
    | Pick<ListFieldStructure, "field_id" | "type">
    | Pick<OpenListFieldStructure, "field_id" | "type">
    | Pick<PermissionsOnArtifactFieldStructure, "field_id" | "type" | "values">
    | Pick<ArtifactLinkFieldStructure, "field_id" | "type">;

export type FieldsStructure = StructureFieldsWithoutUnusedProperties | StepExecutionFieldStructure;

export interface StepExecutionFieldStructure extends Pick<BaseFieldStructure, "field_id"> {
    type: "ttmstepexec";
    label: string;
}

export interface TrackerDefinition
    extends Pick<TrackerResponseNoInstance, "id" | "label" | "item_name" | "structure"> {
    readonly fields: ReadonlyArray<FieldsStructure>;
}

export interface TestExecutionAttachment {
    readonly filename: string;
    readonly html_url: string;
}

export interface TestExecutionLinkedBug {
    readonly id: number;
    readonly title: string;
    readonly xref: string;
}

export interface TestExecutionResponse {
    definition: {
        id: number;
        summary: string;
        description: string;
        description_format: string;
        steps: Array<ArtifactReportResponseStepRepresentation>;
        all_requirements: ReadonlyArray<{
            id: number;
            title: string | null;
            xref: string;
            tracker: { readonly id: number };
        }>;
        artifact: ArtifactResponse;
    };
    steps_results: {
        [key: string]: {
            step_id: number;
            status: TestExecStatus;
        };
    };
    previous_result: {
        status: TestExecStatus | null;
        submitted_on: string;
        submitted_by: UserWithEmailAndStatus;
        result: string;
    } | null;
    status: TestExecStatus | null;
    attachments: ReadonlyArray<TestExecutionAttachment>;
    linked_bugs: ReadonlyArray<TestExecutionLinkedBug>;
}
