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
import type { EditorView } from "@codemirror/view";

export function insertAllowedFieldInCodeMirror(event: Event, editor: EditorView): void {
    if (!(event.target instanceof HTMLOptionElement)) {
        return;
    }
    let value = event.target.value;
    if (isTextNotSelected(editor)) {
        value = value + " ";
        if (shouldASpaceBePrefixed(editor)) {
            value = " " + value;
        }
    }
    editor.dispatch(editor.state.replaceSelection(value));
    editor.focus();
    event.target.selected = false;
}

function isTextNotSelected(editor: EditorView): boolean {
    return editor.state.selection.main.empty;
}

function shouldASpaceBePrefixed(editor: EditorView): boolean {
    return (
        editor.state.selection.main.head !== 0 &&
        editor.state.sliceDoc(
            editor.state.selection.main.head - 1,
            editor.state.selection.main.head,
        ) !== " "
    );
}
