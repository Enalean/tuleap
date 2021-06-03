/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import * as mutations from "./root-mutations";
import type { RootState } from "./type";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";

describe("root-mutations", () => {
    it("stopLoading set the corresponding boolean to false", () => {
        const state: RootState = {
            is_loading: true,
        } as RootState;

        mutations.stopLoading(state);

        expect(state.is_loading).toBe(false);
    });

    describe("setApplicationInErrorStateDueToRestError", () => {
        it("should display an error state for a 400", async () => {
            const state: RootState = {
                should_display_error_state: false,
                error_message: "",
            } as RootState;

            await mutations.setApplicationInErrorStateDueToRestError(state, {
                response: {
                    ok: false,
                    status: 400,
                    statusText: "Bad request",
                    json: () =>
                        Promise.resolve({
                            error: {
                                i18n_error_message: "Missing timeframe",
                            },
                        }),
                },
            } as FetchWrapperError);

            expect(state.should_display_error_state).toBe(true);
            expect(state.error_message).toBe("Missing timeframe");
        });

        it("should display a generic error state for a 500", async () => {
            const state: RootState = {
                should_display_error_state: false,
                error_message: "",
            } as RootState;

            await mutations.setApplicationInErrorStateDueToRestError(state, {
                response: {
                    ok: false,
                    status: 500,
                    statusText: "Internal Server Error",
                },
            } as FetchWrapperError);

            expect(state.should_display_error_state).toBe(true);
            expect(state.error_message).toBe("");
        });
    });

    it("setApplicationInEmptyState should switch the application in empty state, as suggested by the name", () => {
        const state: RootState = {
            should_display_empty_state: false,
        } as RootState;

        mutations.setApplicationInEmptyState(state);

        expect(state.should_display_empty_state).toBe(true);
    });
});
