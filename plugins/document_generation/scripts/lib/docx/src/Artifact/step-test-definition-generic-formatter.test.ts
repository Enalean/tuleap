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

import { describe, it, expect } from "vitest";
import type { ArtifactReportResponseStepDefinitionFieldValue } from "../type";
import { formatStepDefinitionField } from "./step-test-definition-generic-formatter";

describe("step-test-definition-generic-formatter", () => {
    it("should build a function that returns the step definition field value", () => {
        const field_value = {
            field_id: 24,
            type: "ttmstepdef",
            label: "Step Definition",
            value: [
                {
                    id: 13,
                    description: "01",
                    description_format: "text",
                    expected_results: "01",
                    expected_results_format: "text",
                    rank: 1,
                },
                {
                    id: 14,
                    description: "This is text",
                    description_format: "text",
                    expected_results: "text\nwith\nnewlines",
                    expected_results_format: "text",
                    rank: 2,
                },
                {
                    id: 15,
                    description: "<p>This is HTML</p>",
                    description_format: "html",
                    expected_results: "<p>HTML</p>\n\n<p>with</p>\n\n<p>newlines</p>",
                    expected_results_format: "html",
                    rank: 3,
                },
            ],
        } as ArtifactReportResponseStepDefinitionFieldValue;

        const enhanced_step_def_value = formatStepDefinitionField(
            "https://example.com",
            field_value,
        );

        expect(enhanced_step_def_value).toEqual({
            field_name: "Step Definition",
            content_length: "blockttmstepdef",
            value_type: "string",
            steps: [
                {
                    description: "01",
                    description_format: "plaintext",
                    expected_results: "01",
                    expected_results_format: "plaintext",
                    rank: 1,
                },
                {
                    description: "This is text",
                    description_format: "plaintext",
                    expected_results: "text\nwith\nnewlines",
                    expected_results_format: "plaintext",
                    rank: 2,
                },
                {
                    description: "<p>This is HTML</p>",
                    description_format: "html",
                    expected_results: "<p>HTML</p>\n\n<p>with</p>\n\n<p>newlines</p>",
                    expected_results_format: "html",
                    rank: 3,
                },
            ],
        });
    });
});
