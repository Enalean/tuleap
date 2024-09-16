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

import { describe, beforeEach, it, expect } from "vitest";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";
import {
    buildLinkPopoverId,
    insertCrossReferenceLinkPopover,
    insertLinkPopover,
    removePopover,
} from "./create-link-popover";

const editor_id = "aaaa-bbbb-cccc-dddd";
const link_href = "https://example.com/";

describe("create-link-popover", () => {
    let doc: Document, popover_anchor: HTMLElement;

    beforeEach(() => {
        doc = createLocalDocument();
        popover_anchor = doc.createElement("span");
    });

    it("insertLinkPopover() should insert a LinkPopoverElement into the document", () => {
        insertLinkPopover(doc, gettext_provider, popover_anchor, editor_id, link_href);

        expect(doc.getElementById(buildLinkPopoverId(editor_id))).not.toBeNull();
    });

    it("insertCrossReferenceLinkPopover() should insert a LinkPopoverElement into the document", () => {
        insertCrossReferenceLinkPopover(
            doc,
            gettext_provider,
            popover_anchor,
            editor_id,
            link_href,
            "art #123",
        );

        expect(doc.getElementById(buildLinkPopoverId(editor_id))).not.toBeNull();
    });

    it("removePopover() should remove the tlp-popover from the DOM", () => {
        insertLinkPopover(doc, gettext_provider, popover_anchor, editor_id, link_href);
        removePopover(doc, editor_id);

        expect(doc.getElementById(buildLinkPopoverId(editor_id))).toBeNull();
    });
});
