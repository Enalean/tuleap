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
import { RootState } from "../type";
import { FetchWrapperError } from "tlp";
import * as actions from "./error-actions";
import { ErrorState } from "./type";

describe("Error modules actions", () => {
    let context: ActionContext<ErrorState, RootState>;

    beforeEach(() => {
        context = ({
            commit: jest.fn(),
        } as unknown) as ActionContext<ErrorState, RootState>;
    });

    describe(`handleGlobalError`, () => {
        it("sets a global error message when a message can be extracted from the FetchWrapperError instance", async () => {
            const error = new Error() as FetchWrapperError;
            error.response = {
                json: () =>
                    Promise.resolve({
                        error: { code: 500, message: "Internal Server Error" },
                    }),
            } as Response;

            await actions.handleGlobalError(context, error);

            expect(context.commit).toHaveBeenCalledTimes(1);
            expect(context.commit).toHaveBeenCalledWith(
                "setGlobalErrorMessage",
                "500 Internal Server Error"
            );
        });

        it("leaves the global error message empty when a message can not be extracted from the FetchWrapperError instance", async () => {
            const error = new Error() as FetchWrapperError;
            error.response = {
                json: () => Promise.reject(),
            } as Response;

            await actions.handleGlobalError(context, error);

            expect(context.commit).toHaveBeenCalledTimes(1);
            expect(context.commit).toHaveBeenCalledWith("setGlobalErrorMessage", "");
        });
    });

    describe(`handleModalError`, () => {
        it(`when a message can be extracted from the FetchWrapperError,
            it will set an error message that will show up in a modal window`, async () => {
            const error = new Error() as FetchWrapperError;
            error.response = {
                json: () =>
                    Promise.resolve({
                        error: { code: 500, message: "Internal Server Error" },
                    }),
            } as Response;

            await actions.handleModalError(context, error);

            expect(context.commit).toHaveBeenCalledWith(
                "setModalErrorMessage",
                "500 Internal Server Error"
            );
        });

        it(`when a message can't be extracted from the FetchWrapperError,
            it will leave the modal error message empty`, async () => {
            const error = new Error() as FetchWrapperError;
            error.response = {
                json: () => Promise.reject(),
            } as Response;

            await actions.handleModalError(context, error);

            expect(context.commit).toHaveBeenCalledWith("setModalErrorMessage", "");
        });
    });
});
