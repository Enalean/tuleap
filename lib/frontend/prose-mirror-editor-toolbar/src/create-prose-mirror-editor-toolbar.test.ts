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
import { createLocalDocument } from "./helpers/helper-for-test";
import { createProseMirrorEditorToolbar } from "./create-prose-mirror-editor-toolbar";
import { TOOLBAR_TAG_NAME } from "./elements/toolbar-element";

describe("create-prose-mirror-editor-toolbar", () => {
    it("should create a ProseMirrorToolbarElement", () => {
        const doc = createLocalDocument();
        const toolbar = createProseMirrorEditorToolbar(doc);

        expect(toolbar.tagName).toBe(TOOLBAR_TAG_NAME.toUpperCase());
        expect(toolbar.controller).toBeUndefined();
        expect(toolbar.text_elements).toBeUndefined();
        expect(toolbar.script_elements).toBeUndefined();
        expect(toolbar.link_elements).toBeUndefined();
        expect(toolbar.list_elements).toBeUndefined();
        expect(toolbar.style_elements).toBeUndefined();
    });
});
