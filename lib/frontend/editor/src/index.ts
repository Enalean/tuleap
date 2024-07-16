/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
import "../themes/editor.scss";

import type { Plugin } from "prosemirror-state";
import { EditorState } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { DOMParser, Schema } from "prosemirror-model";
import { addListNodes } from "prosemirror-schema-list";
import { schema } from "prosemirror-schema-basic";
import { keymap } from "prosemirror-keymap";
import { baseKeymap } from "prosemirror-commands";
export * from "./plugins";
export type { EditorView };

const mySchema = new Schema({
    nodes: addListNodes(schema.spec.nodes, "paragraph block*", "block"),
    marks: schema.spec.marks,
});

export type UseEditorType = {
    editor: EditorView;
    state: EditorState;
    resetContent: (initialContent: HTMLElement) => void;
};

export function useEditor(
    query_selector: HTMLElement,
    externals_plugins: Plugin[],
    initial_content: HTMLElement,
): UseEditorType {
    const plugins: Plugin[] = externals_plugins.concat(keymap(baseKeymap));
    const state: EditorState = getState(initial_content);
    const editor: EditorView = new EditorView(query_selector, {
        state,
        attributes: {
            class: "ProseMirror-focused",
        },
    });

    function resetContent(initial_content: HTMLElement): void {
        const state = getState(initial_content);
        editor.updateState(state);
    }

    function getState(initial_content: HTMLElement): EditorState {
        return EditorState.create({
            doc: DOMParser.fromSchema(mySchema).parse(initial_content),
            schema: mySchema,
            plugins,
        });
    }

    return {
        editor,
        state,
        resetContent,
    };
}
