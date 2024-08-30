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

import { describe, expect, it, vi } from "vitest";
import { buildImagePopover } from "./popover-image";
import * as tlp_popovers from "@tuleap/tlp-popovers";
import type { Popover } from "@tuleap/tlp-popovers";
import type { EditorView } from "prosemirror-view";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";

describe("popover-images", () => {
    it("it builds input for adding image popover", () => {
        vi.spyOn(tlp_popovers, "createPopover").mockReturnValue({} as Popover);
        const editor_view: EditorView = {} as EditorView;
        const doc = createLocalDocument();
        buildImagePopover(
            "popover-id",
            editor_view,
            doc,
            "alt-azerty123",
            "src-azerty123",
            "title-azerty123",
            "submit-azerty123",
            gettext_provider,
        );
        expect(doc.body.innerHTML).toMatchInlineSnapshot(
            `
          <form class="tlp-popover">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
              <h1 id="title-azerty123" class="tlp-popover-title"></h1>
            </div>
            <div class="tlp-popover-body">
              <div class="tlp-form-element"><label for="src-azerty123" class="tlp-label"><i class="fa-solid fa-asterisk" aria-hidden="true"></i></label><input id="src-azerty123" name="input-src" type="url" class="tlp-input" placeholder="https://example.com" required="" pattern="https?://.+"></div>
              <div class="tlp-form-element"><label for="alt-azerty123" class="tlp-label"></label><input id="alt-azerty123" name="input-text" type="input" class="tlp-input" placeholder="undefined"></div>
            </div>
            <div class="tlp-popover-footer"><button type="button" class="tlp-button-primary tlp-button-outline tlp-button-small" data-dismiss="popover"></button> <button type="submit" class="tlp-button-primary tlp-button-small" id="submit-azerty123"></button></div>
          </form>
          <div><i class="fa-solid fa-image ProseMirror-icon" id="trigger-popover-popover-id"></i></div>
        `,
        );
    });
});
