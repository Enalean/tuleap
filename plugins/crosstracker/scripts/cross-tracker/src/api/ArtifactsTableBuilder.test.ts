/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import {
    ARTIFACT_SELECTABLE_TYPE,
    DATE_SELECTABLE_TYPE,
    LINK_TYPE_SELECTABLE_TYPE,
    NUMERIC_SELECTABLE_TYPE,
    PRETTY_TITLE_SELECTABLE_TYPE,
    PROJECT_SELECTABLE_TYPE,
    STATIC_LIST_SELECTABLE_TYPE,
    TEXT_SELECTABLE_TYPE,
    TRACKER_SELECTABLE_TYPE,
    UNKNOWN_SELECTABLE_TYPE,
    USER_GROUP_LIST_SELECTABLE_TYPE,
    USER_LIST_SELECTABLE_TYPE,
    USER_SELECTABLE_TYPE,
} from "./cross-tracker-rest-api-types";
import { ArtifactsTableBuilder } from "./ArtifactsTableBuilder";
import {
    DATE_CELL,
    FORWARD_DIRECTION,
    LINK_TYPE_CELL,
    NO_DIRECTION,
    NUMERIC_CELL,
    PRETTY_TITLE_CELL,
    PROJECT_CELL,
    REVERSE_DIRECTION,
    STATIC_LIST_CELL,
    TEXT_CELL,
    TRACKER_CELL,
    UNKNOWN_CELL,
    USER_CELL,
    USER_GROUP_LIST_CELL,
    USER_LIST_CELL,
} from "../domain/ArtifactsTable";
import {
    ARTIFACT_COLUMN_NAME,
    ASSIGNED_TO_COLUMN_NAME,
    LINK_TYPE_COLUMN_NAME,
    PRETTY_TITLE_COLUMN_NAME,
    PROJECT_COLUMN_NAME,
    STATUS_COLUMN_NAME,
    SUBMITTED_BY_COLUMN_NAME,
    TRACKER_COLUMN_NAME,
} from "../domain/ColumnName";
import { SelectableQueryContentRepresentationStub } from "../../tests/builders/SelectableQueryContentRepresentationStub";
import { ArtifactRepresentationStub } from "../../tests/builders/ArtifactRepresentationStub";

