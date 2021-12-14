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

interface ArtifactFieldValueStepDefinitionContent {
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

export type ArtifactFieldValue =
    | (ArtifactFieldValueContent & (ArtifactFieldValueShort | ArtifactFieldValueLong))
    | ArtifactFieldValueLinksContent
    | ArtifactFieldValueStepDefinitionContent
    | ArtifactFieldValueStepExecutionContent
    | ArtifactFieldValueArtifactLinkContent;

export type ArtifactFieldShortValue =
    | (ArtifactFieldValueContent & ArtifactFieldValueShort)
    | ArtifactFieldValueLinksContent;

export interface ArtifactContainer {
    readonly name: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue>;
    readonly containers: ReadonlyArray<this>;
}

export interface FormattedArtifact {
    readonly id: number;
    readonly title: string;
    readonly short_title: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue>;
    readonly containers: ReadonlyArray<ArtifactContainer>;
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
    | ArtifactReportResponseUnknownFieldValue
    | ArtifactReportResponseNumericFieldValue
    | ArtifactReportResponseStringFieldValue
    | ArtifactReportResponseTextFieldValue
    | (ArtifactReportResponseDateFieldValue & { is_time_displayed: boolean })
    | ArtifactReportResponseComputedFieldValue
    | ArtifactReportResponseFileFieldValue
    | ArtifactReportResponseSubmittedByFieldValue
    | ArtifactReportResponseLastUpdateByFieldValue
    | (ArtifactReportResponseSimpleListFieldValue & { formatted_values: string[] })
    | (ArtifactReportResponseOpenListFieldValue & { formatted_open_values: string[] })
    | (ArtifactReportResponsePermissionsOnArtifactFieldValue & {
          formatted_granted_ugroups: string[];
      })
    | ArtifactReportResponseCrossReferencesFieldValue
    | ArtifactReportResponseStepDefinitionFieldValue
    | ArtifactStepExecutionFieldValue
    | ArtifactReportArtifactLinksFieldValue;

interface ArtifactReportResponseFileDescriptionFieldValue {
    id: number;
    submitted_by: number;
    description: string;
    name: string;
    size: number;
    type: string;
    html_url: string;
    html_preview_url: string;
    uri: string;
}

export interface ArtifactReportResponseUnknownFieldValue {
    field_id: number;
    type: never;
    label: string;
    value: never;
}

interface ArtifactReportResponseNumericFieldValue {
    field_id: number;
    type: "aid" | "atid" | "int" | "float" | "priority";
    label: string;
    value: number | null;
}

interface ArtifactReportResponseStringFieldValue {
    field_id: number;
    type: "string";
    label: string;
    value: string | null;
}

interface ArtifactReportResponseTextFieldValue {
    field_id: number;
    type: "text";
    label: string;
    value: string | null;
    format: "text" | "html";
}

interface ArtifactReportResponseDateFieldValue {
    field_id: number;
    type: "date" | "lud" | "subon";
    label: string;
    value: string | null;
}

interface ArtifactReportResponseComputedFieldValue {
    field_id: number;
    type: "computed";
    label: string;
    value: number | null;
    manual_value: number | null;
    is_autocomputed: boolean;
}

interface ArtifactReportResponseFileFieldValue {
    field_id: number;
    type: "file";
    label: string;
    file_descriptions: Array<ArtifactReportResponseFileDescriptionFieldValue>;
}

interface ArtifactReportResponseSubmittedByFieldValue {
    field_id: number;
    type: "subby";
    label: string;
    value: ArtifactReportResponseUserRepresentation;
}

interface ArtifactReportResponseLastUpdateByFieldValue {
    field_id: number;
    type: "luby";
    label: string;
    value: ArtifactReportResponseUserRepresentation;
}

interface ArtifactReportResponseSimpleListFieldValue {
    field_id: number;
    type: "sb" | "rb" | "msb" | "cb";
    label: string;
    values:
        | Array<ArtifactReportResponseUserRepresentation>
        | Array<ArtifactReportResponseStaticValueRepresentation>
        | Array<ArtifactReportResponseUserGroupRepresentation>;
}

interface ArtifactReportResponseOpenListFieldValue {
    field_id: number;
    type: "tbl";
    label: string;
    bind_value_objects:
        | Array<ArtifactReportResponseUserRepresentation>
        | Array<
              | ArtifactReportResponseOpenListValueRepresentation
              | ArtifactReportResponseStaticValueRepresentation
          >
        | Array<ArtifactReportResponseUserGroupRepresentation>;
}

export interface ArtifactReportResponseUserRepresentation {
    email: string;
    status: string;
    id: number | null;
    uri: string;
    user_url: string;
    real_name: string;
    display_name: string;
    username: string;
    ldap_id: string;
    avatar_url: string;
    is_anonymous: boolean;
    has_avatar: boolean;
}

interface ArtifactReportResponseStaticValueRepresentation {
    id: number;
    label: string;
    color: string | null;
    tlp_color: string | null;
}

interface ArtifactReportResponseOpenListValueRepresentation {
    id: number;
    label: string;
}

export interface ArtifactReportResponseUserGroupRepresentation {
    id: string;
    uri: string;
    label: string;
    users_uri: string;
    short_name: string;
    key: string;
}

interface ArtifactReportResponsePermissionsOnArtifactFieldValue {
    field_id: number;
    type: "perm";
    label: string;
    granted_groups: string[];
    granted_groups_ids: string[];
}

interface ArtifactReportResponseCrossReferencesFieldValue {
    field_id: number;
    type: "cross";
    label: string;
    value: Array<{
        ref: string;
        url: string;
        direction: string;
    }>;
}

export interface ArtifactReportResponseStepDefinitionFieldValue {
    field_id: number;
    type: "ttmstepdef";
    label: string;
    value: Array<ArtifactReportResponseStepRepresentation>;
}

export interface ArtifactLink {
    type: string | null;
    id: number;
}

export interface ArtifactLinkWithTitle extends ArtifactLink {
    title: string;
    is_linked_artifact_part_of_document?: boolean;
    html_url?: string;
}

interface ArtifactReportResponseArtifactLinksFieldValue {
    field_id: number;
    type: "art_link";
    label: string;
    links: ArtifactLink[];
    reverse_links: ArtifactLink[];
}

interface ArtifactReportArtifactLinksFieldValue {
    field_id: number;
    type: "art_link";
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
    | ArtifactReportResponseUnknownFieldValue
    | ArtifactReportResponseNumericFieldValue
    | ArtifactReportResponseStringFieldValue
    | ArtifactReportResponseTextFieldValue
    | ArtifactReportResponseDateFieldValue
    | ArtifactReportResponseComputedFieldValue
    | ArtifactReportResponseFileFieldValue
    | ArtifactReportResponseSubmittedByFieldValue
    | ArtifactReportResponseLastUpdateByFieldValue
    | ArtifactReportResponseSimpleListFieldValue
    | ArtifactReportResponseOpenListFieldValue
    | ArtifactReportResponsePermissionsOnArtifactFieldValue
    | ArtifactReportResponseCrossReferencesFieldValue
    | ArtifactReportResponseStepDefinitionFieldValue
    | ArtifactReportResponseArtifactLinksFieldValue;

export interface TrackerStructure {
    fields: ReadonlyMap<number, FieldsStructure>;
    disposition: ReadonlyArray<StructureFormat>;
}

export type FieldsStructure =
    | UnknownFieldStructure
    | DateFieldStructure
    | ContainerFieldStructure
    | ListFieldStructure
    | PermissionsOnArtifactFieldStructure
    | StepExecutionFieldStructure
    | ArtifactLinkFieldStructure;

interface BaseFieldStructure {
    field_id: number;
}

interface UnknownFieldStructure extends BaseFieldStructure {
    type: never;
}

interface DateFieldStructure extends BaseFieldStructure {
    type: "date" | "lud" | "subon";
    is_time_displayed: boolean;
}

interface ContainerFieldStructure extends BaseFieldStructure {
    type: "column" | "fieldset";
    label: string;
}

interface ListFieldStructure extends BaseFieldStructure {
    type: "sb" | "rb" | "msb" | "cb" | "tbl";
}

export interface StepExecutionFieldStructure extends BaseFieldStructure {
    type: "ttmstepexec";
    label: string;
}

interface PermissionsOnArtifactFieldStructure extends BaseFieldStructure {
    type: "perm";
    values: {
        is_used_by_default: boolean;
        ugroup_representations: Array<ArtifactReportResponseUserGroupRepresentation>;
    };
}

interface ArtifactLinkFieldStructure extends BaseFieldStructure {
    type: "art_link";
    label: string;
}

export interface StructureFormat {
    id: number;
    content: null | ReadonlyArray<this>;
}

export interface TrackerDefinition {
    fields: ReadonlyArray<FieldsStructure>;
    structure: ReadonlyArray<StructureFormat>;
}

export interface ArtifactResponse {
    readonly id: number;
    readonly title: string | null;
    readonly xref: string;
    readonly tracker: { readonly id: number };
    readonly html_url: string;
    readonly values: ReadonlyArray<ArtifactReportResponseFieldValue>;
}

export interface TestExecutionResponse {
    definition: {
        id: number;
        summary: string;
        description: string;
        description_format: string;
        steps: Array<ArtifactReportResponseStepRepresentation>;
        requirement: {
            id: number;
            title: string | null;
            xref: string;
        } | null;
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
        submitted_by: ArtifactReportResponseUserRepresentation;
    } | null;
}
