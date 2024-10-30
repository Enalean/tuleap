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
import { EditorState } from "prosemirror-state";

import { DOMParser } from "prosemirror-model";
import type { EditorNode } from "../../../types/internal-types";
import { buildCustomSchema } from "../../../custom_schema";
import type { DetectLinkNode } from "./LinkNodeDetector";
import { LinkNodeDetector } from "./LinkNodeDetector";

const custom_schema = buildCustomSchema();

describe("LinkNodeDetector", () => {
    let detector: DetectLinkNode;

    beforeEach(() => {
        detector = LinkNodeDetector(
            EditorState.create({
                schema: custom_schema,
            }),
        );
    });

    it("Given a node, When it contains a link mark, Then it should return false", () => {
        const editor_node = {
            marks: [
                {
                    attrs: {
                        title: "See example",
                        href: "https://example.com/",
                    },
                    type: DOMParser.fromSchema(custom_schema).schema.marks.link,
                },
            ],
        } as unknown as EditorNode;

        expect(detector.isLinkNode(editor_node)).toBe(true);
    });

    it("Given a node, When it does NOT contain a link mark, Then it should return false", () => {
        const editor_node = { marks: [] } as unknown as EditorNode;

        expect(detector.isLinkNode(editor_node)).toBe(false);
    });
});
