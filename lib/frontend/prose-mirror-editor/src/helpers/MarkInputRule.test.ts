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

import { describe, expect, it } from "vitest";
import { EditorState } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { DOMParser } from "prosemirror-model";
import { inputRules } from "prosemirror-inputrules";
import type { InputRule } from "prosemirror-inputrules";
import type { DOMOutputSpec } from "../types";
import { boldRule, inlineCodeRule, italicRule } from "../plugins/toolbar/input-rules";
import { buildCustomSchema, prosemirror_nodes } from "../custom_schema";
import { createLocalDocument } from "./helper-for-test";

describe("markInputRule", () => {
    const schema = buildCustomSchema();

    const triggerInputRule = (view: EditorView, content_text: string): void => {
        view.someProp("handleTextInput", (f) => {
            const text = content_text;
            return f(view, 1, text.length + 1, text);
        });
    };

    const buildEditorView = (
        editor_text_content: string,
        ignore_input_rules_node: boolean,
        input_rules: InputRule[],
    ): EditorView => {
        const custom_schema = {
            node: {
                content: "text*",
                ignore_input_rules: ignore_input_rules_node,
                toDOM(): DOMOutputSpec {
                    return ["node", 0];
                },
            },
            ...prosemirror_nodes,
            doc: {
                content: "node",
            },
        };
        const html_doc = createLocalDocument();
        const content = html_doc.createElement("div");

        content.insertAdjacentHTML("afterbegin", `${editor_text_content}`);

        const schema = buildCustomSchema(custom_schema);
        const doc = DOMParser.fromSchema(schema).parse(content);
        const state = EditorState.create({
            doc,
            schema,
            plugins: [
                inputRules({
                    rules: input_rules,
                }),
            ],
        });

        return new EditorView(content, { state });
    };

    describe("boldRule", () => {
        const bold = "**bold**";
        const typed_text = `The document with ${bold}`;
        const input_rule = [boldRule(schema.marks.strong)];

        it("should apply bold mark when the property ignore_input_rules is false", () => {
            const view = buildEditorView(typed_text, false, input_rule);

            triggerInputRule(view, typed_text);

            expect(view.dom.innerHTML).toBe(`<node>The document with <strong>bold</strong></node>`);
        });

        it("should not apply bold mark when the property ignore_input_rules is true", () => {
            const view = buildEditorView(typed_text, true, input_rule);

            triggerInputRule(view, typed_text);

            expect(view.dom.innerHTML).toBe(`<node>${typed_text}</node>`);
        });
    });

    describe("inlineCodeRule", () => {
        const inline_code = "`inline code`";
        const typed_text = `The document with ${inline_code}`;
        const input_rule = [inlineCodeRule(schema.marks.code)];

        it("should apply inline code mark when the property ignore_input_rules is false", () => {
            const view = buildEditorView(typed_text, false, input_rule);

            triggerInputRule(view, typed_text);

            expect(view.dom.innerHTML).toBe(
                `<node>The document with <code>inline code</code></node>`,
            );
        });

        it("should not apply inline code mark when the property ignore_input_rules is true", () => {
            const view = buildEditorView(typed_text, true, input_rule);

            triggerInputRule(view, typed_text);

            expect(view.dom.innerHTML).toBe(`<node>${typed_text}</node>`);
        });
    });

    describe("italicRule", () => {
        const italic_with_asterisk = "*italic*";
        const typed_text = `The document with ${italic_with_asterisk}`;
        const input_rule = [italicRule(schema.marks.em)];

        it("should apply italic mark when the property ignore_input_rules is false", () => {
            const view = buildEditorView(typed_text, false, input_rule);

            triggerInputRule(view, typed_text);

            expect(view.dom.innerHTML).toBe(`<node>The document with <em>italic</em></node>`);
        });

        it("should not apply italic mark when the property ignore_input_rules is true", () => {
            const view = buildEditorView(typed_text, true, input_rule);

            triggerInputRule(view, typed_text);

            expect(view.dom.innerHTML).toBe(`<node>${typed_text}</node>`);
        });
    });
});
