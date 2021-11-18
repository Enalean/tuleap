/*
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

export interface GlobalExportProperties {
    readonly report_id: number;
    readonly report_name: string;
    readonly report_has_changed: boolean;
    readonly tracker_shortname: string;
    readonly platform_name: string;
    readonly platform_logo_url: string;
    readonly project_name: string;
    readonly tracker_id: number;
    readonly tracker_name: string;
    readonly user_display_name: string;
    readonly user_timezone: string;
    readonly report_url: string;
    readonly report_criteria: ReportCriteria;
    readonly base_url: string;
}

export type ReportCriteria = ExportReportCriteria | ClassicReportCriteria;

interface ExportReportCriteria {
    readonly is_in_expert_mode: true;
    readonly query: string;
}

interface ClassicReportCriteria {
    readonly is_in_expert_mode: false;
    readonly criteria: ReadonlyArray<ReportCriterionValue>;
}

export type ReportCriterionValue = ClassicReportCriterionValue | DateReportCriterionValue;

interface ClassicReportCriterionValue {
    readonly criterion_name: string;
    readonly criterion_type: "classic";
    readonly criterion_value: string;
}

export type DateReportCriterionValue =
    | DateReportCriterionSimpleValue
    | DateReportCriterionAdvancedValue;

interface DateReportCriterionSimpleValue {
    readonly criterion_name: string;
    readonly criterion_type: "date";
    readonly criterion_from_value: null;
    readonly criterion_to_value: string;
    readonly criterion_value_operator: "=" | "<" | ">";
    readonly is_advanced: false;
}

interface DateReportCriterionAdvancedValue {
    readonly criterion_name: string;
    readonly criterion_type: "date";
    readonly criterion_from_value: string | null;
    readonly criterion_to_value: string | null;
    readonly criterion_value_operator: "=" | "<" | ">";
    readonly is_advanced: true;
}

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
    readonly type: string;
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

export interface ExportDocument {
    readonly name: string;
    readonly artifacts: ReadonlyArray<FormattedArtifact>;
    readonly traceability_matrix: ReadonlyArray<TraceabilityMatrixElement>;
}

export interface ArtifactContainer {
    readonly name: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue>;
    readonly containers: ReadonlyArray<this>;
}

export interface FormattedArtifact {
    readonly id: number;
    readonly title: string;
    readonly fields: ReadonlyArray<ArtifactFieldValue>;
    readonly containers: ReadonlyArray<ArtifactContainer>;
}

export interface DateTimeLocaleInformation {
    readonly locale: string;
    readonly timezone: string;
}

export interface TraceabilityMatrixElement {
    readonly requirement: string;
    readonly test: {
        id: number;
        title: string;
    };
    readonly campaign: string;
    readonly result: ArtifactFieldValueStatus;
    readonly executed_by: string | null;
    readonly executed_on: string | null;
}
