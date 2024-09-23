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
 *
 */

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { TextField } from "./popover-link";
import { createAndInsertField } from "../popover/fields-adder";
import { updateInputValues } from "./input-value-updater";
import { DOMParser, type Node } from "prosemirror-model";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";
import { EditorState } from "prosemirror-state";
import { custom_schema } from "../../../custom_schema";
import * as info_retriever from "../helper/NodeInfoRetriever";

describe("update input values", () => {
    let form: HTMLFormElement;
    let state: EditorState;
    const link_href = "href-azerty123";
    const link_title = "title-azerty123";
    const popover_title_id = "popover-title-azerty123";
    const popover_submit_id = "submit-azerty123";
    const local_document: Document = createLocalDocument();
    beforeEach(() => {
        form = local_document.createElement("form");

        const popover_title = local_document.createElement("div");
        popover_title.id = popover_title_id;
        const popover_submit = local_document.createElement("button");
        popover_submit.id = popover_submit_id;

        form.append(popover_title, popover_submit);

        const href_field: TextField = {
            placeholder: "https://example.com",
            label: "Label",
            required: true,
            type: "url",
            focus: true,
            id: link_href,
            name: link_href,
            value: "",
        };
        const title_field: TextField = {
            placeholder: "Text",
            label: "Text",
            type: "input",
            required: false,
            focus: false,
            id: link_title,
            name: link_title,
            value: "",
        };

        createAndInsertField([href_field, title_field], form, local_document);
        local_document.body.append(form);
    });

    it("it add selected text to non existing link", () => {
        const editor_content = local_document.createElement("div");
        editor_content.innerHTML = "<p>standard text</p>";
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
        });
        vi.spyOn(info_retriever, "getWrappingNodeInfo").mockReturnValue({
            from: 0,
            to: 8,
            corresponding_node: {
                textContent: "standard",
            } as Node,
            is_creating_node: false,
        });
        updateInputValues(
            local_document,
            link_title,
            link_href,
            popover_title_id,
            popover_submit_id,
            gettext_provider,
            state,
        );

        assertElementHasValue(link_title, "standard");
        assertElementHasValue(link_href, "");
    });

    it("it does not fill anything when user does not have a selection", () => {
        updateInputValues(
            local_document,
            link_title,
            link_href,
            popover_title_id,
            popover_submit_id,
            gettext_provider,
            state,
        );
        assertElementHasValue(link_title, "");
        assertElementHasValue(link_href, "");
    });

    it("replace link with selection, when user clicks on a link node", () => {
        const editor_content = local_document.createElement("div");
        editor_content.innerHTML = "standard text with <a href='https://example.com'>link</a>";
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
        });
        vi.spyOn(info_retriever, "getWrappingNodeInfo").mockReturnValue({
            from: 20,
            to: 24,
            corresponding_node: state.doc.cut(20, 24),
            is_creating_node: false,
        });

        updateInputValues(
            local_document,
            link_title,
            link_href,
            popover_title_id,
            popover_submit_id,
            gettext_provider,
            state,
        );
        assertElementHasValue(link_title, "link");
        assertElementHasValue(link_href, "https://example.com");
    });

    function assertElementHasValue(element_id: string, value: string): void {
        const element = local_document.getElementById(element_id);
        if (!(element instanceof HTMLInputElement)) {
            throw new Error(`element ${element_id} is not an input`);
        }
        expect(element.value).toBe(value);
    }
});
