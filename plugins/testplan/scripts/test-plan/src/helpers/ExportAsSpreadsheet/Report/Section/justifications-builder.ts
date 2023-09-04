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

import type { PlannedTestCaseAssociatedWithTestExecAndCampaign } from "../get-planned-test-cases";
import type { HTMLCell } from "@tuleap/plugin-docgen-xlsx";
import { DateCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import { retrieveArtifacts } from "./Tracker/artifacts-retriever";
import { transformTextFieldValueIntoACell } from "./transform-field-value-into-cell";
import type { Artifact } from "./Tracker/artifact";
import type { VueGettextProvider } from "../../../vue-gettext-provider";

const TEST_EXEC_RESULT_FIELD_COMMENT_NAME = "results";

type JustificationsSectionRow = readonly [
    TextCell,
    TextCell,
    TextCell,
    TextCell,
    TextCell,
    DateCell,
    HTMLCell | TextCell,
];

export interface JustificationsSection {
    readonly title: TextCell;
    readonly headers: readonly [
        TextCell,
        TextCell,
        TextCell,
        TextCell,
        TextCell,
        TextCell,
        TextCell,
    ];
    readonly rows: ReadonlyArray<JustificationsSectionRow>;
}

export async function buildJustificationsSection(
    gettext_provider: VueGettextProvider,
    planned_test_cases: ReadonlyArray<PlannedTestCaseAssociatedWithTestExecAndCampaign>,
): Promise<JustificationsSection> {
    const non_passed_test_cases = planned_test_cases.filter(
        (value: PlannedTestCaseAssociatedWithTestExecAndCampaign): boolean =>
            value.test_exec_status !== "passed",
    );

    const full_artifact_non_passed_test_execs: ReadonlyMap<number, Artifact> =
        await retrieveArtifacts(
            non_passed_test_cases.map(
                (test_case: PlannedTestCaseAssociatedWithTestExecAndCampaign): number =>
                    test_case.test_exec_id,
            ),
        );

    const rows = non_passed_test_cases.map(
        (
            not_passed_test_case: PlannedTestCaseAssociatedWithTestExecAndCampaign,
        ): JustificationsSectionRow => {
            return [
                new TextCell(String(not_passed_test_case.test_exec_id)),
                new TextCell(String(not_passed_test_case.test_case_id)),
                new TextCell(not_passed_test_case.test_case_title),
                new TextCell(not_passed_test_case.test_exec_internationalized_status),
                new TextCell(not_passed_test_case.test_exec_runner),
                new DateCell(not_passed_test_case.test_exec_date),
                findJustificationCommentCell(
                    not_passed_test_case.test_exec_id,
                    full_artifact_non_passed_test_execs,
                ),
            ];
        },
    );

    return {
        title: new TextCell(
            gettext_provider.$gettext(
                "Justifications (for planned tests that are not-run, failed, blocked)",
            ),
        ),
        headers: [
            new TextCell(gettext_provider.$gettext("Test execution ID")),
            new TextCell(gettext_provider.$gettext("Test case ID")),
            new TextCell(gettext_provider.$gettext("Test case title")),
            new TextCell(gettext_provider.$gettext("Test execution status")),
            new TextCell(gettext_provider.$gettext("Test execution runner")),
            new TextCell(gettext_provider.$gettext("Test execution date")),
            new TextCell(gettext_provider.$gettext("Justification comment")),
        ],
        rows,
    };
}

function findJustificationCommentCell(
    test_exec_id: number,
    full_artifact_test_execs: ReadonlyMap<number, Artifact>,
): HTMLCell | TextCell {
    const test_exec = full_artifact_test_execs.get(test_exec_id);

    if (typeof test_exec === "undefined") {
        return new TextCell("");
    }

    const result_field = test_exec.values_by_field[TEST_EXEC_RESULT_FIELD_COMMENT_NAME];
    if (typeof result_field === "undefined" || result_field.type !== "text") {
        return new TextCell("");
    }

    return transformTextFieldValueIntoACell(result_field);
}
