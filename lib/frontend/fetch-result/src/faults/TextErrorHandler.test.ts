/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { describe, expect, it } from "vitest";
import { TextErrorHandler } from "./TextErrorHandler";

const isTextParseFault = (fault: Fault): boolean =>
    "isTextParseFault" in fault && fault.isTextParseFault() === true;
const isTuleapAPIFault = (fault: Fault): boolean =>
    "isTuleapAPIFault" in fault && fault.isTuleapAPIFault() === true;

describe(`TextErrorHandler`, () => {
    const handle = (response: Response): ResultAsync<Response, Fault> => {
        const handler = TextErrorHandler();
        return handler.handleErrorResponse(response);
    };

    it(`when the response is ok, it returns an Ok containing it`, async () => {
        const response = { ok: true } as Response;
        const result = await handle(response);
        if (!result.isOk()) {
            throw Error("Expected an Ok");
        }
        expect(result.value).toBe(response);
    });

    it(`when there is an API error with a text response, it will return an Err with a TuleapAPIFault`, async () => {
        const response = {
            ok: false,
            text: () => Promise.resolve("Bad request: There is no content to interpret"),
        } as Response;

        const result = await handle(response);
        if (!result.isErr()) {
            throw Error("Expected an Err");
        }
        expect(isTuleapAPIFault(result.error)).toBe(true);
    });

    it(`when there is an API error but it somehow cannot be parsed as text,
        it will return an Err with a TextParseFault`, async () => {
        const response = {
            ok: false,
            text: () => Promise.reject("Could not parse Text"),
        } as Response;

        const result = await handle(response);
        if (!result.isErr()) {
            throw Error("Expected an Err");
        }
        expect(isTextParseFault(result.error)).toBe(true);
    });
});
