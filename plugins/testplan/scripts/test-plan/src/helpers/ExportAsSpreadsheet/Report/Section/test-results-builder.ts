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

import { DateCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { PlannedTestCaseAssociatedWithTestExecAndCampaign } from "../get-planned-test-cases";
import type { VueGettextProvider } from "../../../vue-gettext-provider";

type TestResultsSectionRow = readonly [
    TextCell,
    TextCell,
    TextCell,
    TextCell,
    TextCell,
    TextCell,
    TextCell,
    DateCell,
];

export interface TestResultsSection {
    readonly title: TextCell;
    readonly headers: readonly [
        TextCell,
        TextCell,
        TextCell,
        TextCell,
        TextCell,
        TextCell,
        TextCell,
        TextCell,
    ];
    readonly rows: ReadonlyArray<TestResultsSectionRow>;
}

export function buildTestResultsSection(
    gettext_provider: VueGettextProvider,
    planned_test_cases: ReadonlyArray<PlannedTestCaseAssociatedWithTestExecAndCampaign>,
): TestResultsSection {
    const rows = planned_test_cases.map(
        (
            planned_test_case: PlannedTestCaseAssociatedWithTestExecAndCampaign,
        ): TestResultsSectionRow => {
            return [
                new TextCell(String(planned_test_case.campaign_id)),
                new TextCell(planned_test_case.campaign_name),
                new TextCell(String(planned_test_case.test_case_id)),
                new TextCell(planned_test_case.test_case_title),
                new TextCell(String(planned_test_case.test_exec_id)),
                new TextCell(planned_test_case.test_exec_internationalized_status),
                new TextCell(planned_test_case.test_exec_runner),
                new DateCell(planned_test_case.test_exec_date),
            ];
        },
    );

    return {
        title: new TextCell(gettext_provider.$gettext("Test Results (planned tests)")),
        headers: [
            new TextCell(gettext_provider.$gettext("Campaign ID")),
            new TextCell(gettext_provider.$gettext("Campaign Name")),
            new TextCell(gettext_provider.$gettext("Test case ID")),
            new TextCell(gettext_provider.$gettext("Test case title")),
            new TextCell(gettext_provider.$gettext("Test execution ID")),
            new TextCell(gettext_provider.$gettext("Test execution status")),
            new TextCell(gettext_provider.$gettext("Test execution runner")),
            new TextCell(gettext_provider.$gettext("Test execution date")),
        ],
        rows,
    };
}
