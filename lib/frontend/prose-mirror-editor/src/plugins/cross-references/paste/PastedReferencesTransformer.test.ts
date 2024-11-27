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
import type { Schema, Slice } from "prosemirror-model";
import { DOMParser } from "prosemirror-model";
import { EditorView } from "prosemirror-view";
import { EditorState } from "prosemirror-state";
import { buildCustomSchema } from "../../../custom_schema";
import { createLocalDocument } from "../../../helpers";
import { PastedReferencesTransformer } from "./PastedReferencesTransformer";
import { TextNodeWithReferencesSplitter } from "./TextNodeWithReferencesSplitter";
import type { TransformPastedReferences } from "./PastedReferencesTransformer";

const project_id = 120;

describe("PastedReferencesTransformer", () => {
    let doc: Document, schema: Schema, view: EditorView, transformer: TransformPastedReferences;

    beforeEach(() => {
        doc = createLocalDocument();
        schema = buildCustomSchema();
        transformer = PastedReferencesTransformer(
            TextNodeWithReferencesSplitter(schema, project_id),
        );

        view = new EditorView(doc.createElement("div"), {
            state: EditorState.create({ schema }),
        });
    });

    const buildSliceFromContentString = (content: string): Slice => {
        const doc = createLocalDocument();
        const pasted_content = doc.createElement("div");
        pasted_content.insertAdjacentHTML("afterbegin", content);

        return DOMParser.fromSchema(schema).parseSlice(pasted_content);
    };

    const pasteSlice = (view: EditorView, slice: Slice): void => {
        view.dispatch(view.state.tr.replaceSelection(slice));
    };

    it(`Given a Slice generated from pasted content
        When it contains text having parts matching the Tuleap reference format
        Then it should:
        - find them
        - split them
        - append async_cross_references marks where needed
        And finally return a new Slice`, () => {
        const original_slice = buildSliceFromContentString(`
            <p>This paragraph references art #123</p>
            <p>art #345 and art #234 are referenced in this paragraph</p>
            <blockquote>This quote references doc #123</blockquote>
            <ol>
                <li>
                    <p>epic #10</p>
                    <ol>
                        <li>
                            <p>story #11</p>
                        </li>
                        <li>
                            <p>story #11</p>
                        </li>
                    </ol>
                </li>
            </ol>
        `);

        pasteSlice(view, transformer.transformPastedCrossReferencesToMark(original_slice));

        expect(view.dom.innerHTML).toMatchInlineSnapshot(`
          <p>This paragraph references <async-cross-reference>art #123</async-cross-reference></p>
          <p><async-cross-reference>art #345</async-cross-reference> and <async-cross-reference>art #234</async-cross-reference> are referenced in this paragraph</p>
          <blockquote>
            <p>This quote references <async-cross-reference>doc #123</async-cross-reference></p>
          </blockquote>
          <ol>
            <li>
              <p><async-cross-reference>epic #10</async-cross-reference></p>
              <ol>
                <li>
                  <p><async-cross-reference>story #11</async-cross-reference></p>
                </li>
                <li>
                  <p><async-cross-reference>story #11</async-cross-reference></p>
                </li>
              </ol>
            </li>
          </ol>
        `);
    });

    it(`Given a Slice generated from pasted content
        When it does not contain text having parts matching the Tuleap reference format
        Then it should preserve the original slice's structure`, () => {
        const original_slice = buildSliceFromContentString(`
            <p>This paragraph references nothing</p>
            <blockquote>This quote references nothing</blockquote>
            <ol>
                <li>
                    <p>A list item</p>
                    <ol>
                        <li>
                            <p>A nested list item</p>
                        </li>
                        <li>
                            <p>A nested list item</p>
                        </li>
                    </ol>
                </li>
            </ol>
        `);

        pasteSlice(view, transformer.transformPastedCrossReferencesToMark(original_slice));

        expect(view.dom.innerHTML).toMatchInlineSnapshot(`
          <p>This paragraph references nothing</p>
          <blockquote>
            <p>This quote references nothing</p>
          </blockquote>
          <ol>
            <li>
              <p>A list item</p>
              <ol>
                <li>
                  <p>A nested list item</p>
                </li>
                <li>
                  <p>A nested list item</p>
                </li>
              </ol>
            </li>
          </ol>
        `);
    });
});
