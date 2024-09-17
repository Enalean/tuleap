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
import { LinkUrlExtractor } from "./LinkUrlExtractor";
import { FindEditorNodeAtPositionStub } from "./stubs/FindEditorNodeAtPositionStub";
import { DetectLinkNodeStub } from "./stubs/DetectLinkNodeStub";
import { createLocalDocument } from "../../../helpers/helper-for-test";
import type { EditorNode } from "../../../types/internal-types";
import { custom_schema } from "../../../custom_schema";
import { DOMParser } from "prosemirror-model";

describe("LinkUrlExtractor", () => {
    let doc: Document;

    beforeEach(() => {
        doc = createLocalDocument();
    });

    it("When no EditorNode is found at the given position, then it should return an empty string", () => {
        const url = LinkUrlExtractor(
            FindEditorNodeAtPositionStub.withNoEditorNode(),
            DetectLinkNodeStub.withoutLinkNode(),
        ).extractLinkUrl(1);

        expect(url).toBe("");
    });

    it("When the EditorNode at the given position is not a link node, then it should return an empty string", () => {
        const url = LinkUrlExtractor(
            FindEditorNodeAtPositionStub.withNode(
                doc.createElement("div") as unknown as EditorNode,
            ),
            DetectLinkNodeStub.withoutLinkNode(),
        ).extractLinkUrl(1);

        expect(url).toBe("");
    });

    it("When the Editor node at the given position is a link node, then it should return its href attribute", () => {
        const href = "https://example.com";
        const editor_node = {
            marks: [
                {
                    attrs: {
                        title: "See example",
                        href,
                    },
                    type: DOMParser.fromSchema(custom_schema).schema.marks.link,
                },
            ],
        } as unknown as EditorNode;

        const url = LinkUrlExtractor(
            FindEditorNodeAtPositionStub.withNode(editor_node),
            DetectLinkNodeStub.withLinkNode(),
        ).extractLinkUrl(1);

        expect(url).toBe(href);
    });
});
