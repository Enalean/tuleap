/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { EditorView } from "prosemirror-view";
import { createLocalDocument } from "../../helpers";
import { buildCustomSchema } from "../../custom_schema";
import { DOMParser } from "prosemirror-model";
import { EditorState } from "prosemirror-state";
import { buildInputRules } from "../toolbar/input-rules";

const buildEditorView = (editor_text_content: string): EditorView => {
    const html_doc = createLocalDocument();
    const content = html_doc.createElement("div");

    content.insertAdjacentHTML("afterbegin", `<p>${editor_text_content}</p>`);

    const schema = buildCustomSchema();
    const doc = DOMParser.fromSchema(schema).parse(content);
    const state = EditorState.create({
        doc,
        schema,
        plugins: [buildInputRules(schema)],
    });
    return new EditorView(html_doc.createElement("div"), { state });
};

const triggerInputRule = (view: EditorView, content_text: string): void => {
    view.someProp("handleTextInput", (f) => {
        const text = content_text + ":";
        return f(view, 1, text.length, text);
    });
};

describe("emojis", () => {
    it("Should replace the trigger with the corresponding emoji", () => {
        const content = "This is some :frog";
        const view = buildEditorView(content);

        triggerInputRule(view, content);

        expect(view.dom.innerHTML).toBe("<p>This is some 🐸</p>");
    });

    it("Should keep the sentence when there is no corresponding emoji", () => {
        const content = "This is some :blabla";
        const view = buildEditorView(content);

        triggerInputRule(view, content);

        expect(view.dom.innerHTML).toBe("<p>This is some :blabla</p>");
    });
});
