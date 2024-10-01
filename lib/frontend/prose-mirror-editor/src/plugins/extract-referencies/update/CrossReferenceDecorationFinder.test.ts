/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { Decoration, DecorationSet } from "prosemirror-view";
import { EditorState } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { createCrossReferenceDecoration } from "../../../helpers/create-cross-reference-decoration";
import { custom_schema } from "../../../custom_schema";
import { createLocalDocument } from "../../../helpers";
import { CrossReferenceDecorationFinder } from "./CrossReferenceDecorationFinder";
import type { FindCrossReferenceDecoration } from "./CrossReferenceDecorationFinder";

const art_123_position = { from: 10, to: 18 };
const art_456_position = { from: 23, to: 31 };
const other_decoration_position = { from: 0, to: 9 };

describe("CrossReferenceDecorationFinder", () => {
    let art_123_decoration: Decoration,
        art_456_decoration: Decoration,
        finder: FindCrossReferenceDecoration;

    beforeEach(() => {
        const editor_content = createLocalDocument().createElement("div");
        editor_content.textContent = "Reference art #123 and art #456";

        const state = EditorState.create({
            schema: custom_schema,
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
        });

        art_123_decoration = createCrossReferenceDecoration(art_123_position, {
            text: "art #123",
            link: "https://example.com",
        });
        art_456_decoration = createCrossReferenceDecoration(art_456_position, {
            text: "art #456",
            link: "https://example.com",
        });

        finder = CrossReferenceDecorationFinder(
            DecorationSet.create(state.doc, [
                art_123_decoration,
                art_456_decoration,
                Decoration.inline(other_decoration_position.from, other_decoration_position.to, {
                    class: "keyword-decoration",
                }),
            ]),
        );
    });

    it("When no cross-reference decoration is found at the given cursor position, then it should return null", () => {
        expect(finder.findFirstDecorationAtCursorPosition(art_123_position.to + 2)).toBe(null);
        expect(finder.findFirstDecorationAtCursorPosition(other_decoration_position.from)).toBe(
            null,
        );
    });

    it("When a cross-reference decoration is found, then it should return it", () => {
        expect(finder.findFirstDecorationAtCursorPosition(art_123_position.from)).toStrictEqual(
            art_123_decoration,
        );
        expect(finder.findFirstDecorationAtCursorPosition(art_456_position.from)).toStrictEqual(
            art_456_decoration,
        );
    });
});
