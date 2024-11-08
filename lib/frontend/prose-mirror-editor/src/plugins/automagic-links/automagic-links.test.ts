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
import { automagicLinksInputRule } from "./automagic-links";

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
                rules: [automagicLinksInputRule()],
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

describe("automagic-links", () => {
    it.each([
        ["https://example.com"],
        ["https://example.com:443"],
        ["https://www.example.com#/app/"],
        ["https://www.example.com?query=abcd&stuff_id=123"],
        ["https://example.com/a?a[0]=a"],
        ["https://é.example.com"],
        ["https://العربية.example.com/"],
        ["https://127.0.0.1"],
        ["https://127.0.0.1:8080"],
        ["https://127.0.0.1/index.php"],
        ["https://example.example"],
    ])(
        "When the user has typed an https url, and enters a space right after it, then it should add a link mark to it and a space character",
        (url) => {
            const content_text = `This paragraph contains a link: ${url}`;
            const view = buildEditorView(content_text);

            triggerInputRule(view, content_text);

            const href = url.replace("&", "&amp;");
            expect(view.dom.innerHTML).toBe(
                `<p>This paragraph contains a link: <a href="${href}">${href}</a> </p>`,
            );
        },
    );

    it.each([
        ["example.com"],
        ["mailto:john.doe@example.com"],
        ["http://www.example.com"],
        ["www.example.com"],
        ["ftp://ftp.example.com/file"],
        ["news.us.example.com"],
    ])(
        "When the user has typed an url that does not respect the supported formats, then it should do nothing",
        (url) => {
            const content_text = `This paragraph contains a link: ${url}`;
            const view = buildEditorView(content_text);

            triggerInputRule(view, content_text);

            expect(view.dom.innerHTML).toBe(`<p>${content_text}</p>`);
        },
    );
});
