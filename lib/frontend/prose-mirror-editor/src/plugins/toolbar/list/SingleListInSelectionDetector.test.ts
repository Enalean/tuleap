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
import type { DetectSingleListInSelection } from "./SingleListInSelectionDetector";
import { SingleListInSelectionDetector } from "./SingleListInSelectionDetector";
import { custom_schema } from "../../../custom_schema";

const buildTreeWithNodes = (nodes: EditorNode[]): EditorNode => {
    return {
        nodesBetween: (from: number, to: number, callback: (node: EditorNode) => void) => {
            nodes.forEach((node) => callback(node));
        },
    } as unknown as EditorNode;
};

const selection = {} as Selection;

describe("SingleListInSelectionDetector", () => {
    let bullet_list_detector: DetectSingleListInSelection,
        ordered_list_detector: DetectSingleListInSelection;

    beforeEach(() => {
        bullet_list_detector = SingleListInSelectionDetector(custom_schema.nodes.bullet_list);
        ordered_list_detector = SingleListInSelectionDetector(custom_schema.nodes.ordered_list);
    });

    it("When there is only a list of the target type in the selection, then it should return true", () => {
        const tree_with_only_bullet_list = buildTreeWithNodes([
            { type: custom_schema.nodes.bullet_list } as EditorNode,
        ]);

        const tree_with_only_ordered_list = buildTreeWithNodes([
            { type: custom_schema.nodes.ordered_list } as EditorNode,
        ]);

        expect(
            bullet_list_detector.doesSelectionContainOnlyASingleList(
                tree_with_only_bullet_list,
                selection,
            ),
        ).toBe(true);
        expect(
            ordered_list_detector.doesSelectionContainOnlyASingleList(
                tree_with_only_ordered_list,
                selection,
            ),
        ).toBe(true);
    });

    it("When there is one list of the target type in the selection, but it contains other nodes as well, then it should return false", () => {
        const tree_with_bullet_list = buildTreeWithNodes([
            { type: custom_schema.nodes.bullet_list } as EditorNode,
            { type: custom_schema.nodes.paragraph } as EditorNode,
        ]);

        const tree_with_ordered_list = buildTreeWithNodes([
            { type: custom_schema.nodes.ordered_list } as EditorNode,
            { type: custom_schema.nodes.paragraph } as EditorNode,
        ]);

        expect(
            bullet_list_detector.doesSelectionContainOnlyASingleList(
                tree_with_bullet_list,
                selection,
            ),
        ).toBe(false);
        expect(
            ordered_list_detector.doesSelectionContainOnlyASingleList(
                tree_with_ordered_list,
                selection,
            ),
        ).toBe(false);
    });

    it("When there is more than one list of the target type in the selection, then it should return false", () => {
        const tree_with_ordered_lists = buildTreeWithNodes([
            { type: custom_schema.nodes.ordered_list } as EditorNode,
            { type: custom_schema.nodes.ordered_list } as EditorNode,
        ]);

        const tree_with_bullet_lists = buildTreeWithNodes([
            { type: custom_schema.nodes.bullet_list } as EditorNode,
            { type: custom_schema.nodes.bullet_list } as EditorNode,
        ]);

        expect(
            bullet_list_detector.doesSelectionContainOnlyASingleList(
                tree_with_bullet_lists,
                selection,
            ),
        ).toBe(false);
        expect(
            ordered_list_detector.doesSelectionContainOnlyASingleList(
                tree_with_ordered_lists,
                selection,
            ),
        ).toBe(false);
    });

    it("When there is no list of the target type in the selection, then it should return false", () => {
        const tree_with_only_bullet_list = buildTreeWithNodes([
            { type: custom_schema.nodes.bullet_list } as EditorNode,
        ]);

        const tree_with_only_ordered_list = buildTreeWithNodes([
            { type: custom_schema.nodes.ordered_list } as EditorNode,
        ]);

        expect(
            bullet_list_detector.doesSelectionContainOnlyASingleList(
                tree_with_only_ordered_list,
                selection,
            ),
        ).toBe(false);
        expect(
            ordered_list_detector.doesSelectionContainOnlyASingleList(
                tree_with_only_bullet_list,
                selection,
            ),
        ).toBe(false);
    });
});
