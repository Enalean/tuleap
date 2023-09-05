/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { createVueGettextProviderPassthrough } from "../../../vue-gettext-provider-for-test";
import type { PlannedTestCaseAssociatedWithTestExecAndCampaign } from "../get-planned-test-cases";
import { buildJustificationsSection } from "./justifications-builder";
import { DateCell, HTMLCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import * as artifacts_retriever from "./Tracker/artifacts-retriever";

describe("Build justifications section", () => {
    it("buids section", async () => {
        const gettext_provider = createVueGettextProviderPassthrough();

        const planned_test_cases: PlannedTestCaseAssociatedWithTestExecAndCampaign[] = [
            {
                campaign_id: 123,
                campaign_name: "Campaign name",
                test_case_id: 741,
                test_case_title: "Test case title",
                test_exec_id: 9999,
                test_exec_status: "passed",
                test_exec_internationalized_status: "Passed",
                test_exec_runner: "Some user",
                test_exec_date: new Date("2020-08-11T10:00:00.000Z"),
            },
            {
                campaign_id: 123,
                campaign_name: "Campaign name",
                test_case_id: 741,
                test_case_title: "Test case title 2",
                test_exec_id: 99992,
                test_exec_status: "failed",
                test_exec_internationalized_status: "Failed",
                test_exec_runner: "Some user",
                test_exec_date: new Date("2020-08-11T10:20:00.000Z"),
            },
            {
                campaign_id: 123,
                campaign_name: "Campaign name",
                test_case_id: 741,
                test_case_title: "Test case title 3",
                test_exec_id: 99993,
                test_exec_status: "failed",
                test_exec_internationalized_status: "Failed",
                test_exec_runner: "Some user",
                test_exec_date: new Date("2020-08-11T10:30:00.000Z"),
            },
        ];

        jest.spyOn(artifacts_retriever, "retrieveArtifacts").mockResolvedValue(
            new Map([
                [
                    99992,
                    {
                        id: 99992,
                        values_by_field: {
                            results: {
                                field_id: 444,
                                type: "text",
                                value: "HTML Comment",
                                format: "html",
                                label: "Results",
                            },
                        },
                        values: [],
                        tracker: {
                            id: 12,
                        },
                    },
                ],
                [
                    99993,
                    {
                        id: 99993,
                        values_by_field: {
                            results: {
                                field_id: 444,
                                type: "text",
                                value: "Plaintext Comment",
                                format: "text",
                                label: "Results",
                            },
                        },
                        values: [],
                        tracker: {
                            id: 12,
                        },
                    },
                ],
            ]),
        );

        const section = await buildJustificationsSection(gettext_provider, planned_test_cases);

        expect(section).toStrictEqual({
            title: new TextCell(
                "Justifications (for planned tests that are not-run, failed, blocked)",
            ),
            headers: [
                new TextCell("Test execution ID"),
                new TextCell("Test case ID"),
                new TextCell("Test case title"),
                new TextCell("Test execution status"),
                new TextCell("Test execution runner"),
                new TextCell("Test execution date"),
                new TextCell("Justification comment"),
            ],
            rows: [
                [
                    new TextCell("99992"),
                    new TextCell("741"),
                    new TextCell("Test case title 2"),
                    new TextCell("Failed"),
                    new TextCell("Some user"),
                    new DateCell(new Date("2020-08-11T10:20:00.000Z")),
                    new HTMLCell("HTML Comment"),
                ],
                [
                    new TextCell("99993"),
                    new TextCell("741"),
                    new TextCell("Test case title 3"),
                    new TextCell("Failed"),
                    new TextCell("Some user"),
                    new DateCell(new Date("2020-08-11T10:30:00.000Z")),
                    new TextCell("Plaintext Comment"),
                ],
            ],
        });
    });

    it("buids section even when a test exec artifact cannot be found", async () => {
        const gettext_provider = createVueGettextProviderPassthrough();

        const planned_test_cases: PlannedTestCaseAssociatedWithTestExecAndCampaign[] = [
            {
                campaign_id: 123,
                campaign_name: "Campaign name",
                test_case_id: 741,
                test_case_title: "Test case title 4",
                test_exec_id: 99994,
                test_exec_status: "failed",
                test_exec_internationalized_status: "Failed",
                test_exec_runner: "Some user",
                test_exec_date: new Date("2020-08-11T10:40:00.000Z"),
            },
        ];

        jest.spyOn(artifacts_retriever, "retrieveArtifacts").mockResolvedValue(new Map());

        const section = await buildJustificationsSection(gettext_provider, planned_test_cases);

        expect(section.rows).toStrictEqual([
            [
                new TextCell("99994"),
                new TextCell("741"),
                new TextCell("Test case title 4"),
                new TextCell("Failed"),
                new TextCell("Some user"),
                new DateCell(new Date("2020-08-11T10:40:00.000Z")),
                new TextCell(""),
            ],
        ]);
    });

    it("buids section even when the results field cannot be found on a test exec", async () => {
        const gettext_provider = createVueGettextProviderPassthrough();

        const planned_test_cases: PlannedTestCaseAssociatedWithTestExecAndCampaign[] = [
            {
                campaign_id: 123,
                campaign_name: "Campaign name",
                test_case_id: 741,
                test_case_title: "Test case title 5",
                test_exec_id: 99995,
                test_exec_status: "failed",
                test_exec_internationalized_status: "Failed",
                test_exec_runner: "Some user",
                test_exec_date: new Date("2020-08-11T10:50:00.000Z"),
            },
        ];

        jest.spyOn(artifacts_retriever, "retrieveArtifacts").mockResolvedValue(
            new Map([[99995, { id: 99995, values_by_field: {}, values: [], tracker: { id: 12 } }]]),
        );

        const section = await buildJustificationsSection(gettext_provider, planned_test_cases);

        expect(section.rows).toStrictEqual([
            [
                new TextCell("99995"),
                new TextCell("741"),
                new TextCell("Test case title 5"),
                new TextCell("Failed"),
                new TextCell("Some user"),
                new DateCell(new Date("2020-08-11T10:50:00.000Z")),
                new TextCell(""),
            ],
        ]);
    });
});
