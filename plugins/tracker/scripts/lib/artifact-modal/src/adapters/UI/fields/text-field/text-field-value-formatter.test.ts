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

import type { TextFieldArtifactValue } from "./text-field-value-formatter";
import { formatExistingValue } from "./text-field-value-formatter";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";

describe("formatExistingValue", () => {
    it.each([
        [
            TEXT_FORMAT_TEXT,
            "The D3 S is cool",
            { value: "The D3 S is cool", format: TEXT_FORMAT_TEXT } as TextFieldArtifactValue,
        ],
        [
            TEXT_FORMAT_HTML,
            "<p> The <strong>D3 S</strong> is cool</p>",
            {
                value: "<p> The <strong>D3 S</strong> is cool</p>",
                format: TEXT_FORMAT_HTML,
            } as TextFieldArtifactValue,
        ],
        [
            TEXT_FORMAT_COMMONMARK,
            "The **D3 S** is cool",
            {
                value: "<p>The <strong>D3 S</strong> is cool</p>",
                format: TEXT_FORMAT_HTML,
                commonmark: "The **D3 S** is cool",
            } as TextFieldArtifactValue,
        ],
    ])(
        `returns the %s value from an artifact in an object`,
        (expected_format, expected_content, artifact_value) => {
            const text_field_value = formatExistingValue(artifact_value);

            expect(text_field_value.content).toStrictEqual(expected_content);
            expect(text_field_value.format).toStrictEqual(expected_format);
        },
    );
});
