/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { RestlerErrorHandler } from "./RestlerErrorHandler";

const isJSONParseFault = (fault: Fault): boolean =>
    "isJSONParseFault" in fault && fault.isJSONParseFault() === true;
const isTuleapAPIFault = (fault: Fault): boolean =>
    "isTuleapAPIFault" in fault && fault.isTuleapAPIFault() === true;

describe(`RestlerErrorHandler`, () => {
    const handle = (response: Response): ResultAsync<Response, Fault> => {
        const handler = RestlerErrorHandler();
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

    it.each([
        [
            "with a translated message",
            { error: { i18n_error_message: "Une erreur s'est produite" } },
        ],
        ["with an untranslated message", { error: { message: "An error occurred" } }],
        ["without a message", {}],
    ])(
        `when there is an API error %s, it will return an Err with a TuleapAPIFault`,
        async (_explanation: string, json_content) => {
            const response = {
                ok: false,
                json: () => Promise.resolve(json_content),
            } as Response;

            const result = await handle(response);
            if (!result.isErr()) {
                throw Error("Expected an Err");
            }
            expect(isTuleapAPIFault(result.error)).toBe(true);
        }
    );

    it(`when there is an API error but its JSON cannot be parsed,
        it will return an Err with a JSONParseFault`, async () => {
        const response = {
            ok: false,
            json: () => Promise.reject("Could not parse JSON"),
        } as Response;

        const result = await handle(response);
        if (!result.isErr()) {
            throw Error("Expected an Err");
        }
        expect(isJSONParseFault(result.error)).toBe(true);
    });
});
