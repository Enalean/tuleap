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

import * as mutations from "./mutations";
import { State } from "./type";
import { ColumnDefinition } from "../type";

describe(`State mutations`, () => {
    describe("collapseColumn", () => {
        it("collapses column", () => {
            const column: ColumnDefinition = { is_collapsed: false } as ColumnDefinition;

            const state: State = {
                columns: [column]
            } as State;

            mutations.collapseColumn(state, column);
            expect(state.columns[0].is_collapsed).toBe(true);
        });
    });

    describe("expandColumn", () => {
        it("expands column", () => {
            const column: ColumnDefinition = { is_collapsed: true } as ColumnDefinition;

            const state: State = {
                columns: [column]
            } as State;

            mutations.expandColumn(state, column);
            expect(state.columns[0].is_collapsed).toBe(false);
        });
    });
});
