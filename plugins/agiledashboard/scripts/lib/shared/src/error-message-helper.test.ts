/*
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

import { describe, it, expect } from "vitest";
import { extractErrorMessage } from "./error-message-helper";
import { FetchWrapperError } from "@tuleap/tlp-fetch";

describe(`error-message-helper`, () => {
    describe(`extractErrorMessage`, () => {
        it(`when there is no "response" property in the error object,
            it will return the error's message`, async () => {
            const result = await extractErrorMessage(new Error("Some other type of error"));
            expect(result).toBe("Some other type of error");
        });

        it(`when the error response cannot be parsed as JSON,
            it will return the error's message`, async () => {
            const response = {
                json(): Promise<unknown> {
                    return Promise.reject("Could not deserialize JSON");
                },
            } as Response;
            const result = await extractErrorMessage(
                new FetchWrapperError("Internal Server Error", response),
            );
            expect(result).toBe("Internal Server Error");
        });

        it(`when there is no "error.message" property in the JSON body,
            it will return the error's message`, async () => {
            const response = {
                json(): Promise<Record<string, unknown>> {
                    return Promise.resolve({ key: "value" });
                },
            } as Response;
            const result = await extractErrorMessage(
                new FetchWrapperError("Bad Request", response),
            );
            expect(result).toBe("Bad Request");
        });

        it(`when there is an "error.message" property in the JSON body,
            it will return the JSON body's error message`, async () => {
            const response = {
                json(): Promise<Record<string, unknown>> {
                    return Promise.resolve({ error: { message: `Missing property "query"` } });
                },
            } as Response;
            const result = await extractErrorMessage(
                new FetchWrapperError("Bad Request", response),
            );
            expect(result).toBe(`Missing property "query"`);
        });
    });
});
