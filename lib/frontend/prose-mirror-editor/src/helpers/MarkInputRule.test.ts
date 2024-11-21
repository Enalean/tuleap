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

import { describe, expect, it } from "vitest";
import { EditorState } from "prosemirror-state";
import { buildCustomSchema } from "../custom_schema";
import { applyMark, getTextPositions, removeMarkdownCharacters } from "./MarkInputRule";

// 0   1 2 3 4 5 6 7 8 9    10
//  <p> * * b o l d * * </p>
//  <p> b o l d </p>

const MATCH = "**bold**";
const TEXT = "bold";
const REGEXP_MATCH_ARRAY: RegExpMatchArray = [MATCH, TEXT];
const MATCH_START_POSITION = 1;
const MATCH_END_POSITION = 9;
const TEXT_START_POSITION = 3;
const TEXT_END_POSITION = 7;
const MARK_START_POSITION = 1;
const MARK_END_POSITION = 5;

describe("markInputRule", () => {
    const schema = buildCustomSchema();
    const doc = schema.node("doc", null, [schema.node("paragraph", null, [schema.text(MATCH)])]);
    const state = EditorState.create({ doc });
    const tr = state.tr;

    describe("getTextPositions", () => {
        it("should return the start and end positions of the text to be marked", () => {
            const positions = getTextPositions(REGEXP_MATCH_ARRAY, MATCH_START_POSITION);

            expect(positions).toStrictEqual({
                start: TEXT_START_POSITION,
                end: TEXT_END_POSITION,
            });
        });
    });

    describe("removeMarkdownCharacters", () => {
        it("should remove the markdown characters", () => {
            removeMarkdownCharacters(
                tr,
                MATCH_START_POSITION,
                MATCH_END_POSITION,
                TEXT_START_POSITION,
                TEXT_END_POSITION,
            );

            expect(tr.doc.textContent).toBe(TEXT);
        });
    });

    describe("applyMark", () => {
        it("should apply mark to the text", () => {
            applyMark(tr, schema.marks.strong, MARK_START_POSITION, MARK_END_POSITION);

            const marks = tr.doc.nodeAt(MARK_START_POSITION)?.marks;

            if (!marks) {
                throw new Error(`Could not find the node at position ${MARK_START_POSITION}`);
            }

            expect(marks[0].type.name).toBe("strong");
        });
    });
});
