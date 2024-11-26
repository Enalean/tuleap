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
import { EditorView } from "prosemirror-view";
import { DOMParser } from "prosemirror-model";
import { EditorState, TextSelection } from "prosemirror-state";
import { createLocalDocument } from "../../helpers";
import { buildCustomSchema } from "../../custom_schema";
import { initAddMarkAfterEnterPlugin } from "./add-mark-after-enter";
import { buildAddMarkAfterEnterPluginMap } from "./build-regexp-to-mark-builder-map";

const project_id = 120;

const buildView = (text: string): EditorView => {
    const doc = createLocalDocument();
    const schema = buildCustomSchema();
    const content = document.createElement("p");
    content.appendChild(doc.createTextNode(text));

    return new EditorView(doc.createElement("div"), {
        state: EditorState.create({
            doc: DOMParser.fromSchema(schema).parse(content),
            schema,
            plugins: [
                initAddMarkAfterEnterPlugin(buildAddMarkAfterEnterPluginMap(schema, project_id)),
            ],
        }),
    });
};

const triggerEvent = (view: EditorView): void => {
    view.dispatch(
        view.state.tr.setSelection(
            TextSelection.create(view.state.doc, view.state.doc.content.size - 1),
        ),
    );
    view.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter" }));
};

describe("add-mark-after-enter", () => {
    it(`When the text typed before the [Enter] key stroke matches the Tuleap reference format
        Then the matching text should have an async_cross_reference Mark`, () => {
        const view = buildView("This document references art #123");

        triggerEvent(view);

        expect(view.dom.innerHTML).toMatchInlineSnapshot(
            `<p>This document references <async-cross-reference>art #123</async-cross-reference></p>`,
        );
    });

    it(`When the text typed before the [Enter] key stroke matches the format of an https url
        Then the matching text should have a link Mark`, () => {
        const view = buildView("See https://example.com");

        triggerEvent(view);

        expect(view.dom.innerHTML).toMatchInlineSnapshot(
            `<p>See <a href="https://example.com">https://example.com</a></p>`,
        );
    });
});
