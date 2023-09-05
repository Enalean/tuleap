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

import CodeMirror from "codemirror";
import "codemirror/addon/mode/simple";
import "codemirror/addon/hint/show-hint";
import "codemirror/addon/display/placeholder";
import { getHint } from "./autocompleter";
import type { TQLDefinition } from "./configuration";

export function initializeTQLMode(tql_mode_definition: TQLDefinition): void {
    CodeMirror.defineSimpleMode("tql", tql_mode_definition);
}

export type TQLCodeMirrorEditor = CodeMirror.Editor;

export function codeMirrorify(
    textarea_element: HTMLTextAreaElement,
    autocomplete_keywords: ReadonlyArray<string>,
    submitFormCallback: () => void,
): TQLCodeMirrorEditor {
    CodeMirror.commands.autocomplete = autocomplete;

    return CodeMirror.fromTextArea(textarea_element, {
        extraKeys: { "Ctrl-Space": "autocomplete", "Ctrl-Enter": submitFormCallback },
        lineNumbers: false,
        lineWrapping: true,
        mode: "tql",
        readOnly: textarea_element.readOnly ? "nocursor" : false,
    });

    function autocomplete(editor: TQLCodeMirrorEditor): void {
        editor.showHint({
            words: autocomplete_keywords,
            hint: getHint,
        });
    }
}
