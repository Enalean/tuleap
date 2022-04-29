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

import type { Fault } from "@tuleap/fault";
import { decodeJSON } from "./json-decoder";

interface ValidPayload {
    readonly value: boolean;
}

const isJSONParseFault = (fault: Fault): boolean =>
    "isJSONParseFault" in fault && fault.isJSONParseFault() === true;

describe(`json-decoder`, () => {
    describe(`decodeJSON`, () => {
        it(`transforms a response into a ResultAsync with JSON`, async () => {
            const payload = { value: true };
            const response = {
                ok: true,
                json: (): Promise<ValidPayload> => Promise.resolve(payload),
            } as unknown as Response;

            const result = await decodeJSON(response);
            if (!result.isOk()) {
                throw new Error("Expected an OK");
            }
            expect(result.value).toBe(payload);
        });

        it(`if the payload cannot be parsed into JSON, it will return an Err with a JSONParseFault`, async () => {
            const error_response = {
                ok: true,
                json: (): Promise<never> => Promise.reject(new Error("Could not parse JSON")),
            } as unknown as Response;

            const result = await decodeJSON(error_response);
            if (!result.isErr()) {
                throw new Error("Expected an Err");
            }
            expect(isJSONParseFault(result.error)).toBe(true);
        });
    });
});
