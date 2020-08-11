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

import { PlannedTestCaseAssociatedWithTestExecAndCampaign } from "./get-planned-test-cases";
import { DateCell, TextCell } from "./report-cells";

type JustificationsSectionRow = readonly [
    TextCell,
    TextCell,
    TextCell,
    TextCell,
    TextCell,
    DateCell
];

export interface JustificationsSection {
    readonly title: TextCell;
    readonly headers: readonly [TextCell, TextCell, TextCell, TextCell, TextCell, TextCell];
    readonly rows: ReadonlyArray<JustificationsSectionRow>;
}

export function buildJustificationsSection(
    gettext_provider: VueGettextProvider,
    planned_test_cases: ReadonlyArray<PlannedTestCaseAssociatedWithTestExecAndCampaign>
): JustificationsSection {
    const rows = planned_test_cases
        .filter(
            (value: PlannedTestCaseAssociatedWithTestExecAndCampaign): boolean =>
                value.test_exec_status !== "passed"
        )
        .map(
            (
                not_passed_test_case: PlannedTestCaseAssociatedWithTestExecAndCampaign
            ): JustificationsSectionRow => {
                return [
                    new TextCell(String(not_passed_test_case.test_exec_id)),
                    new TextCell(String(not_passed_test_case.test_case_id)),
                    new TextCell(not_passed_test_case.test_case_title),
                    new TextCell(not_passed_test_case.test_exec_internationalized_status),
                    new TextCell(not_passed_test_case.test_exec_runner),
                    new DateCell(not_passed_test_case.test_exec_date),
                ];
            }
        );

    return {
        title: new TextCell(
            gettext_provider.$gettext(
                "Justifications (for planned tests that are not-run, failed, blocked)"
            )
        ),
        headers: [
            new TextCell(gettext_provider.$gettext("Test execution ID")),
            new TextCell(gettext_provider.$gettext("Test case ID")),
            new TextCell(gettext_provider.$gettext("Test case title")),
            new TextCell(gettext_provider.$gettext("Test execution status")),
            new TextCell(gettext_provider.$gettext("Test execution runner")),
            new TextCell(gettext_provider.$gettext("Test execution date")),
        ],
        rows,
    };
}
