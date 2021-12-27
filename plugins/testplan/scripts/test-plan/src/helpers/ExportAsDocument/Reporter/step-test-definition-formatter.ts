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
    TransformStepDefFieldValue,
    ArtifactReportResponseStepDefinitionFieldValue,
    ArtifactFieldValueStepDefinitionEnhanced,
    ArtifactFromReport,
    TestExecutionResponse,
} from "@tuleap/plugin-docgen-docx";
import type {
    ArtifactFieldValueStepDefinitionEnhancedWithResults,
    ExecutionsForCampaignMap,
} from "../../../type";

export function buildStepDefinitionFunction(): TransformStepDefFieldValue<ArtifactFieldValueStepDefinitionEnhancedWithResults> {
    return (
        value: ArtifactReportResponseStepDefinitionFieldValue
    ): ArtifactFieldValueStepDefinitionEnhancedWithResults => {
        const steps: ArtifactFieldValueStepDefinitionEnhanced[] = [];
        for (const step of value.value) {
            steps.push({
                description: step.description,
                description_format: step.description_format === "html" ? "html" : "plaintext",
                expected_results: step.expected_results,
                expected_results_format:
                    step.expected_results_format === "html" ? "html" : "plaintext",
                rank: step.rank,
                status: null,
            });
        }
        return {
            field_name: value.label,
            content_length: "blockttmstepdef",
            value_type: "string",
            steps: steps,
        };
    };
}

export function buildStepDefinitionEnhancedWithResultsFunction(
    artifact: ArtifactFromReport,
    executions_map: ExecutionsForCampaignMap
): TransformStepDefFieldValue<ArtifactFieldValueStepDefinitionEnhancedWithResults> {
    let execution_for_test: TestExecutionResponse | null = null;
    for (const { executions } of executions_map.values()) {
        for (const exec of executions) {
            if (exec.definition.id === artifact.id) {
                execution_for_test = exec;
                break;
            }
        }
    }

    return (
        value: ArtifactReportResponseStepDefinitionFieldValue
    ): ArtifactFieldValueStepDefinitionEnhancedWithResults => {
        const steps: ArtifactFieldValueStepDefinitionEnhanced[] = [];
        for (const step of value.value) {
            let step_status = null;
            if (
                execution_for_test !== null &&
                step.id.toString() in execution_for_test.steps_results
            ) {
                step_status = execution_for_test.steps_results[step.id.toString()].status;
            }

            steps.push({
                description: step.description,
                description_format: step.description_format === "html" ? "html" : "plaintext",
                expected_results: step.expected_results,
                expected_results_format:
                    step.expected_results_format === "html" ? "html" : "plaintext",
                rank: step.rank,
                status: step_status,
            });
        }
        return {
            field_name: value.label,
            content_length: "blockttmstepdefenhanced",
            value_type: "string",
            steps: steps,
        };
    };
}
