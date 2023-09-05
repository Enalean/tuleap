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
import type { RootState, State } from "./type";
import * as actions from "./actions";

jest.mock("tlp");

describe("State actions", () => {
    let context: ActionContext<State, RootState>;

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
        } as unknown as ActionContext<State, RootState>;
    });

    describe("displayClosedItems", () => {
        it(`When the closed item are displayed, the user pref is stored`, async () => {
            await actions.displayClosedItems(context);
            expect(context.commit).toHaveBeenCalledWith("displayClosedItems");
            expect(context.dispatch).toHaveBeenCalledWith(
                "user/deletePreference",
                { key: "plugin_taskboard_hide_closed_items_42" },
                { root: true },
            );
        });
    });

    describe("hideClosedItems", () => {
        it(`When the closed item are hidden, the user pref is stored`, async () => {
            await actions.hideClosedItems(context);
            expect(context.commit).toHaveBeenCalledWith("hideClosedItems");
            expect(context.dispatch).toHaveBeenCalledWith(
                "user/setPreference",
                { key: "plugin_taskboard_hide_closed_items_42", value: "1" },
                { root: true },
            );
        });
    });
});
