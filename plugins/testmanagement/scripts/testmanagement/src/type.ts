/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    FormattedArtifact,
    TestExecutionResponse,
} from "@tuleap/plugin-docgen-docx";
import type {
    ArtifactFieldValueStepDefinitionEnhanced,
    TestExecutionAttachment,
    TestExecutionLinkedBug,
} from "@tuleap/plugin-docgen-docx";
import type { ArtifactLinkType } from "@tuleap/plugin-docgen-docx";

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

export interface GenericGlobalExportProperties {
    readonly platform_name: string;
    readonly platform_logo_url: string;
    readonly project_name: string;
    readonly user_display_name: string;
    readonly user_timezone: string;
    readonly user_locale: string;
    readonly base_url: string;
    readonly artifact_links_types: ReadonlyArray<ArtifactLinkType>;
    readonly testdefinition_tracker_id: number | null;
    readonly title: string;
}

export interface GlobalExportProperties extends GenericGlobalExportProperties {
    readonly campaign_name: string;
    readonly campaign_url: string;
}

export interface DateTimeLocaleInformation {
    readonly locale: string;
    readonly timezone: string;
}

export interface ExportDocument<StepDefFieldValue> {
    readonly name: string;
    readonly backlog: ReadonlyArray<FormattedArtifact<StepDefFieldValue>>;
    readonly tests: ReadonlyArray<FormattedArtifact<StepDefFieldValue>>;
    readonly traceability_matrix: ReadonlyArray<TraceabilityMatrixElement>;
}

export interface TraceabilityMatrixRequirement {
    readonly id: number;
    readonly title: string;
    readonly tracker_id: number;
}

export interface TraceabilityMatrixTest {
    readonly id: number;
    readonly title: string;
    readonly campaign: string;
    readonly status: ArtifactFieldValueStatus;
    readonly executed_by: string | null;
    readonly executed_on: string | null;
    readonly executed_on_date: Date | null;
}

export interface TraceabilityMatrixElement {
    readonly requirement: TraceabilityMatrixRequirement;
    readonly tests: Map<number, TraceabilityMatrixTest>;
}

export type ExecutionsForCampaignMap = Map<
    number,
    {
        campaign: Campaign;
        executions: ReadonlyArray<TestExecutionResponse>;
    }
>;

export type LastExecutionsMap = Map<number, TestExecutionResponse>;

export interface ArtifactFieldValueStepDefinitionEnhancedWithResults {
    readonly field_name: string;
    readonly content_length: "blockttmstepdef" | "blockttmstepdefenhanced";
    readonly value_type: "string";
    readonly steps: Array<ArtifactFieldValueStepDefinitionEnhanced>;
    readonly status: ArtifactFieldValueStatus;
    readonly result: string;
    readonly attachments: ReadonlyArray<TestExecutionAttachment>;
    readonly linked_bugs: ReadonlyArray<TestExecutionLinkedBug & { readonly html_url: string }>;
    readonly last_execution_date: string;
    readonly last_execution_user: string;
}
