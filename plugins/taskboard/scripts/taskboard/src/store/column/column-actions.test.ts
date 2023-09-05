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

import type { ActionContext } from "vuex";
import type { RootState } from "../type";
import type { ColumnDefinition } from "../../type";
import * as actions from "./column-actions";
import type { ColumnState } from "./type";

jest.mock("tlp");

describe("Column module actions", () => {
    let context: ActionContext<ColumnState, RootState>;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42,
                user: {
                    user_id: 101,
                },
            } as RootState,
        } as unknown as ActionContext<ColumnState, RootState>;
    });

    describe("expandColumn", () => {
        it(`When the column is expanded, the user pref is stored`, async () => {
            const column: ColumnDefinition = {
                id: 69,
            } as ColumnDefinition;

            await actions.expandColumn(context, column);
            expect(context.commit).toHaveBeenCalledWith("expandColumn", column);
            expect(context.dispatch).toHaveBeenCalledWith(
                "user/deletePreference",
                { key: "plugin_taskboard_collapse_column_42_69" },
                { root: true },
            );
        });
    });

    describe("collapseColumn", () => {
        it(`When the column is collapsed, the user pref is stored`, async () => {
            const column: ColumnDefinition = {
                id: 69,
            } as ColumnDefinition;

            await actions.collapseColumn(context, column);
            expect(context.commit).toHaveBeenCalledWith("collapseColumn", column);
            expect(context.dispatch).toHaveBeenCalledWith(
                "user/setPreference",
                { key: "plugin_taskboard_collapse_column_42_69", value: "1" },
                { root: true },
            );
        });
    });
});
