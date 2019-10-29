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

import * as actions from "./user-actions";
import * as tlp from "tlp";
import { mockFetchError, mockFetchSuccess } from "tlp-fetch-mocks-helper-jest";
import { ActionContext } from "vuex";
import { RootState } from "../type";
import { UserPreference, UserPreferenceValue, UserState } from "./type";

jest.mock("tlp");

describe("User state actions", () => {
    let context: ActionContext<UserState, RootState>;

    beforeEach(() => {
        context = {
            state: {
                user_id: 101
            } as UserState
        } as ActionContext<UserState, RootState>;
    });

    describe("deletePreference", () => {
        it("Calls REST API to delete the preference for regular user", async () => {
            const tlpDeleteMock = jest.spyOn(tlp, "del");
            mockFetchSuccess(tlpDeleteMock, {});

            await actions.deletePreference(context, { key: "my-key" } as UserPreference);
            expect(tlpDeleteMock).toHaveBeenCalledWith(`/api/v1/users/101/preferences?key=my-key`);
        });

        it("Silently ignore REST errors", async () => {
            const tlpDeleteMock = jest.spyOn(tlp, "del");
            mockFetchError(tlpDeleteMock, {});

            await actions.deletePreference(context, { key: "my-key" } as UserPreference);
        });
    });

    describe("setPreference", () => {
        it("Calls REST API to set the preference for regular user", async () => {
            const tlpPatchMock = jest.spyOn(tlp, "patch");
            mockFetchSuccess(tlpPatchMock, {});

            await actions.setPreference(context, {
                key: "my-key",
                value: "my-value"
            } as UserPreferenceValue);
            expect(tlpPatchMock).toHaveBeenCalledWith(`/api/v1/users/101/preferences`, {
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    key: "my-key",
                    value: "my-value"
                })
            });
        });

        it("Silently ignore REST errors", async () => {
            const tlpPatchMock = jest.spyOn(tlp, "patch");
            mockFetchError(tlpPatchMock, {});

            await actions.setPreference(context, {
                key: "my-key",
                value: "my-value"
            } as UserPreferenceValue);
        });
    });
});
