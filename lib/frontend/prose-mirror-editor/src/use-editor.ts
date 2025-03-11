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
import { buildCustomSchema } from "./custom_schema";
import type { EditorNodes } from "./custom_schema";
import type { PluginDropFile, PluginInput, SerializeDOM } from "./plugins";
import { initLinkPopoverPlugin, setupToolbar } from "./plugins";
import type { GetText } from "@tuleap/gettext";

import {
    getLocaleWithDefault,
    getPOFileFromLocaleWithoutExtension,
    initGettext,
} from "@tuleap/gettext";
import { v4 as uuidv4 } from "uuid";
import { initPluginCloseMarksAfterSpace } from "./plugins/close-marks-after-space";
import { type ToolbarBus } from "./plugins/toolbar/helper/toolbar-bus";
import { initCrossReferencesPlugins } from "./plugins/cross-references";
import { buildDOMSerializer } from "./plugins/input/DomSerializer";
import {
    initAddMarkAfterEnterPlugin,
    buildAddMarkAfterEnterPluginMap,
} from "./plugins/add-mark-after-enter";

export type UseEditorType = {
    editor: EditorView;
    state: EditorState;
    resetContent: (initialContent: HTMLElement) => Promise<void>;
};

export type EditorConfigOptions = {
    custom_editor_nodes: EditorNodes;
    are_headings_enabled: boolean;
    are_subtitles_enabled: boolean;
};

export async function useEditor(
    editor_element: HTMLElement,
    setupUploadPlugin: (gettext_provider: GetText) => PluginDropFile,
    setupInputPlugin: (serializer: SerializeDOM) => PluginInput,
    setupAdditionalPlugins: () => Plugin[],
    is_upload_allowed: boolean,
    initial_content: HTMLElement,
    project_id: number,
    toolbar_bus: ToolbarBus,
    config_options: EditorConfigOptions = {
        custom_editor_nodes: {},
        are_headings_enabled: true,
        are_subtitles_enabled: false,
    },
): Promise<UseEditorType> {
    const gettext_provider = await initGettext(
        getLocaleWithDefault(document),
        "prose-mirror",
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const upload_plugin = setupUploadPlugin(gettext_provider);

    const schema = buildCustomSchema(config_options.custom_editor_nodes);
    const editor_id = uuidv4();
    const plugins: Plugin[] = [
        ...setupAdditionalPlugins(),
        setupInputPlugin(buildDOMSerializer(schema)),
        upload_plugin,
        ...(is_upload_allowed
            ? [
                  dropCursor({
                      color: false,
                      width: 2,
                      class: "prose-mirror-editor-dropcursor",
                  }),
              ]
            : []),
        initLinkPopoverPlugin(document, gettext_provider, editor_id),
        ...setupToolbar(
            schema,
            toolbar_bus,
            config_options.are_headings_enabled,
            config_options.are_subtitles_enabled,
        ),
        initPluginCloseMarksAfterSpace(),
        ...initCrossReferencesPlugins(project_id),
        initAddMarkAfterEnterPlugin(buildAddMarkAfterEnterPluginMap(schema, project_id)),
    ];

    const state: EditorState = getState(initial_content);
    const editor: EditorView = new EditorView(editor_element, {
        state,
    });

    async function resetContent(initial_content: HTMLElement): Promise<void> {
        await upload_plugin.cancelOngoingUpload();
        const state = getState(initial_content);
        editor.updateState(state);
    }

    function getState(initial_content: Node): EditorState {
        return EditorState.create({
            doc: DOMParser.fromSchema(schema).parse(initial_content, { preserveWhitespace: true }),
            schema,
            plugins,
        });
    }

    return {
        editor,
        state,
        resetContent,
    };
}
