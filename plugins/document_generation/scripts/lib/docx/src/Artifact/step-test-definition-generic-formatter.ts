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
    ArtifactFieldValueStepDefinition,
    ArtifactFieldValueStepDefinitionContent,
    ArtifactReportResponseStepDefinitionFieldValue,
} from "../type";
export function formatStepDefinitionField(
    base_url: string,
    value: ArtifactReportResponseStepDefinitionFieldValue,
): ArtifactFieldValueStepDefinitionContent {
    const steps: ArtifactFieldValueStepDefinition[] = [];
    for (const step of value.value) {
        steps.push({
            description: step.description,
            description_format: step.description_format === "html" ? "html" : "plaintext",
            expected_results: step.expected_results,
            expected_results_format: step.expected_results_format === "html" ? "html" : "plaintext",
            rank: step.rank,
        });
    }
    return {
        field_name: value.label,
        content_length: "blockttmstepdef",
        value_type: "string",
        steps: steps,
    };
}
