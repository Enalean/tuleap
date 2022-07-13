/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import type { ShortcutsGroup } from "../type";
import { removeShortcutsGroupFromShortcutsModal } from "./remove-from-modal";

describe("removeShortcutsGroupFromShortcutsModal", () => {
    let doc: Document;
    let shortcuts_section: HTMLElement;
    let shortcuts_group_container: HTMLElement;

    const shortcuts_group: ShortcutsGroup = {
        title: "Shortcuts group title",
    } as ShortcutsGroup;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocument(doc);
    });

    it("removes a shortcuts group from the shortcuts help modal", () => {
        removeShortcutsGroupFromShortcutsModal(doc, shortcuts_group);

        expect(shortcuts_section.innerHTML).toBe("");
    });

    it("does nothing if the shortcuts group does not exist", () => {
        delete shortcuts_group_container.dataset.shortcutsGroup;

        removeShortcutsGroupFromShortcutsModal(doc, shortcuts_group);

        expect(shortcuts_section.childNodes).toHaveLength(1);
        expect(shortcuts_section.firstElementChild).toBe(shortcuts_group_container);
    });

    function setupDocument(doc: Document): void {
        shortcuts_section = doc.createElement("section");
        shortcuts_section.dataset.shortcutsGlobalSection = "";

        shortcuts_group_container = doc.createElement("div");
        shortcuts_group_container.dataset.shortcutsGroup = shortcuts_group.title;

        shortcuts_section.append(shortcuts_group_container);
        doc.body.append(shortcuts_section);
    }
});
