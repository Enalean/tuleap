/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import { FORMAT_COMMONMARK, FORMAT_TEXT } from "@tuleap/plugin-pullrequest-constants";
import { getContentFormat } from "./content-format";

describe("content-format", () => {
    it.each([
        [FORMAT_TEXT, false],
        [FORMAT_COMMONMARK, true],
    ])(
        "getContentFormat() - should return %s when is_comments_markdown_mode_enabled is %s",
        (expected_format, is_comments_markdown_mode_enabled) => {
            expect(getContentFormat(is_comments_markdown_mode_enabled)).toBe(expected_format);
        },
    );
});
