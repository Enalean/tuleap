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
import type { Attrs, NodeType } from "prosemirror-model";
import type { EditorNode } from "../../../types/internal-types";
import { custom_schema } from "../../../custom_schema";
import type { RetrieveHeading } from "./HeadingInSelectionRetriever";
import { HeadingInSelectionRetriever } from "./HeadingInSelectionRetriever";
import { CheckSelectedNodesHaveSameParentStub } from "./stub/CheckSelectedNodesHaveSameParentStub";

const buildEditorNode = (type: NodeType, attrs: Attrs = {}): EditorNode =>
    ({ type, attrs }) as EditorNode;

const buildTreeWithNodes = (nodes: EditorNode[]): EditorNode => {
    return {
        nodesBetween: (from: number, to: number, callback: (node: EditorNode) => void) => {
            nodes.forEach((node) => callback(node));
        },
    } as unknown as EditorNode;
};

describe("HeadingInSelectionRetriever", () => {
    let retriever: RetrieveHeading;

    beforeEach(() => {
        retriever = HeadingInSelectionRetriever(
            CheckSelectedNodesHaveSameParentStub.withSameParent(),
        );
    });

    it("When the nodes in the selection do not have the same parent element, then it should return null", () => {
        const retriever = HeadingInSelectionRetriever(
            CheckSelectedNodesHaveSameParentStub.withoutSameParent(),
        );
        const heading = retriever.retrieveHeadingInSelection({} as EditorNode, {} as Selection);

        expect(heading).toBeNull();
    });

    describe("Given an empty selection", () => {
        const tree = {} as EditorNode;

        it("When the node found at the cursor position is not a heading, then it should return null", () => {
            const selection = {
                empty: true,
                $head: {
                    node: () => buildEditorNode(custom_schema.nodes.paragraph),
                },
            } as Selection;
            const heading = retriever.retrieveHeadingInSelection(tree, selection);

            expect(heading).toBeNull();
        });

        it("When the node found at the cursor position is a heading, then it should return a Heading with the level found in the node attrs", () => {
            const level = 2;
            const selection = {
                empty: true,
                $head: {
                    node: () =>
                        buildEditorNode(custom_schema.nodes.heading, {
                            level,
                        }),
                },
            } as Selection;
            const heading = retriever.retrieveHeadingInSelection(tree, selection);

            expect(heading).toStrictEqual({ level });
        });
    });

    describe("Given a not empty selection", () => {
        const selection = { empty: false, from: 10, to: 50 } as Selection;

        it("When it does not contain any heading node, then it should return null", () => {
            const tree = buildTreeWithNodes([
                buildEditorNode(custom_schema.nodes.hard_break),
                buildEditorNode(custom_schema.nodes.paragraph),
            ]);
            const heading = retriever.retrieveHeadingInSelection(tree, selection);

            expect(heading).toBeNull();
        });

        it("When it contains a heading node, then it should return a Heading with the level found in the node attrs", () => {
            const level = 3;
            const tree = buildTreeWithNodes([
                buildEditorNode(custom_schema.nodes.heading, { level }),
                buildEditorNode(custom_schema.nodes.text),
            ]);
            const heading = retriever.retrieveHeadingInSelection(tree, selection);

            expect(heading).toStrictEqual({ level });
        });
    });
});
