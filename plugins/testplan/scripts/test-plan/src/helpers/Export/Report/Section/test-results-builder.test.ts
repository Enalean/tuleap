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
import { PlannedTestCaseAssociatedWithTestExecAndCampaign } from "../get-planned-test-cases";
import { buildTestResultsSection } from "./test-results-builder";
import { DateCell, TextCell } from "../report-cells";

describe("Build test results section", () => {
    it("buids section", () => {
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
        ];

        const section = buildTestResultsSection(gettext_provider, planned_test_cases);

        expect(section).toStrictEqual({
            title: new TextCell("Test Results (planned tests)"),
            headers: [
                new TextCell("Campaign ID"),
                new TextCell("Campaign Name"),
                new TextCell("Test case ID"),
                new TextCell("Test case title"),
                new TextCell("Test execution ID"),
                new TextCell("Test execution status"),
                new TextCell("Test execution runner"),
                new TextCell("Test execution date"),
            ],
            rows: [
                [
                    new TextCell("123"),
                    new TextCell("Campaign name"),
                    new TextCell("741"),
                    new TextCell("Test case title"),
                    new TextCell("9999"),
                    new TextCell("Passed"),
                    new TextCell("Some user"),
                    new DateCell(new Date("2020-08-11T10:00:00.000Z")),
                ],
                [
                    new TextCell("123"),
                    new TextCell("Campaign name"),
                    new TextCell("741"),
                    new TextCell("Test case title 2"),
                    new TextCell("99992"),
                    new TextCell("Failed"),
                    new TextCell("Some user"),
                    new DateCell(new Date("2020-08-11T10:20:00.000Z")),
                ],
            ],
        });
    });
});
