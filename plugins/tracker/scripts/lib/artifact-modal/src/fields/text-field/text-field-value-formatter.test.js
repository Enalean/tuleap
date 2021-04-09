/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import { formatExistingValue } from "./text-field-value-formatter.js";

describe("formatExistingValue", () => {
    it.each([
        ["text", "The D3 S is cool", { value: "The D3 S is cool", format: "text" }],
        [
            "html",
            "<p> The <strong>D3 S</strong> is cool</p>",
            {
                value: "<p> The <strong>D3 S</strong> is cool</p>",
                format: "html",
            },
        ],
        [
            "commonmark",
            "The **D3 S** is cool",
            {
                value: "<p>The <strong>D3 S</strong> is cool</p>",
                format: "html",
                commonmark: "The **D3 S** is cool",
            },
        ],
    ])(
        `returns the %s value from an artifact in an object`,
        (expected_format, expected_content, artifact_value) => {
            const text_field_value = formatExistingValue(artifact_value);

            expect(text_field_value.content).toEqual(expected_content);
            expect(text_field_value.format).toEqual(expected_format);
        }
    );
});
