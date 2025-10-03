/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { ArtifactsTableBuilder } from "../../../../api/ArtifactsTableBuilder";
import { SelectableQueryContentRepresentationStub } from "../../../../../tests/builders/SelectableQueryContentRepresentationStub";
import {
    DATE_SELECTABLE_TYPE,
    NUMERIC_SELECTABLE_TYPE,
    PRETTY_TITLE_SELECTABLE_TYPE,
    PROJECT_SELECTABLE_TYPE,
    STATIC_LIST_SELECTABLE_TYPE,
    TEXT_SELECTABLE_TYPE,
    TRACKER_SELECTABLE_TYPE,
    USER_GROUP_LIST_SELECTABLE_TYPE,
    USER_LIST_SELECTABLE_TYPE,
    USER_SELECTABLE_TYPE,
} from "../../../../api/cross-tracker-rest-api-types";
import { ArtifactRepresentationStub } from "../../../../../tests/builders/ArtifactRepresentationStub";
import { ARTIFACT_COLUMN_NAME } from "../../../../domain/ColumnName";

import { describe, expect, it } from "vitest";
import type { ContentSection } from "./data-formater";
import { formatData } from "./data-formater";
import { NumberCell, TextCell, EmptyCell, HTMLCell, DateCell } from "@tuleap/plugin-docgen-xlsx";
import { ColumnNameGetter } from "../../../../domain/ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../../../vue-gettext-provider-for-test";
import { NO_DIRECTION } from "../../../../domain/ArtifactsTable";

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

    const user_list_column = "User Comp List";
    const second_user_in_list = "Buick";
    const first_user_list = [
        { display_name: first_user, user_url: null },
        { display_name: second_user_in_list, user_url: null },
    ];
    const second_user_in_second_list = "Fiat";
    const second_user_list = [
        { display_name: second_user, user_url: null },
        { display_name: second_user_in_second_list, user_url: null },
    ];

    const user_group_column = "Group";
    const first_user_group = "GM";
    const second_user_group = "FCA";
    const third_user_group = "PSA";
    const first_user_group_list = [{ label: first_user_group }];
    const second_user_group_list = [{ label: second_user_group }, { label: third_user_group }];

    const date_column = "Date";
    const first_date = "2024-09-03T00:00:00+02:00";
    const datetime_column = "Date time";
    const first_datetime = "2024-09-24T15:55:00+02:00";

    const static_list_column = "Static list Column";
    const first_value = { label: "AWD", color: null };
    const second_value = { label: "RWD", color: null };

    const pretty_title_column = "Artifact";
    const first_pretty_title = {
        tracker_name: first_tracker,
        color: null,
        artifact_id: 6,
        title: "Boost 1.2bar",
    };
    const second_pretty_title = {
        tracker_name: first_tracker,
        color: null,
        artifact_id: 8,
        title: "Fuel Injector",
    };
    const third_pretty_title = {
        tracker_name: second_tracker,
        color: null,
        artifact_id: 8,
        title: "Big Rotary-screw compressor",
    };

    it("generates the formatted data with that will be used to create the XLSX document with rows", () => {
        const table = [
            ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [
                        { type: NUMERIC_SELECTABLE_TYPE, name: numeric_column },
                        { type: PROJECT_SELECTABLE_TYPE, name: project_column },
                        { type: TEXT_SELECTABLE_TYPE, name: text_column },
                        { type: USER_SELECTABLE_TYPE, name: user_column },
                        { type: TRACKER_SELECTABLE_TYPE, name: tracker_column },
                        { type: USER_LIST_SELECTABLE_TYPE, name: user_list_column },
                        { type: USER_GROUP_LIST_SELECTABLE_TYPE, name: user_group_column },
                        { type: DATE_SELECTABLE_TYPE, name: date_column },
                        { type: DATE_SELECTABLE_TYPE, name: datetime_column },
                        { type: STATIC_LIST_SELECTABLE_TYPE, name: static_list_column },
                        { type: PRETTY_TITLE_SELECTABLE_TYPE, name: pretty_title_column },
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
                            [user_list_column]: { value: first_user_list },
                            [user_group_column]: { value: first_user_group_list },
                            [date_column]: { value: first_date, with_time: false },
                            [datetime_column]: { value: null, with_time: false },
                            [static_list_column]: {
                                value: [first_value, second_value],
                            },
                            [pretty_title_column]: first_pretty_title,
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
                            [user_list_column]: { value: first_user_list },
                            [user_group_column]: { value: first_user_group_list },
                            [date_column]: { value: null, with_time: false },
                            [datetime_column]: { value: null, with_time: false },
                            [static_list_column]: { value: [] },
                            [pretty_title_column]: second_pretty_title,
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
                            [user_list_column]: { value: second_user_list },
                            [user_group_column]: {
                                value: second_user_group_list,
                            },
                            [date_column]: { value: null, with_time: false },
                            [datetime_column]: { value: first_datetime, with_time: true },
                            [static_list_column]: { value: [second_value] },
                            [pretty_title_column]: third_pretty_title,
                        }),
                    ],
                ),
                NO_DIRECTION,
            ),
        ];
        const result = formatData(table, ColumnNameGetter(createVueGettextProviderPassThrough()));

        const content_cell_result: ContentSection = {
            headers: [
                new TextCell(numeric_column),
                new TextCell(project_column),
                new TextCell(text_column),
                new TextCell(user_column),
                new TextCell(tracker_column),
                new TextCell(user_list_column),
                new TextCell(user_group_column),
                new TextCell(date_column),
                new TextCell(datetime_column),
                new TextCell(static_list_column),
                new TextCell(pretty_title_column),
            ],
            rows: [
                [
                    new NumberCell(float_value),
                    new TextCell(project_name),
                    new HTMLCell(first_text),
                    new TextCell(first_user),
                    new TextCell(first_tracker),
                    new TextCell(first_user + ", " + second_user_in_list),
                    new TextCell(first_user_group),
                    new DateCell(first_date),
                    new EmptyCell(),
                    new TextCell(first_value.label + ", " + second_value.label),
                    new TextCell(
                        first_pretty_title.tracker_name +
                            "#" +
                            first_pretty_title.artifact_id +
                            " " +
                            first_pretty_title.title,
                    ),
                ],
                [
                    new NumberCell(int_value),
                    new TextCell(project_name),
                    new HTMLCell(""),
                    new TextCell(first_user),
                    new TextCell(first_tracker),
                    new TextCell(first_user + ", " + second_user_in_list),
                    new TextCell(first_user_group),
                    new EmptyCell(),
                    new EmptyCell(),
                    new TextCell(""),
                    new TextCell(
                        second_pretty_title.tracker_name +
                            "#" +
                            second_pretty_title.artifact_id +
                            " " +
                            second_pretty_title.title,
                    ),
                ],
                [
                    new EmptyCell(),
                    new TextCell(other_project_name),
                    new HTMLCell(second_text),
                    new TextCell(second_user),
                    new TextCell(second_tracker),
                    new TextCell(second_user + ", " + second_user_in_second_list),
                    new TextCell(second_user_group + ", " + third_user_group),
                    new EmptyCell(),
                    new DateCell(first_datetime),
                    new TextCell(second_value.label),
                    new TextCell(
                        third_pretty_title.tracker_name +
                            "#" +
                            third_pretty_title.artifact_id +
                            " " +
                            third_pretty_title.title,
                    ),
                ],
            ],
        };
        expect(content_cell_result).toStrictEqual(result);
    });
});
