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

import { describe, it, expect } from "vitest";
import type { Selection } from "prosemirror-state";
import { FindEditorNodeAtPositionStub } from "../../../helpers/stubs/FindEditorNodeAtPositionStub";
import { custom_schema } from "../../../custom_schema";
import type { EditorNode } from "../../../types/internal-types";
import { ImageFromSelectionExtractor } from "./ImageFromSelectionExtractor";

describe("ImageFromSelectionExtractor", () => {
    it("When the selection $anchor and $head content length is grater than 1, then it should return true", () => {
        const selection = {
            $anchor: { pos: 5 },
            $head: { pos: 10 },
            from: 5,
        } as unknown as Selection;

        const extractor = ImageFromSelectionExtractor(
            FindEditorNodeAtPositionStub.withNode({
                type: custom_schema.nodes.image,
            } as EditorNode),
        );

        expect(extractor.extractImageProperties(selection)).toBe(null);
    });

    it("should return null when no node is found at the cursor position", () => {
        const selection = {
            $anchor: { pos: 10 },
            $head: { pos: 11 },
            from: 10,
        } as unknown as Selection;

        const extractor = ImageFromSelectionExtractor(
            FindEditorNodeAtPositionStub.withNoEditorNode(),
        );

        expect(extractor.extractImageProperties(selection)).toBe(null);
    });

    it("should return null when the node at the cursor position is not a image node", () => {
        const selection = {
            $anchor: { pos: 10 },
            $head: { pos: 11 },
            from: 10,
        } as unknown as Selection;

        const extractor = ImageFromSelectionExtractor(
            FindEditorNodeAtPositionStub.withNode({
                type: custom_schema.nodes.custom_hard_break,
            } as EditorNode),
        );

        expect(extractor.extractImageProperties(selection)).toBe(null);
    });

    it("should return the properties of the image node found at the cursor position", () => {
        const selection = {
            $anchor: { pos: 10 },
            $head: { pos: 11 },
            from: 10,
        } as unknown as Selection;

        const image_node_attributes = {
            src: "https://example.com",
            title: "An example image",
            alt: "An example image",
        };

        const extractor = ImageFromSelectionExtractor(
            FindEditorNodeAtPositionStub.withNode({
                type: custom_schema.nodes.image,
                attrs: image_node_attributes,
            } as unknown as EditorNode),
        );

        expect(extractor.extractImageProperties(selection)).toStrictEqual({
            src: image_node_attributes.src,
            title: image_node_attributes.title,
        });
    });
});
