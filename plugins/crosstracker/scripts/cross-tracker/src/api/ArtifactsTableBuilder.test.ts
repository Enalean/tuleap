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
    DATE_SELECTABLE_TYPE,
    NUMERIC_SELECTABLE_TYPE,
    PROJECT_SELECTABLE_TYPE,
    TEXT_SELECTABLE_TYPE,
    TRACKER_SELECTABLE_TYPE,
} from "./cross-tracker-rest-api-types";
import { ArtifactsTableBuilder } from "./ArtifactsTableBuilder";
import {
    DATE_CELL,
    NUMERIC_CELL,
    PROJECT_CELL,
    TEXT_CELL,
    TRACKER_CELL,
} from "../domain/ArtifactsTable";
import { PROJECT_COLUMN_NAME, TRACKER_COLUMN_NAME } from "../domain/ColumnName";

describe(`ArtifactsTableBuilder`, () => {
    describe(`mapReportToArtifactsTable()`, () => {
        it(`will transform each selected name into a column name
            and for each artifact, it will create a Map from column name to its value
            so that it is easy to render the table of results for each column`, () => {
            const first_date = "2022-09-15T00:00:00+06:00";
            const second_date = "2018-09-23T23:26:36+09:00";
            const date_column = "start_date";

            const float_value = 15.2;
            const int_value = 10;
            const numeric_column = "remaining_effort";

            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [
                    { type: DATE_SELECTABLE_TYPE, name: date_column },
                    { type: NUMERIC_SELECTABLE_TYPE, name: numeric_column },
                ],
                artifacts: [
                    {
                        [date_column]: { value: first_date, with_time: false },
                        [numeric_column]: { value: float_value },
                    },
                    {
                        [date_column]: { value: second_date, with_time: true },
                        [numeric_column]: { value: int_value },
                    },
                ],
            });

            expect(table.columns.size).toBe(2);

            expect(table.columns.has(date_column)).toBe(true);
            expect(table.columns.has(numeric_column)).toBe(true);

            expect(table.rows).toHaveLength(2);

            const [first_row, second_row] = table.rows;

            const date_value_first_row = first_row.get(date_column);
            if (date_value_first_row?.type !== DATE_CELL) {
                throw Error("Expected to find first date value");
            }
            expect(date_value_first_row.value.unwrapOr(null)).toBe(first_date);

            const numeric_value_first_row = first_row.get(numeric_column);
            if (numeric_value_first_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find first numeric value");
            }
            expect(numeric_value_first_row.value.unwrapOr(null)).toBe(float_value);

            const date_value_second_row = second_row.get(date_column);
            if (date_value_second_row?.type !== DATE_CELL) {
                throw Error("Expected to find second date value");
            }
            expect(date_value_second_row.value.unwrapOr(null)).toBe(second_date);

            const numeric_value_second_row = second_row.get(numeric_column);
            if (numeric_value_second_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find second numeric value");
            }
            expect(numeric_value_second_row.value.unwrapOr(null)).toBe(int_value);
        });

        it(`builds a table with "date" selectables`, () => {
            const first_date = "2022-09-15T00:00:00+06:00";
            const second_date = "2018-09-23T23:26:36+09:00";
            const date_column = "start_date";

            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [{ type: DATE_SELECTABLE_TYPE, name: date_column }],
                artifacts: [
                    { [date_column]: { value: first_date, with_time: false } },
                    { [date_column]: { value: second_date, with_time: true } },
                    { [date_column]: { value: null, with_time: false } },
                ],
            });

            expect(table.columns.has(date_column)).toBe(true);
            expect(table.rows).toHaveLength(3);
            const [first_row, second_row, third_row] = table.rows;
            const date_value_first_row = first_row.get(date_column);
            if (date_value_first_row?.type !== DATE_CELL) {
                throw Error("Expected to find first date value");
            }
            expect(date_value_first_row.value.unwrapOr(null)).toBe(first_date);
            expect(date_value_first_row.with_time).toBe(false);

            const date_value_second_row = second_row.get(date_column);
            if (date_value_second_row?.type !== DATE_CELL) {
                throw Error("Expected to find second date value");
            }
            expect(date_value_second_row.value.unwrapOr(null)).toBe(second_date);
            expect(date_value_second_row.with_time).toBe(true);

            const date_value_third_row = third_row.get(date_column);
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

            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [{ type: NUMERIC_SELECTABLE_TYPE, name: numeric_column }],
                artifacts: [
                    { [numeric_column]: { value: float_value } },
                    { [numeric_column]: { value: int_value } },
                    { [numeric_column]: { value: null } },
                ],
            });

            expect(table.columns.has(numeric_column)).toBe(true);
            expect(table.rows).toHaveLength(3);
            const [first_row, second_row, third_row] = table.rows;
            const numeric_value_first_row = first_row.get(numeric_column);
            if (numeric_value_first_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find first numeric value");
            }
            expect(numeric_value_first_row.value.unwrapOr(null)).toBe(float_value);

            const numeric_value_second_row = second_row.get(numeric_column);
            if (numeric_value_second_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find second numeric value");
            }
            expect(numeric_value_second_row.value.unwrapOr(null)).toBe(int_value);

            const numeric_value_third_row = third_row.get(numeric_column);
            if (numeric_value_third_row?.type !== NUMERIC_CELL) {
                throw Error("Expected to find third numeric value");
            }
            expect(numeric_value_third_row.value.isNothing()).toBe(true);
        });

        it(`builds a table with "text" selectables`, () => {
            const text_value = "<p>Griffith II</p>";
            const text_column = "details";

            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [{ type: TEXT_SELECTABLE_TYPE, name: text_column }],
                artifacts: [
                    { [text_column]: { value: text_value } },
                    { [text_column]: { value: "" } },
                ],
            });

            expect(table.columns.has(text_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const text_value_first_row = first_row.get(text_column);
            if (text_value_first_row?.type !== TEXT_CELL) {
                throw Error("Expected to find first text value");
            }
            expect(text_value_first_row.value).toBe(text_value);

            const text_value_second_row = second_row.get(text_column);
            if (text_value_second_row?.type !== TEXT_CELL) {
                throw Error("Expected to find second text value");
            }
            expect(text_value_second_row.value).toBe("");
        });

        it(`builds a table with "project" selectables`, () => {
            const first_project = { icon: "", name: "Minimum Butter" };
            const second_project = { icon: "🖍️", name: "Teal Creek" };
            const project_column = PROJECT_COLUMN_NAME;

            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [{ type: PROJECT_SELECTABLE_TYPE, name: project_column }],
                artifacts: [
                    { [project_column]: first_project },
                    { [project_column]: second_project },
                ],
            });

            expect(table.columns.has(project_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const project_first_row = first_row.get(project_column);
            if (project_first_row?.type !== PROJECT_CELL) {
                throw Error("Expected to find first project name");
            }
            expect(project_first_row.icon).toBe(first_project.icon);
            expect(project_first_row.name).toBe(first_project.name);

            const project_second_row = second_row.get(project_column);
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

            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [{ type: TRACKER_SELECTABLE_TYPE, name: tracker_column }],
                artifacts: [
                    { [tracker_column]: first_tracker },
                    { [tracker_column]: second_tracker },
                ],
            });

            expect(table.columns.has(tracker_column)).toBe(true);
            expect(table.rows).toHaveLength(2);
            const [first_row, second_row] = table.rows;
            const tracker_first_row = first_row.get(tracker_column);
            if (tracker_first_row?.type !== TRACKER_CELL) {
                throw Error("Expected to find first tracker name");
            }
            expect(tracker_first_row.name).toBe(first_tracker.name);
            expect(tracker_first_row.color).toBe(first_tracker.color);

            const tracker_second_row = second_row.get(tracker_column);
            if (tracker_second_row?.type !== TRACKER_CELL) {
                throw Error("Expected to find second tracker name");
            }
            expect(tracker_second_row.name).toBe(second_tracker.name);
            expect(tracker_second_row.color).toBe(second_tracker.color);
        });

        it(`given a report content representation with an unsupported selectable type,
            it will NOT include it in the columns of the table
            and will NOT include it in the rows`, () => {
            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [{ type: "unsupported", name: "wacken" }],
                artifacts: [{ wacken: { value: "frightfulness" } }],
            });
            expect(table.columns.size).toBe(0);
            expect(table.rows).toHaveLength(1);
            expect(table.rows[0].size).toBe(0);
        });

        function* generateBrokenSelectedValues(): Generator<[string, Record<string, unknown>]> {
            yield [DATE_SELECTABLE_TYPE, { value: "ritualist" }];
            yield [NUMERIC_SELECTABLE_TYPE, { value: "ritualist" }];
            yield [TEXT_SELECTABLE_TYPE, { value: 12 }];
            yield [PROJECT_SELECTABLE_TYPE, { value: 12 }];
            yield [TRACKER_SELECTABLE_TYPE, { value: 12 }];
        }

        it.each([...generateBrokenSelectedValues()])(
            `when the artifact value does not match the %s representation, it will throw an error`,
            (selected_type, representation) => {
                expect(() =>
                    ArtifactsTableBuilder().mapReportToArtifactsTable({
                        selected: [{ type: selected_type, name: "makeress" }],
                        artifacts: [{ makeress: representation }],
                    }),
                ).toThrow();
            },
        );
    });
});
