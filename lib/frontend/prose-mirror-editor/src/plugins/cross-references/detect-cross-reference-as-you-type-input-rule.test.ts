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

import { describe, beforeEach, it, expect } from "vitest";
import { EditorState } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { EditorView } from "prosemirror-view";
import { inputRules } from "prosemirror-inputrules";
import { createLocalDocument } from "../../helpers";
import { buildCustomSchema } from "../../custom_schema";
import { DetectCrossReferenceAsYouTypeInputRule } from "./detect-cross-reference-as-you-type-input-rule";

const project_id = 120;

const triggerInputRule = (view: EditorView, content_text: string): void => {
    view.someProp("handleTextInput", (f) => {
        const text = content_text + " ";
        return f(view, 1, text.length, text);
    });
};

describe("detect-cross-reference-as-you-type-input-rule", () => {
    let doc: Document;

    beforeEach(() => {
        doc = createLocalDocument();
    });

    const buildEditorView = (content: HTMLElement): EditorView => {
        const schema = buildCustomSchema();

        const state = EditorState.create({
            doc: DOMParser.fromSchema(schema).parse(content),
            schema,
            plugins: [
                inputRules({
                    rules: [DetectCrossReferenceAsYouTypeInputRule(project_id)],
                }),
            ],
        });
        return new EditorView(doc.createElement("div"), { state });
    };

    const buildSimpleParagraphFromText = (text: string): HTMLParagraphElement => {
        const content = document.createElement("p");
        content.appendChild(doc.createTextNode(text));

        return content;
    };

    it(`When the user has typed a cross reference, and enters a space character right after it
        Then it should add an async-cross-reference mark to it and a space character`, () => {
        const reference = "art #123";
        const typed_text = `This document references ${reference}`;
        const content = buildSimpleParagraphFromText(typed_text);
        const view = buildEditorView(content);

        triggerInputRule(view, typed_text);

        expect(view.dom.innerHTML).toBe(
            `<p>This document references <async-cross-reference>${reference}</async-cross-reference> </p>`,
        );
    });

    it(`When the user has typed something that isn't recognized as a cross-reference, then it should do nothing
        Then it should add an async-cross-reference mark to it and a space character`, () => {
        const typed_text = `This document references nothing`;
        const content = buildSimpleParagraphFromText(typed_text);
        const view = buildEditorView(content);

        triggerInputRule(view, typed_text);

        expect(view.dom.innerHTML).toBe(`<p>${typed_text}</p>`);
    });

    it("When the user has inserted a link whose text is a cross-reference, then it should do nothing", () => {
        const content = document.createElement("p");
        const link = document.createElement("a");

        link.href = "https://example.com";
        link.appendChild(doc.createTextNode("art #123"));

        content.append(doc.createTextNode("A linkified cross-reference to "), link);

        const view = buildEditorView(content);

        triggerInputRule(view, "A linkified cross-reference to art #123");

        expect(view.dom.innerHTML).toBe(
            `<p>A linkified cross-reference to <a href="https://example.com">art #123</a></p>`,
        );
    });
});
