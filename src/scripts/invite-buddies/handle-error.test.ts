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

import { handleError } from "./handle-error";
import { FetchWrapperError } from "tlp";
import * as feedback_display from "./feedback-display";

jest.mock("./feedback-display");

describe("handle-error", () => {
    describe("handleError", () => {
        it("Rethrows unknown error", async () => {
            const random_error = new Error() as FetchWrapperError;

            await expect(handleError(random_error)).rejects.toThrow(random_error);
        });

        it("Throw json error if the response does not contain valid json", async () => {
            const json_error = new Error("No valid json");
            const rest_error = new FetchWrapperError("Internal Server Error", {
                json: () => Promise.reject(json_error),
            } as Response);

            await expect(handleError(rest_error)).rejects.toThrow(json_error);
        });

        it("Displays a generic error, and rethrows the error", async () => {
            const rest_error = new FetchWrapperError("Internal Server Error", {
                json: () =>
                    Promise.resolve({
                        error: { code: 500, message: "Internal Server Error" },
                    }),
            } as Response);

            const displayError = jest.spyOn(feedback_display, "displayError");

            await expect(handleError(rest_error)).rejects.toThrow(rest_error);

            expect(displayError).toHaveBeenCalledWith("500 Internal Server Error");
        });

        it("Displays i18n error, and rethrows the error", async () => {
            const rest_error = new FetchWrapperError("Internal Server Error", {
                json: () =>
                    Promise.resolve({
                        error: {
                            code: 400,
                            message: "Bad request",
                            i18n_error_message: "Feature is not available",
                        },
                    }),
            } as Response);

            const displayError = jest.spyOn(feedback_display, "displayError");

            await expect(handleError(rest_error)).rejects.toThrow(rest_error);

            expect(displayError).toHaveBeenCalledWith("Feature is not available");
        });
    });
});
