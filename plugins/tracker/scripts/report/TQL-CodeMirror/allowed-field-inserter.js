/**
 * Copyright (c) 2017, Enalean. All rights reserved
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

export { insertAllowedFieldInCodeMirror };

function insertAllowedFieldInCodeMirror(event, query_rich_editor) {
    if (!(query_rich_editor instanceof CodeMirror)) {
        return;
    }

    const cursor_start = query_rich_editor.getCursor("from");
    const cursor_end = query_rich_editor.getCursor("to");
    let value = event.target.value;

    if (isTextNotSelected(cursor_start, cursor_end)) {
        value = value + " ";
        if (shouldASpaceBePrefixed(query_rich_editor, cursor_start)) {
            value = " " + value;
        }
        query_rich_editor.doc.setSelection(cursor_start);
    }

    query_rich_editor.doc.replaceSelection(value);
    query_rich_editor.focus();
    event.target.selected = false;
}

function isTextNotSelected(cursor_start, cursor_end) {
    return cursor_start.line === cursor_end.line && cursor_start.ch === cursor_end.ch;
}

function shouldASpaceBePrefixed(query_rich_editor, cursor_start) {
    const line_start = query_rich_editor.getLine(cursor_start.line);

    return cursor_start.ch !== 0 && line_start.charAt(cursor_start.ch - 1) !== " ";
}
