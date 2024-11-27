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
import type { Schema } from "prosemirror-model";
import type { EditorNode } from "../../../types/internal-types";
import { buildCustomSchema } from "../../../custom_schema";
import { TextNodeWithReferencesSplitter } from "./TextNodeWithReferencesSplitter";
import type { SplitTextNodeWithReferences } from "./TextNodeWithReferencesSplitter";

const project_id = 120;

describe("TextNodeWithReferencesSplitter", () => {
    let schema: Schema, splitter: SplitTextNodeWithReferences;

    beforeEach(() => {
        schema = buildCustomSchema();
        splitter = TextNodeWithReferencesSplitter(schema, project_id);
    });

    const buildTextNodeWithAsyncCrossReference = (text: string): EditorNode =>
        schema.text(text).mark([schema.marks.async_cross_reference.create({ text, project_id })]);

    it(`Given a text node
        When its text contains parts matching the Tuleap reference format
        Then it should split it and return a collection of text nodes:
        - Those containing the references will have an async_cross_reference Mark
        - The other won't`, () => {
        const text_node = schema.text(
            "This document references art #123 and art #124 (followup of art #123).",
        );

        expect(splitter.split(text_node)).toStrictEqual([
            schema.text("This document references "),
            buildTextNodeWithAsyncCrossReference("art #123"),
            schema.text(" and "),
            buildTextNodeWithAsyncCrossReference("art #124"),
            schema.text(" (followup of "),
            buildTextNodeWithAsyncCrossReference("art #123"),
            schema.text(")."),
        ]);
    });

    it(`Given a text node
        When its text contains several cross-references and it starts with one
        Then it should rebuild the sentence in the correct order`, () => {
        const text_node = schema.text("art #123 and art #124 are referenced in this document.");

        expect(splitter.split(text_node)).toStrictEqual([
            buildTextNodeWithAsyncCrossReference("art #123"),
            schema.text(" and "),
            buildTextNodeWithAsyncCrossReference("art #124"),
            schema.text(" are referenced in this document."),
        ]);
    });

    it(`Given a text node
        When its text does not contain parts matching the Tuleap reference format
        Then it should return a full copy of the original text node`, () => {
        const text_node = schema.text("This document references nothing.");

        expect(splitter.split(text_node)).toStrictEqual([text_node.copy(text_node.content)]);
    });

    it(`Given a not textual node, Then it should throw an error`, () => {
        const paragraph_node = schema.nodes.paragraph.create(
            {},
            schema.text("This document references nothing."),
        );

        expect(() => splitter.split(paragraph_node)).toThrowError();
    });
});
