/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { handleError, handleModalError } from "./error-handler";
import type { VueGettextProvider } from "./vue-gettext-provider";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { ActionContext } from "vuex";
import type { State } from "../type";

describe("Error Handler", () => {
    describe("handleError", () => {
        const gettext_provider: VueGettextProvider = {
            $gettext: (s: string) => s,
        };

        it("When there is no response key, Then generic message is returned", async () => {
            const message = await handleError({} as FetchWrapperError, gettext_provider);
            expect(message).toEqual("Oops, an error occurred!");
        });

        it("When there is no error key, Then generic message is returned", async () => {
            const message = await handleError(
                {
                    response: {
                        json: (): Promise<{ message: string }> => Promise.resolve({ message: "" }),
                    },
                } as FetchWrapperError,
                gettext_provider
            );
            expect(message).toEqual("Oops, an error occurred!");
        });

        it("When there is i18n message, Then it is returned", async () => {
            const message = await handleError(
                {
                    response: {
                        json: (): Promise<{
                            error: { i18n_error_message: string; code: number; message: string };
                        }> =>
                            Promise.resolve({
                                error: {
                                    i18n_error_message: "My i18n Message",
                                    code: 404,
                                    message: "not found",
                                },
                            }),
                    },
                } as FetchWrapperError,
                gettext_provider
            );
            expect(message).toEqual("My i18n Message");
        });

        it("When there is no i18n message, Then it code ans message are returned", async () => {
            const message = await handleError(
                {
                    response: {
                        json: (): Promise<{
                            error: { code: number; message: string };
                        }> =>
                            Promise.resolve({
                                error: {
                                    code: 404,
                                    message: "not found",
                                },
                            }),
                    },
                } as FetchWrapperError,
                gettext_provider
            );
            expect(message).toEqual("404 not found");
        });
    });

    describe(`handleModalError`, () => {
        let context: ActionContext<State, State>;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
            } as unknown as ActionContext<State, State>;
        });
        it(`When a message can be extracted from the FetchWrapperError,
            it will set an error message that will show up in a modal window`, async () => {
            const error = {
                response: {
                    json: () =>
                        Promise.resolve({
                            error: { code: 500, message: "Internal Server Error" },
                        }),
                } as Response,
            } as FetchWrapperError;

            await handleModalError(context, error);

            expect(context.commit).toHaveBeenCalledWith(
                "setModalErrorMessage",
                "500 Internal Server Error"
            );
        });

        it(`When a message can not be extracted from the FetchWrapperError,
            it will leave the modal error message empty`, async () => {
            const error = {
                response: {
                    json: () => Promise.reject(),
                } as Response,
            } as FetchWrapperError;

            await handleModalError(context, error);

            expect(context.commit).toHaveBeenCalledWith("setModalErrorMessage", "");
        });
    });
});
