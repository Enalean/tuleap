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
import type { DetectHeadingsInSelection } from "./HeadingsInSelectionDetector";
import { HeadingsInSelectionDetector } from "./HeadingsInSelectionDetector";

const buildTreeWithNodes = (nodes: EditorNode[]): EditorNode => {
    return {
        nodesBetween: (from: number, to: number, callback: (node: EditorNode) => void) => {
            nodes.forEach((node) => callback(node));
        },
    } as unknown as EditorNode;
};

const selection = { from: 10, to: 20 } as unknown as Selection;

describe("HeadingsInSelectionDetector", () => {
    let detector: DetectHeadingsInSelection;

    beforeEach(() => {
        detector = HeadingsInSelectionDetector();
    });

    it("When no heading can be found in the selection, then it should return false", () => {
        const has_headings = detector.doesSelectionContainHeadings(
            buildTreeWithNodes([
                { type: custom_schema.nodes.paragraph } as EditorNode,
                { type: custom_schema.nodes.text } as EditorNode,
                { type: custom_schema.nodes.text } as EditorNode,
            ]),
            selection,
        );

        expect(has_headings).toBe(false);
    });

    it("When at least one heading is found in the selection, then it should return true", () => {
        const has_headings = detector.doesSelectionContainHeadings(
            buildTreeWithNodes([
                { type: custom_schema.nodes.heading } as EditorNode,
                { type: custom_schema.nodes.text } as EditorNode,
                { type: custom_schema.nodes.text } as EditorNode,
            ]),
            selection,
        );

        expect(has_headings).toBe(true);
    });
});
