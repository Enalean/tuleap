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
import { ListsInSelectionDetector } from "./ListsInSelectionDetector";
import type { DetectListsInSelection } from "./ListsInSelectionDetector";

const buildTreeWithNodes = (nodes: EditorNode[]): EditorNode => {
    return {
        nodesBetween: (from: number, to: number, callback: (node: EditorNode) => void) => {
            nodes.forEach((node) => callback(node));
        },
    } as unknown as EditorNode;
};

const selection = {} as Selection;
const custom_schema = buildCustomSchema();

describe("ListsInSelectionDetector", () => {
    let detector: DetectListsInSelection;

    beforeEach(() => {
        detector = ListsInSelectionDetector(custom_schema);
    });

    it("When the selection contains at least one list, then it should return true", () => {
        const tree_with_ordered_list = buildTreeWithNodes([
            { type: custom_schema.nodes.ordered_list } as EditorNode,
            { type: custom_schema.nodes.paragraph } as EditorNode,
        ]);

        const tree_with_bullet_list = buildTreeWithNodes([
            { type: custom_schema.nodes.paragraph } as EditorNode,
            { type: custom_schema.nodes.bullet_list } as EditorNode,
        ]);

        expect(detector.doesSelectionContainLists(tree_with_ordered_list, selection)).toBe(true);
        expect(detector.doesSelectionContainLists(tree_with_bullet_list, selection)).toBe(true);
    });

    it("When the selection does not contain any list, then it should return false", () => {
        const tree = buildTreeWithNodes([
            { type: custom_schema.nodes.heading } as EditorNode,
            { type: custom_schema.nodes.paragraph } as EditorNode,
        ]);

        expect(detector.doesSelectionContainLists(tree, selection)).toBe(false);
    });
});
