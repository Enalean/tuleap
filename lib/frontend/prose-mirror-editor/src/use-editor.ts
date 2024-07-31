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

import type { Plugin } from "prosemirror-state";
import { EditorState } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { DOMParser } from "prosemirror-model";
import { custom_schema } from "./custom_schema";
import { setupToolbar } from "./plugins";
import { getLocaleWithDefault, initGettextSync } from "@tuleap/gettext";
import fr_FR from "../po/fr_FR.po";

export const gettext_provider = initGettextSync(
    "prose-mirror",
    { fr_FR },
    getLocaleWithDefault(document),
);

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
    const plugins: Plugin[] = externals_plugins.concat(setupToolbar());
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
            doc: DOMParser.fromSchema(custom_schema).parse(initial_content),
            schema: custom_schema,
            plugins,
        });
    }

    return {
        editor,
        state,
        resetContent,
    };
}
