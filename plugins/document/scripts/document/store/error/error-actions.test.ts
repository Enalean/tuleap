/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import * as actions from "./error-actions";
import type { State } from "../../type";
import type { ActionContext } from "vuex";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe(`Error module actions`, () => {
    let context: ActionContext<State, State>;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
        } as unknown as ActionContext<State, State>;
    });

    describe(`handleGlobalModalError`, () => {
        it(`when a message can be extracted from the FetchWrapperError,
            it will set an error message that will show up in a dedicated modal window`, async () => {
            const error = {
                response: {
                    json: () =>
                        Promise.resolve({
                            error: { code: 500, message: "Internal Server Error" },
                        }),
                } as Response,
            } as FetchWrapperError;
            await actions.handleGlobalModalError(context, error);

            expect(context.commit).toHaveBeenCalledWith(
                "setGlobalModalErrorMessage",
                "500 Internal Server Error"
            );
        });

        it(`when a message can't be extracted from the FetchWrapperError,
            it will leave the modal error message empty`, async () => {
            const response = {
                json(): Promise<unknown> {
                    return Promise.reject("Oh snap");
                },
            } as Response;
            const error = new FetchWrapperError("Internal Server Error", response);

            await actions.handleGlobalModalError(context, error);

            expect(context.commit).toHaveBeenCalledWith("setGlobalModalErrorMessage", "");
        });
    });
});
