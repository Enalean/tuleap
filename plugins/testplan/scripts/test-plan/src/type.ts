/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    ArtifactFieldValueStatus,
    ArtifactLinkType,
    FormattedArtifact,
    TestExecutionResponse,
} from "@tuleap/plugin-docgen-docx";

export interface Campaign {
    readonly id: number;
    readonly label: string;
    readonly nb_of_notrun: number;
    readonly nb_of_blocked: number;
    readonly nb_of_failed: number;
    readonly nb_of_passed: number;
    readonly is_being_refreshed: boolean;
    readonly is_just_refreshed: boolean;
    readonly is_error: boolean;
}

interface ArtifactReference {
    readonly id: number;
    readonly tracker: { readonly id: number };
}

interface TestExecutionUsedToDefineStatus extends ArtifactReference {
    submitted_on: string;
    submitted_by: {
        display_name: string;
    };
}

export interface BacklogItemFromREST {
    readonly id: number;
    readonly label: string;
    readonly short_type: string;
    readonly color: string;
    readonly artifact: ArtifactReference;
    readonly can_add_a_test: boolean;
}

export interface BacklogItem extends BacklogItemFromREST {
    readonly is_expanded: boolean;
    readonly is_just_refreshed: boolean;
    readonly are_test_definitions_loaded: boolean;
    readonly is_loading_test_definitions: boolean;
    readonly has_test_definitions_loading_error: boolean;
    readonly test_definitions: TestDefinition[];
}

interface TestDefinitionFromRESTWithNoStatusInformation {
    readonly id: number;
    readonly short_type: string;
    readonly summary: string;
    readonly automated_tests: string;
    readonly category: string | null;
}

export interface PlannedTestDefinitionFromREST
    extends TestDefinitionFromRESTWithNoStatusInformation {
    readonly test_status: "passed" | "failed" | "blocked" | "notrun";
    readonly test_execution_used_to_define_status: TestExecutionUsedToDefineStatus;
    readonly test_campaign_defining_status: ArtifactReference;
}

interface NotPlannedTestDefinitionFromREST extends TestDefinitionFromRESTWithNoStatusInformation {
    readonly test_status: null;
    readonly test_execution_used_to_define_status: null;
    readonly test_campaign_defining_status: null;
}

export type TestDefinitionFromREST =
    | PlannedTestDefinitionFromREST
    | NotPlannedTestDefinitionFromREST;

interface TestDefinitionRefreshInformation {
    readonly is_just_refreshed: boolean;
}

export type TestDefinition = TestDefinitionFromREST & TestDefinitionRefreshInformation;

export interface TraceabilityMatrixRequirement {
    readonly id: number;
    readonly title: string;
}

export interface TraceabilityMatrixTest {
    readonly id: number;
    readonly title: string;
    readonly campaign: string;
    readonly status: ArtifactFieldValueStatus;
    readonly executed_by: string | null;
    readonly executed_on: string | null;
}

export interface TraceabilityMatrixElement {
    readonly requirement: TraceabilityMatrixRequirement;
    readonly tests: ReadonlyArray<TraceabilityMatrixTest>;
}

export interface ExportDocument {
    readonly name: string;
    readonly backlog: ReadonlyArray<FormattedArtifact>;
    readonly tests: ReadonlyArray<FormattedArtifact>;
    readonly traceability_matrix: ReadonlyArray<TraceabilityMatrixElement>;
}

export interface GlobalExportProperties {
    readonly platform_name: string;
    readonly platform_logo_url: string;
    readonly project_name: string;
    readonly user_display_name: string;
    readonly user_timezone: string;
    readonly user_locale: string;
    readonly milestone_name: string;
    readonly parent_milestone_name: string;
    readonly milestone_url: string;
    readonly base_url: string;
    readonly artifact_links_types: ReadonlyArray<ArtifactLinkType>;
    readonly testdefinition_tracker_id: number | null;
}

export interface DateTimeLocaleInformation {
    readonly locale: string;
    readonly timezone: string;
}

export type ExecutionsForCampaignMap = Map<
    number,
    {
        campaign: Campaign;
        executions: ReadonlyArray<TestExecutionResponse>;
    }
>;
