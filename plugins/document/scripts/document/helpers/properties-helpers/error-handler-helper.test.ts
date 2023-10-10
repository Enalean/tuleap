/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { getErrorMessage, handleErrorForHistoryVersion } from "./error-handler-helper";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { DocumentJsonError, JsonError } from "../../type";

describe("error-handler-helper", () => {
    describe("handleErrorForHistoryVersion", () => {
        it("throws an exception when the exception is not a FetchWrapperError", async () => {
            const exception = new Error("some error");
            await expect(handleErrorForHistoryVersion(exception)).rejects.toThrow();
        });

        it("returns the error message", async () => {
            const exception = new FetchWrapperError("Bad request", {
                json: (): Promise<{ error: { code: number; message: string } }> =>
                    Promise.resolve({
                        error: { code: 400, message: "Not now! Not after you did" },
                    }),
            } as Response);
            const error_message = await handleErrorForHistoryVersion(exception);
            expect(error_message).toBe("Not now! Not after you did");
        });

        it("returns the default message if the rest error message cannot be retrieved", async () => {
            const exception = new FetchWrapperError("Bad Request", {
                json: (): Promise<{ error: { code: number; message: string } }> =>
                    Promise.reject("Oh no! Anyway..."),
            } as Response);
            const error_message = await handleErrorForHistoryVersion(exception);
            expect(error_message).toBe("Internal server error");
        });
    });
    describe("getErrorMessage", () => {
        it("returns the translated message", () => {
            const error_json = { error: { message: "wololo", i18n_error_message: "converti" } };
            expect(getErrorMessage(error_json)).toBe("converti");
        });
        it("returns the base message when there is no translated message", () => {
            const error_json = { error: { message: "wololo" } as JsonError };
            expect(getErrorMessage(error_json)).toBe("wololo");
        });
        it("returns an empty string by default", () => {
            const error_json = {} as DocumentJsonError;
            expect(getErrorMessage(error_json)).toBe("");
        });
    });
});
