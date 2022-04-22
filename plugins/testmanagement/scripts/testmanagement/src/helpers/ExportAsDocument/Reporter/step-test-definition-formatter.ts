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
        base_url: string,
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
            status: null,
            result: "",
            attachments: [],
            linked_bugs: [],
            last_execution_date: "",
            last_execution_user: "",
        };
    };
}

export function buildStepDefinitionEnhancedWithResultsFunction(
    artifact: ArtifactFromReport,
    executions_map: ExecutionsForCampaignMap
): TransformStepDefFieldValue<ArtifactFieldValueStepDefinitionEnhancedWithResults> {
    const execution_for_test = getLastExecutionForTest(artifact, executions_map);

    return (
        base_url: string,
        value: ArtifactReportResponseStepDefinitionFieldValue
    ): ArtifactFieldValueStepDefinitionEnhancedWithResults => {
        const steps: ArtifactFieldValueStepDefinitionEnhanced[] = [];
        let test_status = null;
        if (execution_for_test !== null) {
            test_status = execution_for_test.status;
        }

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
                status: step_status ?? "notrun",
            });
        }

        const attachments = (execution_for_test?.attachments ?? []).map((attachment) => {
            return {
                ...attachment,
                html_url: new URL(base_url.replace(/\/$/, "") + attachment.html_url).href,
            };
        });
        const linked_bugs = (execution_for_test?.linked_bugs ?? []).map((bug) => {
            return {
                ...bug,
                html_url: new URL(base_url.replace(/\/$/, "") + "/plugins/tracker/?aid=" + bug.id)
                    .href,
            };
        });

        return {
            field_name: value.label,
            content_length: "blockttmstepdefenhanced",
            value_type: "string",
            steps: steps,
            status: test_status ?? "notrun",
            result: execution_for_test?.previous_result?.result ?? "",
            attachments,
            linked_bugs,
            last_execution_date: execution_for_test?.previous_result?.submitted_on ?? "",
            last_execution_user:
                execution_for_test?.previous_result?.submitted_by.display_name ?? "",
        };
    };
}

function getLastExecutionForTest(
    artifact: ArtifactFromReport,
    executions_map: ExecutionsForCampaignMap
): TestExecutionResponse | null {
    let execution_for_test: TestExecutionResponse | null = null;
    const all_execution_for_test: TestExecutionResponse[] = [];

    for (const { executions } of executions_map.values()) {
        for (const exec of executions) {
            if (exec.definition.id === artifact.id) {
                all_execution_for_test.push(exec);
            }
        }
    }

    if (all_execution_for_test.length === 0) {
        return null;
    }

    if (all_execution_for_test.length === 1) {
        return all_execution_for_test[0];
    }

    let higher_found_execution_date: Date | null = null;
    for (const execution of all_execution_for_test) {
        if (execution.previous_result === null) {
            if (execution_for_test === null) {
                execution_for_test = execution;
            }
        } else {
            const current_execution_date = new Date(execution.previous_result.submitted_on);
            if (
                higher_found_execution_date === null ||
                current_execution_date > higher_found_execution_date
            ) {
                execution_for_test = execution;
                higher_found_execution_date = current_execution_date;
            }
        }
    }

    return execution_for_test;
}
