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

import { ActionContext } from "vuex";
import { RootState, State } from "./type";
import * as tlp from "tlp";
import { ColumnDefinition } from "../type";
import { mockFetchError, mockFetchSuccess } from "tlp-fetch-mocks-helper-jest";
import * as actions from "./actions";

jest.mock("tlp");

describe("State actions", () => {
    let context: ActionContext<State, RootState>;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            dispatch: jest.fn(),
            rootState: {
                milestone_id: 42,
                user: {
                    user_id: 101
                }
            } as RootState
        } as unknown) as ActionContext<State, RootState>;
    });

    describe("expandColumn", () => {
        it(`When the column is expanded, the user pref is stored`, () => {
            const column: ColumnDefinition = {
                id: 69
            } as ColumnDefinition;

            const tlpDeleteMock = jest.spyOn(tlp, "del");
            mockFetchSuccess(tlpDeleteMock, {});

            actions.expandColumn(context, column);
            expect(context.commit).toHaveBeenCalledWith("expandColumn", column);
            expect(tlpDeleteMock).toHaveBeenCalledWith(
                `/api/v1/users/101/preferences?key=plugin_taskboard_collapse_column_42_69`
            );
        });

        it(`ignores error on save`, () => {
            const column: ColumnDefinition = {
                id: 69
            } as ColumnDefinition;

            const tlpDeleteMock = jest.spyOn(tlp, "del");
            mockFetchError(tlpDeleteMock, {});

            actions.expandColumn(context, column);
            expect(context.commit).toHaveBeenCalledWith("expandColumn", column);
            expect(tlpDeleteMock).toHaveBeenCalledWith(
                `/api/v1/users/101/preferences?key=plugin_taskboard_collapse_column_42_69`
            );
        });
    });

    describe("collapseColumn", () => {
        it(`When the column is collapsed, the user pref is stored`, () => {
            const column: ColumnDefinition = {
                id: 69
            } as ColumnDefinition;

            const tlpPatchMock = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            actions.collapseColumn(context, column);
            expect(context.commit).toHaveBeenCalledWith("collapseColumn", column);
            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/users/101/preferences`, {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    key: "plugin_taskboard_collapse_column_42_69",
                    value: 1
                })
            });
        });

        it(`ignores error on save`, () => {
            const column: ColumnDefinition = {
                id: 69
            } as ColumnDefinition;

            const tlpPatchMock = jest.spyOn(tlp, "patch");
            mockFetchError(tlpPatchMock, {});

            actions.collapseColumn(context, column);
            expect(context.commit).toHaveBeenCalledWith("collapseColumn", column);
            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/users/101/preferences`, {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    key: "plugin_taskboard_collapse_column_42_69",
                    value: 1
                })
            });
        });
    });
});
