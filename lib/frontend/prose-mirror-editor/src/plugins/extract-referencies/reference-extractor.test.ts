/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { type CrossReference, getNodeText, hasAReference } from "./reference-extractor";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync } from "neverthrow";

describe("reference extractor", () => {
    it("extract reference when a reference is found", () => {
        const reference = {
            text: "art #12",
            link: "<a href='https://example.com'>art #12</a>",
        } as CrossReference;
        vi.spyOn(fetch_result, "postJSON").mockReturnValue(okAsync([reference]));
        getNodeText("art #12", 101).match(
            (references: Array<CrossReference>) => {
                expect(references).toEqual([reference]);
            },
            () => {
                throw new Error("reference extractor error");
            },
        );
    });

    it("does nothing when no reference", () => {
        getNodeText("this is a text", 101).match(
            (references: Array<CrossReference>) => {
                expect(references).toEqual([]);
            },
            () => {
                throw new Error("reference extractor error");
            },
        );
    });

    it.each<[string, boolean]>([
        ["art 123", false],
        ["art#123", false],
        ["art #", false],
        ["art #123", true],
        ["art #abc", true],
        ["art #abc:123", true],
        ["art #123:123", true],
        ["art #abc:abc", true],
        ["art #123-rev #123", true],
        ["art #123:wikipage/2", true],
        ["art #abc-def:ghi", true],
        ["art #abc-de_f:ghi", true],
        ["wiki #project:page/subpage&amp;toto&tutu & co", true],
        ["Merge &#039;ref #12784&#039; into stable/master", true],
        ["Merge &#039;ref #12784&#039; for doc #123", true],
        ["Merge &#x27;ref #12784&#x27; for doc #123", true],
        ["Merge &quot;ref #12784&quot; for doc #123", true],
        ["See ref #12784.", true],
        ["See ref #a.b-c_d/12784.", true],
    ])("extract references (%s, %b)", (reference, expected_result) => {
        expect(hasAReference(reference)).toBe(expected_result);
    });
});
