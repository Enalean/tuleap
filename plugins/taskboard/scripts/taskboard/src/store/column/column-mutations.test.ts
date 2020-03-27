/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { ColumnDefinition } from "../../type";
import * as mutations from "./column-mutations";
import { ColumnState } from "./type";

describe(`Column module mutations`, () => {
    describe("collapseColumn", () => {
        it("collapses column", () => {
            const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;

            const state: ColumnState = {
                columns: [column],
            };

            mutations.collapseColumn(state, column);
            expect(state.columns[0].is_collapsed).toBe(true);
        });
    });

    describe("expandColumn", () => {
        it("expands column", () => {
            const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;

            const state: ColumnState = {
                columns: [column],
            };

            mutations.expandColumn(state, column);
            expect(state.columns[0].is_collapsed).toBe(false);
        });
    });
    describe("pointerEntersColumn", () => {
        it("marks the column with hovering state", () => {
            const column: ColumnDefinition = { has_hover: false } as ColumnDefinition;

            const state: ColumnState = {
                columns: [column],
            };

            mutations.pointerEntersColumn(state, column);
            expect(state.columns[0].has_hover).toBe(true);
        });
    });

    describe("pointerLeavesColumn", () => {
        it("removes the hovering state", () => {
            const column: ColumnDefinition = { has_hover: true } as ColumnDefinition;

            const state: ColumnState = {
                columns: [column],
            };

            mutations.pointerLeavesColumn(state, column);
            expect(state.columns[0].has_hover).toBe(false);
        });
    });
});
