/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import { DOMParser } from "prosemirror-model";
import { expect, describe, it } from "vitest";
import { EditorState } from "prosemirror-state";
import { createLocalDocument } from "../../../helpers";
import { buildCustomSchema } from "../../../custom_schema";
import type { Decoration } from "prosemirror-view";
import type { CrossReference } from "../reference-extractor";
import { PositionsInDescendentsFinder } from "../helpers/DescendentsContainingReferenceFinder";
import { ReferencePositionComputer } from "../helpers/ReferencePositionComputer";
import { ParentNodeRetriever } from "../helpers/ParentNodeRetriever";
import { ContextLengthComputer } from "../helpers/ContextLengthPositionComputer";
import { ReferenceWithContextGetter } from "../helpers/ReferenceWithContextGetter";
import { CrossReferencesDecorator } from "../cross-references-decorator";

// For whole file, expected value can be calculated easily:
// - `from` start a 1, with the sum of input written before the reference
// - `to` is from + reference text length
describe("references decorator for whole document", () => {
    let state: EditorState;
    const local_document: Document = createLocalDocument();
    const link_decorator = CrossReferencesDecorator(
        ReferencePositionComputer(
            ParentNodeRetriever(),
            ContextLengthComputer(),
            ReferenceWithContextGetter(),
        ),
        PositionsInDescendentsFinder(),
    );

    function initEditorWithText(text: string): void {
        const editor_content = local_document.createElement("div");
        const custom_schema = buildCustomSchema();

        editor_content.innerHTML = text;
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
        });
    }

    it("decorates links of tree content", () => {
        const references = [
            {
                text: "art #1",
                link: "https://example.com?goto=1",
                context: "with",
            },
            {
                text: "bugs #2",
                link: "https://example.com?goto=2",
                context: "request",
            },
        ];

        initEditorWithText(
            "<p>a text with art #1 and request bugs #2</p><br><p>and art #1 is present elsewhere</p>",
        );
        const node = state.doc;

        const expected_first_ref = buildDecoration(13, 20, references[0]);
        const expected_second_ref = buildDecoration(32, 40, references[1]);
        const expected_third_ref = buildDecoration(48, 55, references[0]);

        const result = link_decorator.decorateCrossReference(node, references);

        expect(result.find()).toEqual([
            expected_second_ref,
            expected_first_ref,
            expected_third_ref,
        ]);
    });

    it.each([
        ["art #1 and", 1, 8],
        ["the art #1", 5, 12],
    ])(
        "When text is %s, then decorations are set {from: %d, to: %d} ",
        (initial_content: string, expected_from: number, expected_to: number) => {
            const references = [
                {
                    text: "art #1",
                    link: "https://example.com?goto=1",
                    context: "",
                },
                {
                    text: "bugs #2",
                    link: "https://example.com?goto=2",
                    context: "",
                },
            ];

            initEditorWithText(initial_content);

            const node = state.doc;

            const expected_first_ref = buildDecoration(expected_from, expected_to, references[0]);

            const result = link_decorator.decorateCrossReference(node, references);
            expect(result.find()).toEqual([expected_first_ref]);
        },
    );

    it("When the text does not contain any cross reference, then no decorations are rendered ", () => {
        const references = [
            {
                text: "art #1",
                link: "https://example.com?goto=1",
                context: "with",
            },
        ];
        initEditorWithText("no ref");
        const node = state.doc;

        const result = link_decorator.decorateCrossReference(node, references);
        expect(result.find()).toEqual([]);
    });

    it("When the reference is found, but the context does not match anything in text, only correct decoration is rendered ", () => {
        const references = [
            {
                text: "art #1",
                link: "https://example.com?goto=1",
                context: "with",
            },
        ];

        initEditorWithText("the art #1 and non exiting art #123");

        const node = state.doc;

        const expected_first_ref = buildDecoration(5, 12, references[0]);
        const result = link_decorator.decorateCrossReference(node, references);
        expect(result.find()).toEqual([expected_first_ref]);
    });

    function buildDecoration(
        from: number,
        to: number,
        cross_reference: CrossReference,
    ): Decoration {
        return {
            from,
            to,
            type: {
                attrs: {
                    class: "cross-reference",
                    "data-href": cross_reference.link,
                },
                spec: {
                    type: "cross-ref-link",
                },
            },
        } as unknown as Decoration;
    }
});
