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
import { DATE_SELECTABLE_TYPE, NUMERIC_SELECTABLE_TYPE } from "./cross-tracker-rest-api-types";
import { ArtifactsTableBuilder } from "./ArtifactsTableBuilder";
import { DATE_CELL, NUMERIC_CELL } from "../domain/ArtifactsTable";

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
                        start_date: { value: first_date, with_time: false },
                        remaining_effort: { value: float_value },
                    },
                    {
                        start_date: { value: second_date, with_time: true },
                        remaining_effort: { value: int_value },
                    },
                ],
            });

            expect(table.columns.size).toBe(2);

            expect(table.columns.has(date_column)).toBe(true);
            expect(table.columns.has(numeric_column)).toBe(true);

            expect(table.rows).toHaveLength(2);

            const [first_row, second_row] = table.rows;
            const date_value_first_row = first_row.get(date_column);
            if (!date_value_first_row || date_value_first_row.type !== DATE_CELL) {
                throw Error("Expected to find first date value");
            }
            expect(date_value_first_row.type).toBe(DATE_CELL);
            expect(date_value_first_row.value.unwrapOr(null)).toBe(first_date);
            expect(date_value_first_row.with_time).toBe(false);

            const numeric_value_first_row = first_row.get(numeric_column);
            if (!numeric_value_first_row) {
                throw Error("Expected to find first date value");
            }
            expect(numeric_value_first_row.type).toBe(NUMERIC_CELL);
            expect(numeric_value_first_row.value.unwrapOr(null)).toBe(float_value);

            const date_value_second_row = second_row.get(date_column);
            if (!date_value_second_row || date_value_second_row.type !== DATE_CELL) {
                throw Error("Expected to find second date value");
            }
            expect(date_value_second_row.type).toBe(DATE_CELL);
            expect(date_value_second_row.value.unwrapOr(null)).toBe(second_date);
            expect(date_value_second_row.with_time).toBe(true);

            const numeric_value_second_row = second_row.get(numeric_column);
            if (!numeric_value_second_row) {
                throw Error("Expected to find second date value");
            }
            expect(numeric_value_second_row.type).toBe(NUMERIC_CELL);
            expect(numeric_value_second_row.value.unwrapOr(null)).toBe(int_value);
        });

        it(`when the artifact has a null "date" value for the given selectable,
            it will build a Cell with Nothing`, () => {
            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [{ type: DATE_SELECTABLE_TYPE, name: "start_date" }],
                artifacts: [{ start_date: { value: null, with_time: false } }],
            });

            const date_value = table.rows[0].get("start_date");
            if (!date_value || date_value.type !== DATE_CELL) {
                throw Error("Expected to find date value");
            }

            expect(date_value.type).toBe(DATE_CELL);
            expect(date_value.value.isNothing()).toBe(true);
            expect(date_value.with_time).toBe(false);
        });

        it(`when the artifact has a null "numeric" value for the given selectable,
            it will build a Cell with Nothing`, () => {
            const table = ArtifactsTableBuilder().mapReportToArtifactsTable({
                selected: [{ type: NUMERIC_SELECTABLE_TYPE, name: "remaining_effort" }],
                artifacts: [{ remaining_effort: { value: null } }],
            });

            const date_value = table.rows[0].get("remaining_effort");
            if (!date_value) {
                throw Error("Expected to find date value");
            }

            expect(date_value.type).toBe(NUMERIC_CELL);
            expect(date_value.value.isNothing()).toBe(true);
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

        it.each([
            ["date", DATE_SELECTABLE_TYPE],
            ["numeric", NUMERIC_SELECTABLE_TYPE],
        ])(
            `when the artifact value does not match the %s representation, it will throw an error`,
            (_type, selected_type) => {
                expect(() =>
                    ArtifactsTableBuilder().mapReportToArtifactsTable({
                        selected: [{ type: selected_type, name: "makeress" }],
                        artifacts: [{ makeress: { value: "ritualist" } }],
                    }),
                ).toThrow();
            },
        );
    });
});