describe(`ArtifactsTableBuilder`, () => {
    describe(`mapQueryContentToArtifactsTable()`, () => {
        const artifact_column = ARTIFACT_COLUMN_NAME;

        it(`will transform each selected name into a column name
            and for each artifact, it will create a Map from column name to its value
            so that it is easy to render the table of results for each column`, () => {
            const first_artifact_uri = "/plugins/tracker/?aid=540";
            const second_artifact_uri = "/plugins/tracker/?aid=435";

            const first_date = "2022-09-15T00:00:00+06:00";
            const second_date = "2018-09-23T23:26:36+09:00";
            const date_column = "start_date";

            const float_value = 15.2;
            const int_value = 10;
            const numeric_column = "remaining_effort";

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [
                        { type: DATE_SELECTABLE_TYPE, name: date_column },
                        { type: NUMERIC_SELECTABLE_TYPE, name: numeric_column },
                    ],
                    [
                        ArtifactRepresentationStub.build({
                            [artifact_column]: { uri: first_artifact_uri },
                            [date_column]: { value: first_date, with_time: false },
                            [numeric_column]: { value: float_value },
                        }),
                        ArtifactRepresentationStub.build({
                            [artifact_column]: { uri: second_artifact_uri },
                            [date_column]: { value: second_date, with_time: true },
                            [numeric_column]: { value: int_value },
                        }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns).toHaveLength(3);
            expect(table.columns.has(artifact_column)).toBe(true);
            expect(table.columns.has(date_column)).toBe(true);
            expect(table.columns.has(numeric_column)).toBe(true);

            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;

            expect(first_row.artifact_uri).toBe(first_artifact_uri);

            const date_value_first_row = first_row.cells.get(date_column);
            if (date_value_first_row?.type !== DATE_CELL) {
                throw Error("Expected to find first date value");
            }
            expect(date_value_first_row.value.unwrapOr(null)).toBe(first_date);

            const numeric_value_first_row = first_row.cells.get(numeric_column);
            if (numeric_value_first_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find first numeric value");
            }
            expect(numeric_value_first_row.value.unwrapOr(null)).toBe(float_value);

            expect(second_row.artifact_uri).toBe(second_artifact_uri);

            const date_value_second_row = second_row.cells.get(date_column);
            if (date_value_second_row?.type !== DATE_CELL) {
                throw Error("Expected to find second date value");
            }
            expect(date_value_second_row.value.unwrapOr(null)).toBe(second_date);

            const numeric_value_second_row = second_row.cells.get(numeric_column);
            if (numeric_value_second_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find second numeric value");
            }
            expect(numeric_value_second_row.value.unwrapOr(null)).toBe(int_value);
        });

        it(`builds a table with "date" selectables`, () => {
            const first_date = "2022-09-15T00:00:00+06:00";
            const second_date = "2018-09-23T23:26:36+09:00";
            const date_column = "start_date";

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: DATE_SELECTABLE_TYPE, name: date_column }],
                    [
                        ArtifactRepresentationStub.build({
                            [date_column]: { value: first_date, with_time: false },
                        }),
                        ArtifactRepresentationStub.build({
                            [date_column]: { value: second_date, with_time: true },
                        }),
                        ArtifactRepresentationStub.build({
                            [date_column]: { value: null, with_time: false },
                        }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(date_column)).toBe(true);
            expect(table.rows).toHaveLength(3);
            const [first_row, second_row, third_row] = table.rows;
            const date_value_first_row = first_row.cells.get(date_column);
            if (date_value_first_row?.type !== DATE_CELL) {
                throw Error("Expected to find first date value");
            }
            expect(date_value_first_row.value.unwrapOr(null)).toBe(first_date);
            expect(date_value_first_row.with_time).toBe(false);

            const date_value_second_row = second_row.cells.get(date_column);
            if (date_value_second_row?.type !== DATE_CELL) {
                throw Error("Expected to find second date value");
            }
            expect(date_value_second_row.value.unwrapOr(null)).toBe(second_date);
            expect(date_value_second_row.with_time).toBe(true);

            const date_value_third_row = third_row.cells.get(date_column);
            if (date_value_third_row?.type !== DATE_CELL) {
                throw Error("Expected to find third date value");
            }
            expect(date_value_third_row.value.isNothing()).toBe(true);
            expect(date_value_third_row.with_time).toBe(false);
        });

        it(`builds a table with "numeric" selectables`, () => {
            const float_value = 15.2;
            const int_value = 10;
            const numeric_column = "remaining_effort";

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: NUMERIC_SELECTABLE_TYPE, name: numeric_column }],
                    [
                        ArtifactRepresentationStub.build({
                            [numeric_column]: { value: float_value },
                        }),
                        ArtifactRepresentationStub.build({
                            [numeric_column]: { value: int_value },
                        }),
                        ArtifactRepresentationStub.build({ [numeric_column]: { value: null } }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(numeric_column)).toBe(true);
            expect(table.rows).toHaveLength(3);
            const [first_row, second_row, third_row] = table.rows;
            const numeric_value_first_row = first_row.cells.get(numeric_column);
            if (numeric_value_first_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find first numeric value");
            }
            expect(numeric_value_first_row.value.unwrapOr(null)).toBe(float_value);

            const numeric_value_second_row = second_row.cells.get(numeric_column);
            if (numeric_value_second_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find second numeric value");
            }
            expect(numeric_value_second_row.value.unwrapOr(null)).toBe(int_value);

            const numeric_value_third_row = third_row.cells.get(numeric_column);
            if (numeric_value_third_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find third numeric value");
            }
            expect(numeric_value_third_row.value.isNothing()).toBe(true);
        });

        it(`builds a table with "text" selectables`, () => {
            const text_value = "<p>Griffith II</p>";
            const text_column = "details";

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: TEXT_SELECTABLE_TYPE, name: text_column }],
                    [
                        ArtifactRepresentationStub.build({ [text_column]: { value: text_value } }),
                        ArtifactRepresentationStub.build({ [text_column]: { value: "" } }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(text_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const text_value_first_row = first_row.cells.get(text_column);
            if (text_value_first_row?.type !== TEXT_CELL) {
                throw Error("Expected to find first text value");
            }
            expect(text_value_first_row.value).toBe(text_value);

            const text_value_second_row = second_row.cells.get(text_column);
            if (text_value_second_row?.type !== TEXT_CELL) {
                throw Error("Expected to find second text value");
            }
            expect(text_value_second_row.value).toBe("");
        });

        it(`builds a table with "unknown" selectables`, () => {
            const unknown_column = "details";

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: UNKNOWN_SELECTABLE_TYPE, name: unknown_column }],
                    [
                        ArtifactRepresentationStub.build({
                            [unknown_column]: { value: "" },
                        }),
                        ArtifactRepresentationStub.build({ [unknown_column]: { value: "" } }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(unknown_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const unknown_value_first_row = first_row.cells.get(unknown_column);
            if (unknown_value_first_row?.type !== UNKNOWN_CELL) {
                throw Error("Expected to find first unknown value");
            }
            expect(unknown_value_first_row.value).toBe("");

            const unknown_value_second_row = second_row.cells.get(unknown_column);
            if (unknown_value_second_row?.type !== UNKNOWN_CELL) {
                throw Error("Expected to find second unknown value");
            }
            expect(unknown_value_second_row.value).toBe("");
        });
        it(`builds a table with "user" selectables`, () => {
            const first_user = {
                display_name: "Paula Muhammed (pmuhammed)",
                avatar_url: "https://example.com/users/pmuhammed/avatar.png",
                user_url: "/users/pmuhammed",
                is_anonymous: false,
            };

            const second_user = {
                display_name: "Anonymous user",
                avatar_url: "https://example.com/themes/common/images/avatar_default.png",
                user_url: null,
                is_anonymous: true,
            };
            const user_column = SUBMITTED_BY_COLUMN_NAME;

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: USER_SELECTABLE_TYPE, name: user_column }],
                    [
                        ArtifactRepresentationStub.build({ [user_column]: first_user }),
                        ArtifactRepresentationStub.build({ [user_column]: second_user }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(user_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const user_value_first_row = first_row.cells.get(user_column);
            if (user_value_first_row?.type !== USER_CELL) {
                throw Error("Expected to find first user value");
            }
            expect(user_value_first_row.display_name).toBe(first_user.display_name);
            expect(user_value_first_row.avatar_uri).toBe(first_user.avatar_url);
            expect(user_value_first_row.user_uri.unwrapOr(null)).toBe(first_user.user_url);

            const user_value_second_row = second_row.cells.get(user_column);
            if (user_value_second_row?.type !== USER_CELL) {
                throw Error("Expected to find second user value");
            }
            expect(user_value_second_row.display_name).toBe(second_user.display_name);
            expect(user_value_second_row.avatar_uri).toBe(second_user.avatar_url);
            expect(user_value_second_row.user_uri.isNothing()).toBe(true);
        });

        it(`builds a table with "list_static" selectables `, () => {
            const first_list_item = { label: "baggy", color: null };
            const second_list_item = { label: "Authorized", color: "sherwood-green" };
            const third_list_item = { label: "Restricted", color: "fiesta-red" };
            const list_column = STATUS_COLUMN_NAME;

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: STATIC_LIST_SELECTABLE_TYPE, name: list_column }],
                    [
                        ArtifactRepresentationStub.build({
                            [list_column]: { value: [first_list_item] },
                        }),
                        ArtifactRepresentationStub.build({
                            [list_column]: { value: [second_list_item, third_list_item] },
                        }),
                        ArtifactRepresentationStub.build({
                            [list_column]: { value: [] },
                        }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(list_column)).toBe(true);
            expect(table.rows).toHaveLength(3);
            const [first_row, second_row, third_row] = table.rows;
            const list_value_first_row = first_row.cells.get(list_column);
            if (list_value_first_row?.type !== STATIC_LIST_CELL) {
                throw Error("Expected to find first static list cell");
            }
            expect(list_value_first_row.value).toHaveLength(1);
            expect(list_value_first_row.value[0].label).toBe(first_list_item.label);
            expect(list_value_first_row.value[0].color.isNothing()).toBe(true);

            const list_value_second_row = second_row.cells.get(list_column);
            if (list_value_second_row?.type !== STATIC_LIST_CELL) {
                throw Error("Expected to find second static list cell");
            }
            expect(list_value_second_row.value).toHaveLength(2);
            expect(list_value_second_row.value[0].label).toBe(second_list_item.label);
            expect(list_value_second_row.value[0].color.unwrapOr(null)).toBe(
                second_list_item.color,
            );
            expect(list_value_second_row.value[1].label).toBe(third_list_item.label);
            expect(list_value_second_row.value[1].color.unwrapOr(null)).toBe(third_list_item.color);

            const list_value_third_row = third_row.cells.get(list_column);
            if (list_value_third_row?.type !== STATIC_LIST_CELL) {
                throw Error("Expected to find third static list cell");
            }
            expect(list_value_third_row.value).toHaveLength(0);
        });

        it(`builds a table with "list_user" selectables`, () => {
            const first_user = {
                display_name: "Anonymous user",
                avatar_url: "https://example.com/themes/common/images/avatar_default.png",
                user_url: null,
                is_anonymous: true,
            };

            const second_user = {
                display_name: "David Lopez (dlopez)",
                avatar_url: "https://example.com/users/dlopez/avatar.png",
                user_url: "/users/dlopez",
                is_anonymous: false,
            };

            const third_user = {
                display_name: "Shan Long (slong)",
                avatar_url: "https://example.com/users/slong/avatar.png",
                user_url: "/users/slong",
                is_anonymous: false,
            };
            const list_column = ASSIGNED_TO_COLUMN_NAME;

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: USER_LIST_SELECTABLE_TYPE, name: list_column }],
                    [
                        ArtifactRepresentationStub.build({
                            [list_column]: { value: [first_user] },
                        }),
                        ArtifactRepresentationStub.build({
                            [list_column]: { value: [second_user, third_user] },
                        }),
                        ArtifactRepresentationStub.build({ [list_column]: { value: [] } }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(list_column)).toBe(true);
            expect(table.rows).toHaveLength(3);
            const [first_row, second_row, third_row] = table.rows;
            const list_value_first_row = first_row.cells.get(list_column);
            if (list_value_first_row?.type !== USER_LIST_CELL) {
                throw Error("Expected to find first user list cell");
            }
            expect(list_value_first_row.value).toHaveLength(1);
            expect(list_value_first_row.value[0].display_name).toBe(first_user.display_name);
            expect(list_value_first_row.value[0].avatar_uri).toBe(first_user.avatar_url);
            expect(list_value_first_row.value[0].user_uri.isNothing()).toBe(true);

            const list_value_second_row = second_row.cells.get(list_column);
            if (list_value_second_row?.type !== USER_LIST_CELL) {
                throw Error("Expected to find second user list cell");
            }
            expect(list_value_second_row.value).toHaveLength(2);
            expect(list_value_second_row.value[0].display_name).toBe(second_user.display_name);
            expect(list_value_second_row.value[0].avatar_uri).toBe(second_user.avatar_url);
            expect(list_value_second_row.value[0].user_uri.unwrapOr(null)).toBe(
                second_user.user_url,
            );
            expect(list_value_second_row.value[1].display_name).toBe(third_user.display_name);
            expect(list_value_second_row.value[1].avatar_uri).toBe(third_user.avatar_url);
            expect(list_value_second_row.value[1].user_uri.unwrapOr(null)).toBe(
                third_user.user_url,
            );

            const list_value_third_row = third_row.cells.get(list_column);
            if (list_value_third_row?.type !== USER_LIST_CELL) {
                throw Error("Expected to find third user list cell");
            }
            expect(list_value_third_row.value).toHaveLength(0);
        });

        it(`builds a table with "list_user_group" selectables`, () => {
            const first_user_group = { label: "Developers" };
            const second_user_group = { label: "Project Members" };
            const third_user_group = { label: "integrators" };
            const list_column = "notified_team";

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: USER_GROUP_LIST_SELECTABLE_TYPE, name: list_column }],
                    [
                        ArtifactRepresentationStub.build({
                            [list_column]: { value: [first_user_group] },
                        }),
                        ArtifactRepresentationStub.build({
                            [list_column]: { value: [second_user_group, third_user_group] },
                        }),
                        ArtifactRepresentationStub.build({
                            [list_column]: { value: [] },
                        }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(list_column)).toBe(true);
            expect(table.rows).toHaveLength(3);
            const [first_row, second_row, third_row] = table.rows;
            const list_value_first_row = first_row.cells.get(list_column);
            if (list_value_first_row?.type !== USER_GROUP_LIST_CELL) {
                throw Error("Expected to find first user group list cell");
            }
            expect(list_value_first_row.value).toHaveLength(1);
            expect(list_value_first_row.value[0].label).toBe(first_user_group.label);

            const list_value_second_row = second_row.cells.get(list_column);
            if (list_value_second_row?.type !== USER_GROUP_LIST_CELL) {
                throw Error("Expected to find second user group list cell");
            }
            expect(list_value_second_row.value).toHaveLength(2);
            expect(list_value_second_row.value[0].label).toBe(second_user_group.label);
            expect(list_value_second_row.value[1].label).toBe(third_user_group.label);

            const list_value_third_row = third_row.cells.get(list_column);
            if (list_value_third_row?.type !== USER_GROUP_LIST_CELL) {
                throw Error("Expected to find third user group list cell");
            }
            expect(list_value_third_row.value).toHaveLength(0);
        });

        it(`builds a table with "project" selectables`, () => {
            const first_project = { icon: "", name: "Minimum Butter" };
            const second_project = { icon: "🖍️", name: "Teal Creek" };
            const project_column = PROJECT_COLUMN_NAME;

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: PROJECT_SELECTABLE_TYPE, name: project_column }],
                    [
                        ArtifactRepresentationStub.build({ [project_column]: first_project }),
                        ArtifactRepresentationStub.build({ [project_column]: second_project }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(project_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const project_first_row = first_row.cells.get(project_column);
            if (project_first_row?.type !== PROJECT_CELL) {
                throw Error("Expected to find first project name");
            }
            expect(project_first_row.icon).toBe(first_project.icon);
            expect(project_first_row.name).toBe(first_project.name);

            const project_second_row = second_row.cells.get(project_column);
            if (project_second_row?.type !== PROJECT_CELL) {
                throw Error("Expected to find second project name");
            }
            expect(project_second_row.icon).toBe(second_project.icon);
            expect(project_second_row.name).toBe(second_project.name);
        });

        it(`builds a table with "tracker" selectables`, () => {
            const first_tracker = { color: "army-green", name: "Releases" };
            const second_tracker = { color: "inca-silver", name: "Activities" };
            const tracker_column = TRACKER_COLUMN_NAME;

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: TRACKER_SELECTABLE_TYPE, name: tracker_column }],
                    [
                        ArtifactRepresentationStub.build({ [tracker_column]: first_tracker }),
                        ArtifactRepresentationStub.build({ [tracker_column]: second_tracker }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(tracker_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const tracker_first_row = first_row.cells.get(tracker_column);
            if (tracker_first_row?.type !== TRACKER_CELL) {
                throw Error("Expected to find first tracker name");
            }
            expect(tracker_first_row.name).toBe(first_tracker.name);
            expect(tracker_first_row.color).toBe(first_tracker.color);

            const tracker_second_row = second_row.cells.get(tracker_column);
            if (tracker_second_row?.type !== TRACKER_CELL) {
                throw Error("Expected to find second tracker name");
            }
            expect(tracker_second_row.name).toBe(second_tracker.name);
            expect(tracker_second_row.color).toBe(second_tracker.color);
        });

        it(`builds a table with "pretty_title" selectables`, () => {
            const first_title = {
                tracker_name: "releases",
                color: "teddy-brown",
                artifact_id: 418,
                title: "Concordity knifeway",
            };
            const second_title = {
                tracker_name: "activities",
                color: "daphne-blue",
                artifact_id: 314,
                title: "",
            };
            const title_column = PRETTY_TITLE_COLUMN_NAME;

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: PRETTY_TITLE_SELECTABLE_TYPE, name: title_column }],
                    [
                        ArtifactRepresentationStub.build({ [title_column]: first_title }),
                        ArtifactRepresentationStub.build({ [title_column]: second_title }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(title_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const title_first_row = first_row.cells.get(title_column);
            if (title_first_row?.type !== PRETTY_TITLE_CELL) {
                throw Error("Expected to find first title name");
            }
            expect(title_first_row.tracker_name).toBe(first_title.tracker_name);
            expect(title_first_row.color).toBe(first_title.color);
            expect(title_first_row.artifact_id).toBe(first_title.artifact_id);
            expect(title_first_row.title).toBe(first_title.title);

            const title_second_row = second_row.cells.get(title_column);
            if (title_second_row?.type !== PRETTY_TITLE_CELL) {
                throw Error("Expected to find second title name");
            }
            expect(title_second_row.tracker_name).toBe(second_title.tracker_name);
            expect(title_second_row.color).toBe(second_title.color);
            expect(title_second_row.artifact_id).toBe(second_title.artifact_id);
            expect(title_second_row.title).toBe(second_title.title);
        });

        it(`builds a table with "link_type" selectables`, () => {
            const link_type_column = LINK_TYPE_COLUMN_NAME;
            const link_type_value_reverse = "Is Child Of";
            const link_type_value_forward = "Is Parent Of";

            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: LINK_TYPE_SELECTABLE_TYPE, name: link_type_column }],
                    [
                        ArtifactRepresentationStub.build({
                            [link_type_column]: {
                                shortname: "_is_child",
                                direction: REVERSE_DIRECTION,
                                label: link_type_value_reverse,
                            },
                        }),
                        ArtifactRepresentationStub.build({
                            [link_type_column]: {
                                shortname: "_is_child",
                                direction: FORWARD_DIRECTION,
                                label: link_type_value_forward,
                            },
                        }),
                    ],
                ),
                NO_DIRECTION,
            );

            expect(table.columns.has(link_type_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const link_value_first_row = first_row.cells.get(link_type_column);
            if (link_value_first_row?.type !== LINK_TYPE_CELL) {
                throw Error("Expected to find first link value");
            }
            expect(link_value_first_row.label).toBe(link_type_value_reverse);

            const link_value_second_row = second_row.cells.get(link_type_column);
            if (link_value_second_row?.type !== LINK_TYPE_CELL) {
                throw Error("Expected to find second link value");
            }
            expect(link_value_second_row.label).toBe(link_type_value_forward);
        });

        it(`given a query content representation with an unsupported selectable type,
            it will NOT include it in the columns of the table
            and will NOT include it in the rows`, () => {
            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                SelectableQueryContentRepresentationStub.build(
                    [{ type: "unsupported", name: "wacken" }],
                    [ArtifactRepresentationStub.build({ wacken: { value: "frightfulness" } })],
                ),
                NO_DIRECTION,
            );
            expect(table.columns).toHaveLength(1);
            expect(table.columns.has(ARTIFACT_COLUMN_NAME)).toBe(true);
            expect(table.columns.has("wacken")).toBe(false);
            expect(table.rows).toHaveLength(1);
            expect(table.rows[0].artifact_uri).toBeDefined();
            expect(table.rows[0].cells).toHaveLength(0);
        });

        function* generateBrokenSelectedValues(): Generator<[string, Record<string, unknown>]> {
            yield [DATE_SELECTABLE_TYPE, { value: "ritualist" }];
            yield [NUMERIC_SELECTABLE_TYPE, { value: "ritualist" }];
            yield [TEXT_SELECTABLE_TYPE, { value: 12 }];
            yield [USER_SELECTABLE_TYPE, { value: 12 }];
            yield [STATIC_LIST_SELECTABLE_TYPE, { value: 12 }];
            yield [USER_LIST_SELECTABLE_TYPE, { value: 12 }];
            yield [USER_GROUP_LIST_SELECTABLE_TYPE, { value: 12 }];
            yield [PROJECT_SELECTABLE_TYPE, { value: 12 }];
            yield [TRACKER_SELECTABLE_TYPE, { value: 12 }];
            yield [PRETTY_TITLE_SELECTABLE_TYPE, { value: 12 }];
            yield [LINK_TYPE_SELECTABLE_TYPE, { value: 12 }];
            yield [UNKNOWN_SELECTABLE_TYPE, { value: 12 }];
        }

        it.each([...generateBrokenSelectedValues()])(
            `when the artifact value does not match the %s representation, it will throw an error`,
            (selected_type, representation) => {
                expect(() =>
                    ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                        SelectableQueryContentRepresentationStub.build(
                            [{ type: selected_type, name: "makeress" }],
                            [ArtifactRepresentationStub.build({ makeress: representation })],
                        ),
                        NO_DIRECTION,
                    ),
                ).toThrow();
            },
        );

        it(`allows an empty query so that we can show an empty state screen`, () => {
            const table = ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                {
                    selected: [],
                    artifacts: [],
                },
                NO_DIRECTION,
            );
            expect(table.columns).toHaveLength(0);
            expect(table.rows).toHaveLength(0);
        });

        it(`when the artifact value does not match the @artifact representation, it will throw an error`, () => {
            expect(() =>
                ArtifactsTableBuilder().mapQueryContentToArtifactsTable(
                    {
                        selected: [{ type: ARTIFACT_SELECTABLE_TYPE, name: ARTIFACT_COLUMN_NAME }],
                        artifacts: [{ [ARTIFACT_COLUMN_NAME]: { uri_is_missing: true } }],
                    },
                    NO_DIRECTION,
                ),
            ).toThrow();
        });
    });
});
