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
 */

import CodeMirror from "codemirror";
import "codemirror/mode/css/css.js";
import "codemirror/mode/htmlmixed/htmlmixed.js";

type EditorMode = "text/css" | "htmlmixed";

function instantiateEditor(
    textarea_id: string,
    mode: EditorMode,
): CodeMirror.EditorFromTextArea | undefined {
    const textarea = document.getElementById(textarea_id);
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return undefined;
    }

    return CodeMirror.fromTextArea(textarea, {
        extraKeys: { "Ctrl-Space": "autocomplete" },
        mode,
        lineNumbers: true,
        indentUnit: 4,
        lineWrapping: true,
    });
}

export function initiateStylesCodeEditor(): CodeMirror.EditorFromTextArea | undefined {
    return instantiateEditor("input-style", "text/css");
}

export function initiateTitlePageContentCodeEditor(): CodeMirror.EditorFromTextArea | undefined {
    return instantiateEditor("input-title-page-content", "htmlmixed");
}

export function initiateHeaderContentCodeEditor(): CodeMirror.EditorFromTextArea | undefined {
    return instantiateEditor("input-header-content", "htmlmixed");
}

export function initiateFooterContentCodeEditor(): CodeMirror.EditorFromTextArea | undefined {
    return instantiateEditor("input-footer-content", "htmlmixed");
}
