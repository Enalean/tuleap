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
