/**
 * Copyright (c) Enalean 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import type { KeyBinding, ViewUpdate } from "@codemirror/view";
import { EditorView, keymap, placeholder } from "@codemirror/view";
import { defaultKeymap, history, historyKeymap } from "@codemirror/commands";
import type { TQLDefinition } from "./language";
import { tql } from "./language";
import { autocompletion, completeFromList } from "@codemirror/autocomplete";

export type TQLCodeMirrorEditor = EditorView;

export function codeMirrorify(
    textarea_element: HTMLTextAreaElement,
    submitFormCallback: () => void,
    tql_definition: TQLDefinition,
    placeholder_text: string,
    update_callback: (() => void) | null = null,
): TQLCodeMirrorEditor {
    const submit_keybinding: KeyBinding = {
        key: "Ctrl-Enter",
        run: function () {
            submitFormCallback();
            return true;
        },
    };

    const full_update_callback = (update: ViewUpdate): void => {
        if (update_callback !== null && update.docChanged) {
            update_callback();
        }
    };

    const editor = new EditorView({
        doc: textarea_element.value,
        extensions: [
            history(),
            keymap.of([submit_keybinding, ...defaultKeymap, ...historyKeymap]),
            tql(tql_definition),
            autocompletion({
                override: [completeFromList(tql_definition.autocomplete)],
                icons: false,
            }),
            EditorView.updateListener.of(full_update_callback),
            EditorView.lineWrapping,
            placeholder(placeholder_text),
        ],
    });

    if (textarea_element.parentNode) {
        textarea_element.parentNode.insertBefore(editor.dom, textarea_element);
    }
    textarea_element.style.display = "none";
    if (textarea_element.form !== null) {
        textarea_element.form.addEventListener("submit", () => {
            textarea_element.value = editor.state.doc.toString();
            submitFormCallback();
        });
    }

    return editor;
}
