/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { GLOBAL_SCOPE } from "../type";
import type { ShortcutsGroup } from "../type";

import { addShortcutsGroupToShortcutsModal, createShortcutsGroupContainer } from "./add-to-modal";
import * as getter_shortcuts_group_head from "./create-shortcuts-group-container/create-shortcuts-group-head";
import * as getter_shortcuts_group_table from "./create-shortcuts-group-container/create-shortcuts-group-table";
import * as getter_shortcut_section from "./get-shortcuts-section";

vi.mock("./create-shortcuts-group-container/create-shortcuts-group-head");
vi.mock("./create-shortcuts-group-container/create-shortcuts-group-table");
vi.mock("./get-shortcuts-section");

describe("add-to-help-modal.ts", () => {
    let doc: Document;

    let global_shortcuts_section: HTMLElement;
    let specific_shortcuts_section: HTMLElement;

    let shortcuts_group_head: HTMLElement;
    let shortcuts_template: HTMLElement;
    let shortcuts_group_table: HTMLTableElement;

    const shortcuts_group: ShortcutsGroup = { title: "Shortcuts group title" } as ShortcutsGroup;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();

        shortcuts_template = doc.createElement("div");
        shortcuts_template.setAttribute("data-shortcuts-help-header-template", "");
        doc.body.append(shortcuts_template);

        shortcuts_group_head = doc.createElement("div");
        shortcuts_group_table = doc.createElement("table");

        global_shortcuts_section = doc.createElement("section");
        specific_shortcuts_section = doc.createElement("section");

        vi.spyOn(getter_shortcuts_group_head, "createShortcutsGroupHead").mockReturnValue(
            shortcuts_group_head,
        );
        vi.spyOn(getter_shortcuts_group_table, "createShortcutsGroupTable").mockReturnValue(
            shortcuts_group_table,
        );
        vi.spyOn(getter_shortcut_section, "getGlobalShortcutsSection").mockReturnValue(
            global_shortcuts_section,
        );
        vi.spyOn(getter_shortcut_section, "getSpecificShortcutsSection").mockReturnValue(
            specific_shortcuts_section,
        );
    });

    describe("addShortcutsGroupToShortcutsModal", () => {
        it("adds to the global shortcuts section in the shortcuts modal if GLOBAL_SCOPE is provided", () => {
            const get_global_shortcuts_section = vi.spyOn(
                getter_shortcut_section,
                "getGlobalShortcutsSection",
            );
            addShortcutsGroupToShortcutsModal(doc, shortcuts_group, GLOBAL_SCOPE);

            expect(get_global_shortcuts_section).toHaveBeenCalled();
        });

        it("adds to the specific shortcuts section in the shortcuts modal if no scope is provided", () => {
            const get_specific_shortcuts_section = vi.spyOn(
                getter_shortcut_section,
                "getSpecificShortcutsSection",
            );
            addShortcutsGroupToShortcutsModal(doc, shortcuts_group);

            expect(get_specific_shortcuts_section).toHaveBeenCalled();
        });

        it("never add shortcut when help button is not present", () => {
            shortcuts_template.remove();
            const get_specific_shortcuts_section = vi.spyOn(
                getter_shortcut_section,
                "getSpecificShortcutsSection",
            );
            addShortcutsGroupToShortcutsModal(doc, shortcuts_group);

            expect(get_specific_shortcuts_section).not.toHaveBeenCalled();
        });
    });

    describe("createShortcutsGroupContainer", () => {
        it("appends a group head and table to the shortcuts group in shortcuts section", () => {
            const shortcuts_group_container = createShortcutsGroupContainer(doc, shortcuts_group);

            expect(shortcuts_group_container.firstChild).toBe(shortcuts_group_head);
            expect(shortcuts_group_container.lastChild).toBe(shortcuts_group_table);
        });

        it("appends its title as a data-attribute to the shortcuts group", () => {
            const shortcuts_group_container = createShortcutsGroupContainer(doc, shortcuts_group);

            expect(shortcuts_group_container.dataset.shortcutsGroup).toBe(shortcuts_group.title);
        });
    });
});
