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
import { dropCursor } from "prosemirror-dropcursor";
import { custom_schema } from "./custom_schema";
import type { PluginDropFile } from "./plugins";
import { initPluginInput, setupToolbar } from "./plugins";
import type { GetText } from "@tuleap/gettext";
import {
    getLocaleWithDefault,
    getPOFileFromLocaleWithoutExtension,
    initGettext,
} from "@tuleap/gettext";

export type UseEditorType = {
    editor: EditorView;
    state: EditorState;
    resetContent: (initialContent: HTMLElement) => void;
};

export async function useEditor(
    query_selector: HTMLElement,
    setupUploadPlugin: (gettext_provider: GetText) => PluginDropFile,
    onChange: (new_text_content: string) => void,
    initial_content: HTMLElement,
): Promise<UseEditorType> {
    const gettext_provider = await initGettext(
        getLocaleWithDefault(document),
        "prose-mirror",
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const upload_plugin = setupUploadPlugin(gettext_provider);

    const plugins: Plugin[] = [
        initPluginInput(onChange),
        upload_plugin,
        dropCursor(),
        ...setupToolbar(gettext_provider),
    ];

    const state: EditorState = getState(initial_content);
    const editor: EditorView = new EditorView(query_selector, {
        state,
        attributes: {
            class: "ProseMirror-focused",
        },
    });

    function resetContent(initial_content: HTMLElement): void {
        upload_plugin.cancelOngoingUpload();
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
