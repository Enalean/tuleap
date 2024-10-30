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
import type { Selection } from "prosemirror-state";
import type { EditorNode } from "../../../types/internal-types";
import { buildCustomSchema } from "../../../custom_schema";
import type { DetectParagraphsInSelection } from "./ParagraphsInSelectionDetector";
import { ParagraphsInSelectionDetector } from "./ParagraphsInSelectionDetector";

const buildTreeWithNodes = (nodes: EditorNode[]): EditorNode => {
    return {
        nodesBetween: (from: number, to: number, callback: (node: EditorNode) => void) => {
            nodes.forEach((node) => callback(node));
        },
    } as unknown as EditorNode;
};

const selection = {} as Selection;
const custom_schema = buildCustomSchema();

describe("ParagraphsInSelectionDetector", () => {
    let detector: DetectParagraphsInSelection;

    beforeEach(() => {
        detector = ParagraphsInSelectionDetector(custom_schema);
    });

    it("When the selection contains a heading, a code block, or both, then it should return false", () => {
        const tree_with_heading = buildTreeWithNodes([
            { type: custom_schema.nodes.heading } as EditorNode,
            { type: custom_schema.nodes.paragraph } as EditorNode,
            { type: custom_schema.nodes.text } as EditorNode,
        ]);

        const tree_with_code_block = buildTreeWithNodes([
            { type: custom_schema.nodes.paragraph } as EditorNode,
            { type: custom_schema.nodes.code_block } as EditorNode,
            { type: custom_schema.nodes.text } as EditorNode,
        ]);

        const tree_with_both = buildTreeWithNodes([
            { type: custom_schema.nodes.heading } as EditorNode,
            { type: custom_schema.nodes.paragraph } as EditorNode,
            { type: custom_schema.nodes.code_block } as EditorNode,
            { type: custom_schema.nodes.text } as EditorNode,
        ]);

        expect(detector.doesSelectionContainOnlyParagraphs(tree_with_heading, selection)).toBe(
            false,
        );
        expect(detector.doesSelectionContainOnlyParagraphs(tree_with_code_block, selection)).toBe(
            false,
        );
        expect(detector.doesSelectionContainOnlyParagraphs(tree_with_both, selection)).toBe(false);
    });

    it("When the selection contains only paragraphs and text blocks, then it should return true", () => {
        const tree = buildTreeWithNodes([
            { type: custom_schema.nodes.paragraph } as EditorNode,
            { type: custom_schema.nodes.text } as EditorNode,
            { type: custom_schema.nodes.paragraph } as EditorNode,
            { type: custom_schema.nodes.text } as EditorNode,
        ]);
        expect(detector.doesSelectionContainOnlyParagraphs(tree, selection)).toBe(true);
    });
});
