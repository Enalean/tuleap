/**
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
import { getEncodedURIString, rawUri, uri } from "./uri-string-template";

describe(`uri-string-template`, () => {
    it("encodes parameters", () => {
        const param_str = "R&D";
        const param_number = 123;
        const param_bool = false;
        const encoded_uri = uri`https://example.com/page/${param_str}/${param_number}/${param_bool}`;

        expect(getEncodedURIString(encoded_uri)).toBe("https://example.com/page/R%26D/123/false");
    });

    it("encodes nested parameters", () => {
        const param_sub_part = "a#a";
        const uri_part = uri`/sub_page/${param_sub_part}`;
        const param = "R&D";
        const encoded_uri = uri`https://example.com/page/${param}${uri_part}`;

        expect(getEncodedURIString(encoded_uri)).toBe(
            "https://example.com/page/R%26D/sub_page/a%23a",
        );
    });

    it('leaves "raw" URI parts as is', () => {
        const encoded_uri = uri`${rawUri("https://[2001:db8::1]")}/page`;

        expect(getEncodedURIString(encoded_uri)).toBe("https://[2001:db8::1]/page");
    });
});
