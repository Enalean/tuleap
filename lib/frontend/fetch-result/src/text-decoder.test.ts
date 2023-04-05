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

import { describe, expect, it } from "vitest";
import type { Fault } from "@tuleap/fault";
import { decodeAsText } from "./text-decoder";

const isTextParseFault = (fault: Fault): boolean =>
    "isTextParseFault" in fault && fault.isTextParseFault() === true;

describe(`text-decoder`, () => {
    describe(`decodeAsText()`, () => {
        it(`transforms a response into a ResultAsync with a string`, async () => {
            const payload = "photoheliographic pronglike";
            const response = {
                ok: true,
                text: () => Promise.resolve(payload),
            } as Response;

            const result = await decodeAsText(response);
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value).toBe(payload);
        });

        it(`if the payload somehow cannot be parsed as text, it will return an Err with a TextParseFault`, async () => {
            const response = {
                ok: true,
                text: () => Promise.reject("Could not parse Text"),
            } as Response;

            const result = await decodeAsText(response);
            if (!result.isErr()) {
                throw Error("Expected an Err");
            }
            expect(isTextParseFault(result.error)).toBe(true);
        });
    });
});
