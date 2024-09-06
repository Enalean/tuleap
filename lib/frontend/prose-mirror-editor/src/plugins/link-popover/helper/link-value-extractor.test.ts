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

import { describe, expect, it } from "vitest";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";
import { EditorState } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { custom_schema } from "../../../custom_schema";
import { setupToolbar } from "../../toolbar";
import { getLinkValue } from "./link-value-extractor";

describe("extract link value from selection", () => {
    let state: EditorState;
    it("extract link value", () => {
        const local_document: Document = createLocalDocument();
        const editor_content = local_document.createElement("div");
        editor_content.innerHTML = "standard text with <a href='https://example.com'>link</a>";
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
            ...setupToolbar(gettext_provider, "1"),
        });

        const value = getLinkValue(state, 20, 22);
        expect(value).toBe("https://example.com");
    });

    it("extract nothing when no link", () => {
        const local_document: Document = createLocalDocument();
        const editor_content = local_document.createElement("div");
        editor_content.innerHTML = "standard text with no link";
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
            ...setupToolbar(gettext_provider, "1"),
        });

        const value = getLinkValue(state, 6, 8);
        expect(value).toBe("");
    });
});
