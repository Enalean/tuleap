/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { EditorState } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { EditorView } from "prosemirror-view";
import { inputRules } from "prosemirror-inputrules";
import { createLocalDocument } from "../../helpers";
import { buildCustomSchema } from "../../custom_schema";
import { DetectCrossReferenceAsYouTypeInputRule } from "./detect-cross-reference-as-you-type-input-rule";

const project_id = 120;

const buildEditorView = (editor_text_content: string): EditorView => {
    const html_doc = createLocalDocument();
    const content = html_doc.createElement("div");

    content.insertAdjacentHTML("afterbegin", `<p>${editor_text_content}</p>`);

    const schema = buildCustomSchema();
    const doc = DOMParser.fromSchema(schema).parse(content);
    const state = EditorState.create({
        doc,
        schema,
        plugins: [
            inputRules({
                rules: [DetectCrossReferenceAsYouTypeInputRule(project_id)],
            }),
        ],
    });
    return new EditorView(html_doc.createElement("div"), { state });
};

const triggerInputRule = (view: EditorView, content_text: string): void => {
    view.someProp("handleTextInput", (f) => {
        const text = content_text + " ";
        return f(view, 1, text.length, text);
    });
};

describe("detect-cross-reference-as-you-type-input-rule", () => {
    it(`When the user has typed a cross reference, and enters a space character right after it
        Then it should add an async-cross-reference mark to it and a space character`, () => {
        const reference = "art #123";
        const typed_text = `This document references ${reference}`;
        const view = buildEditorView(typed_text);

        triggerInputRule(view, typed_text);

        expect(view.dom.innerHTML).toBe(
            `<p>This document references <async-cross-reference>${reference}</async-cross-reference> </p>`,
        );
    });

    it(`When the user has typed something that isn't recognized as a cross-reference, then it should do nothing
        Then it should add an async-cross-reference mark to it and a space character`, () => {
        const typed_text = `This document references nothing`;
        const view = buildEditorView(typed_text);

        triggerInputRule(view, typed_text);

        expect(view.dom.innerHTML).toBe(`<p>${typed_text}</p>`);
    });
});
