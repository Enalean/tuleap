/*
 * Copyright (c) Enalean, 2021-present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import type { ShortcutsGroup } from "../../type";
import { createShortcutsGroupHead } from "./create-shortcuts-group-head";

describe("createShortcutsGroupHead", () => {
    let doc: Document;

    const shortcuts_group_no_details: ShortcutsGroup = {
        title: "shortcuts_group title",
    } as ShortcutsGroup;

    const shortcuts_group_with_details: ShortcutsGroup = {
        title: "shortcuts_group title",
        details: "shortcuts_group details",
    } as ShortcutsGroup;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("returns the shortcuts group head", () => {
        const shortcuts_group_head = createShortcutsGroupHead(doc, shortcuts_group_no_details);
        expect(shortcuts_group_head.outerHTML).toBe(
            `<div class="help-modal-shortcuts-group-head">` +
                `<h2 class="tlp-modal-subtitle">shortcuts_group title</h2>` +
                `</div>`,
        );
    });

    it("returns the shortcuts group head with details if provided", () => {
        const shortcuts_group_head = createShortcutsGroupHead(doc, shortcuts_group_with_details);
        expect(shortcuts_group_head.outerHTML).toBe(
            `<div class="help-modal-shortcuts-group-head">` +
                `<h2 class="tlp-modal-subtitle">shortcuts_group title</h2>` +
                `<p class="help-modal-shortcuts-group-details">shortcuts_group details</p>` +
                `</div>`,
        );
    });
});
