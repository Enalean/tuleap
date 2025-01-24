/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { ArtifactsTableBuilder } from "../../../api/ArtifactsTableBuilder";
import { SelectableReportContentRepresentationStub } from "../../../../tests/builders/SelectableReportContentRepresentationStub";
import {
    NUMERIC_SELECTABLE_TYPE,
    PROJECT_SELECTABLE_TYPE,
    TEXT_SELECTABLE_TYPE,
    TRACKER_SELECTABLE_TYPE,
    USER_SELECTABLE_TYPE,
} from "../../../api/cross-tracker-rest-api-types";
import { ArtifactRepresentationStub } from "../../../../tests/builders/ArtifactRepresentationStub";
import { ARTIFACT_COLUMN_NAME } from "../../../domain/ColumnName";

import { describe, expect, it } from "vitest";
import type { ReportSection } from "./data-formater";
import { formatData } from "./data-formater";
import { NumberCell, TextCell, EmptyCell, HTMLCell } from "@tuleap/plugin-docgen-xlsx";

describe("data-formater", () => {
    const artifact_column = ARTIFACT_COLUMN_NAME;
    const first_artifact_uri = "/plugins/tracker/?aid=540";
    const second_artifact_uri = "/plugins/tracker/?aid=435";
    const third_artifact_uri = "/plugins/tracker/?aid=4130";

    const project_column = "Project";
    const project_name = "CT4-V Blackwing";
    const other_project_name = "Charger SRT Hellcat Redeye";

    const numeric_column = "remaining_effort";
    const float_value = 15.2;
    const int_value = 10;

    const text_column = "Engine";
    const first_text = "3.6L V6";
    const second_text = "6.2L V8";

    const user_column = "User";
    const first_user = "Cadillac";
    const second_user = "Dodge";

    const tracker_column = "Tracker";
    const first_tracker = "Twin-Turbo";
    const second_tracker = "Supercharged";

    it("generates the formatted data with that will be used to create the XLSX document with rows", () => {
        const table = [
            ArtifactsTableBuilder().mapReportToArtifactsTable(
                SelectableReportContentRepresentationStub.build(
                    [
                        { type: NUMERIC_SELECTABLE_TYPE, name: numeric_column },
                        { type: PROJECT_SELECTABLE_TYPE, name: project_column },
                        { type: TEXT_SELECTABLE_TYPE, name: text_column },
                        { type: USER_SELECTABLE_TYPE, name: user_column },
                        { type: TRACKER_SELECTABLE_TYPE, name: tracker_column },
                    ],
                    [
                        ArtifactRepresentationStub.build({
                            [artifact_column]: { uri: first_artifact_uri },
                            [numeric_column]: { value: float_value },
                            [project_column]: { name: project_name, icon: "" },
                            [text_column]: { value: first_text },
                            [user_column]: { display_name: first_user, user_url: null },
                            [tracker_column]: {
                                name: first_tracker,
                                color: "tlp-swatch-fiesta-red",
                            },
                        }),
                        ArtifactRepresentationStub.build({
                            [artifact_column]: { uri: second_artifact_uri },
                            [numeric_column]: { value: int_value },
                            [project_column]: { name: project_name, icon: "" },
                            [text_column]: { value: "" },
                            [user_column]: { display_name: first_user, user_url: null },
                            [tracker_column]: {
                                name: first_tracker,
                                color: "tlp-swatch-fiesta-red",
                            },
                        }),
                        ArtifactRepresentationStub.build({
                            [artifact_column]: { uri: third_artifact_uri },
                            [numeric_column]: { value: null },
                            [project_column]: { name: other_project_name, icon: "" },
                            [text_column]: { value: second_text },
                            [user_column]: { display_name: second_user, user_url: null },
                            [tracker_column]: {
                                name: second_tracker,
                                color: "tlp-swatch-deep-blue",
                            },
                        }),
                    ],
                ),
            ),
        ];
        const result = formatData(table);

        const report_cell_result: ReportSection = {
            headers: [
                new TextCell(numeric_column),
                new TextCell(project_column),
                new TextCell(text_column),
                new TextCell(user_column),
                new TextCell(tracker_column),
            ],
            rows: [
                [
                    new NumberCell(float_value),
                    new TextCell(project_name),
                    new HTMLCell(first_text),
                    new TextCell(first_user),
                    new TextCell(first_tracker),
                ],
                [
                    new NumberCell(int_value),
                    new TextCell(project_name),
                    new HTMLCell(""),
                    new TextCell(first_user),
                    new TextCell(first_tracker),
                ],
                [
                    new EmptyCell(),
                    new TextCell(other_project_name),
                    new HTMLCell(second_text),
                    new TextCell(second_user),
                    new TextCell(second_tracker),
                ],
            ],
        };
        expect(report_cell_result).toStrictEqual(result);
    });
});
