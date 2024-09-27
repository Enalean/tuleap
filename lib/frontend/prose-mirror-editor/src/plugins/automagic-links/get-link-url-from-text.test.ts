/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, expect, it } from "vitest";
import { getLinkUrlFromText } from "./get-link-url-from-text";

describe("getLinkUrlFromText", () => {
    it.each([
        ["https://example.com", "https://example.com"],
        ["http://url", "http://url"],
        ["http://www.url.org", "http://www.url.org"],
        ["some text", undefined],
        ["", undefined],
        ["http://", undefined],
        ["https://", undefined],
    ])(`reports when text is %s extracted url should be %s`, (text, expected_url) => {
        expect(getLinkUrlFromText(text)).toBe(expected_url);
    });
});
