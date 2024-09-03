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
import { insertLinkPopover, removeLinkPopover } from "./link-popover-inserter";
import { createLocalDocument, gettext_provider } from "../../../helpers/helper-for-test";

const editor_id = "aaaa-bbbb-cccc-dddd";
const link_href = "https://example.com/";

describe("link-popover-inserter", () => {
    let doc: Document;

    beforeEach(() => {
        doc = createLocalDocument();
    });

    it("insertLinkPopover() should insert a tlp-popover containing a clickable link and return it", () => {
        const popover_element = insertLinkPopover(doc, gettext_provider, editor_id, link_href);

        expect(popover_element).not.toBeNull();
        expect(
            popover_element?.querySelector<HTMLLinkElement>("[data-test=open-link-button]")?.href,
        ).toBe(link_href);
        expect(doc.getElementById(`link-popover-${editor_id}`)).toBe(popover_element);
    });

    it("removeLinkPopover() should remover the tlp-popover from the DOM", () => {
        insertLinkPopover(doc, gettext_provider, editor_id, link_href);
        removeLinkPopover(doc, editor_id);

        expect(doc.getElementById(`link-popover-${editor_id}`)).toBeNull();
    });
});
