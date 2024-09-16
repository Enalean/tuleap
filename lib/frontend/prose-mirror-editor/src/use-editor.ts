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
import {
    initLinkPopoverPlugin,
    initPluginTransformInput,
    initPluginInput,
    setupToolbar,
} from "./plugins";
import type { GetText } from "@tuleap/gettext";

import {
    getLocaleWithDefault,
    getPOFileFromLocaleWithoutExtension,
    initGettext,
} from "@tuleap/gettext";
import { v4 as uuidv4 } from "uuid";
import type { CrossReference } from "./plugins/extract-referencies/reference-extractor";
import { initPluginCloseMarksAfterSpace } from "./plugins/close-marks-after-space";

export type UseEditorType = {
    editor: EditorView;
    state: EditorState;
    resetContent: (initialContent: HTMLElement) => Promise<void>;
};

export async function useEditor(
    query_selector: HTMLElement,
    setupUploadPlugin: (gettext_provider: GetText) => PluginDropFile,
    onChange: (new_text_content: string) => void,
    initial_content: HTMLElement,
    project_id: number,
    references: Array<CrossReference>,
): Promise<UseEditorType> {
    const gettext_provider = await initGettext(
        getLocaleWithDefault(document),
        "prose-mirror",
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const upload_plugin = setupUploadPlugin(gettext_provider);

    const editor_id = uuidv4();
    const plugins: Plugin[] = [
        initPluginInput(onChange),
        upload_plugin,
        dropCursor(),
        initLinkPopoverPlugin(document, gettext_provider, editor_id),
        ...setupToolbar(gettext_provider, editor_id),
        initPluginTransformInput(project_id, references),
        initPluginCloseMarksAfterSpace(),
    ];

    const state: EditorState = getState(initial_content);
    const editor: EditorView = new EditorView(query_selector, {
        state,
        attributes: {
            class: "ProseMirror-focused",
        },
    });

    async function resetContent(initial_content: HTMLElement): Promise<void> {
        await upload_plugin.cancelOngoingUpload();
        const state = getState(initial_content);
        editor.updateState(state);
    }

    function getState(initial_content: Node): EditorState {
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
