/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import * as actions from "./actions";
import { State } from "./type";
import {
    mockFetchError,
    mockFetchSuccess,
} from "../../../../themes/tlp/mocks/tlp-fetch-mock-helper";
import * as tlp from "../../../../themes/tlp/src/js/fetch-wrapper";
import { ActionContext } from "vuex";

jest.mock("tlp");

describe("SwitchTo actions", () => {
    describe("loadHistory", () => {
        it("Rethrow API error", async () => {
            const tlpGetMock = jest.spyOn(tlp, "get");

            mockFetchError(tlpGetMock, {});

            const context = ({
                commit: jest.fn(),
                dispatch: jest.fn(),
                state: {
                    user_id: 102,
                    is_history_loaded: false,
                } as State,
            } as unknown) as ActionContext<State, State>;

            await expect(actions.loadHistory(context)).rejects.toBeDefined();

            expect(context.commit).toHaveBeenCalledWith("setErrorForHistory", true);
        });

        it("Fetch user history", async () => {
            const tlpGetMock = jest.spyOn(tlp, "get");

            mockFetchSuccess(tlpGetMock, {
                return_json: {
                    entries: [{ xref: "art #1" }],
                },
            });

            const context = ({
                commit: jest.fn(),
                dispatch: jest.fn(),
                state: {
                    user_id: 102,
                    is_history_loaded: false,
                } as State,
            } as unknown) as ActionContext<State, State>;

            await actions.loadHistory(context);

            expect(context.commit).toHaveBeenCalledWith("saveHistory", {
                entries: [{ xref: "art #1" }],
            });
        });

        it("Does not fetch user history if it has already been loaded", async () => {
            const tlpGetMock = jest.spyOn(tlp, "get");

            const context = ({
                commit: jest.fn(),
                dispatch: jest.fn(),
                state: {
                    user_id: 102,
                    is_history_loaded: true,
                } as State,
            } as unknown) as ActionContext<State, State>;

            await actions.loadHistory(context);

            expect(tlpGetMock).not.toHaveBeenCalled();
        });
    });
});
