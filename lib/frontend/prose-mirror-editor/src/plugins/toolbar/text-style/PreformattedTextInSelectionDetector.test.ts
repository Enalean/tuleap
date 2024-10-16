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
import { custom_schema } from "../../../custom_schema";
import { CheckSelectedNodesHaveSameParentStub } from "./stub/CheckSelectedNodesHaveSameParentStub";
import type { DetectPreformattedTextInSelection } from "./PreformattedTextInSelectionDetector";
import { PreformattedTextInSelectionDetector } from "./PreformattedTextInSelectionDetector";

const buildTreeWithNodes = (nodes: EditorNode[]): EditorNode => {
    return {
        nodesBetween: (from: number, to: number, callback: (node: EditorNode) => void) => {
            nodes.forEach((node) => callback(node));
        },
    } as unknown as EditorNode;
};

const selection = {} as Selection;

describe("PreformattedTextInSelectionDetector", () => {
    it("When the nodes inside the selection do not have the same parent, then it should return false", () => {
        const detector = PreformattedTextInSelectionDetector(
            CheckSelectedNodesHaveSameParentStub.withoutSameParent(),
        );

        const tree = buildTreeWithNodes([]);

        expect(detector.doesSelectionContainOnlyPreformattedText(tree, selection)).toBe(false);
    });

    describe("Given that the nodes contained in the selection have the same parent", () => {
        let detector: DetectPreformattedTextInSelection;

        beforeEach(() => {
            detector = PreformattedTextInSelectionDetector(
                CheckSelectedNodesHaveSameParentStub.withSameParent(),
            );
        });

        it("When the selection does not contain a code block, then it should return false", () => {
            const tree = buildTreeWithNodes([
                { type: custom_schema.nodes.heading } as EditorNode,
                { type: custom_schema.nodes.paragraph } as EditorNode,
                { type: custom_schema.nodes.text } as EditorNode,
            ]);
            expect(detector.doesSelectionContainOnlyPreformattedText(tree, selection)).toBe(false);
        });

        it("When the selection contains a code block, then it should return true", () => {
            const tree = buildTreeWithNodes([
                { type: custom_schema.nodes.code_block } as EditorNode,
                { type: custom_schema.nodes.text } as EditorNode,
            ]);
            expect(detector.doesSelectionContainOnlyPreformattedText(tree, selection)).toBe(true);
        });
    });
});
