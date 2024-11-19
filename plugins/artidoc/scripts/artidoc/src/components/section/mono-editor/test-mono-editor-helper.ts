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

import { buildCustomSchema } from "@tuleap/prose-mirror-editor";
import { artidoc_editor_schema } from "@/components/section/mono-editor/artidoc-editor-schema";
import type { Plugin } from "prosemirror-state";
import { EditorState } from "prosemirror-state";
import { EditorView } from "prosemirror-view";

// Note:
// <as> = <artidoc-section>
// <ast> = <artidoc-section-title>
// <asd> = <artidoc-section-description>

// Positioning:
// 0    1     2 3 4 5 6 7 8 9 10 11      12     13   14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29    30     31      32
//  <as> <ast> T h e _ t i t l  e  </ast>  <asd>  <p>  T  h  e  _  d  e  s  c  r  i  p  t  i  o  n  </p>  </asd>  </as>

export const END_OF_TITLE_POSITION = 11;
export const SOMEWHERE_IN_THE_TITLE_POSITION = 7; // [2;11]
export const START_OF_DESCRIPTION_POSITION = 14;
export const SOMEWHERE_IN_THE_DESCRIPTION_POSITION = 18; // [14;29]

const schema = buildCustomSchema(artidoc_editor_schema);
const doc = schema.node("doc", null, [
    schema.node("artidoc_section", null, [
        schema.node("artidoc_section_title", null, schema.text("The title")),
        schema.node("artidoc_section_description", null, [
            schema.node("paragraph", null, schema.text("The description")),
        ]),
    ]),
]);

export const initStateWithPlugins = (plugins: readonly Plugin[]): EditorState => {
    return EditorState.create({
        doc,
        schema,
        plugins,
    });
};

export const initViewWithState = (state: EditorState): EditorView => {
    const editor_element = document.createElement("div");
    document.body.appendChild(editor_element);

    return new EditorView(editor_element, { state });
};
