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

import { describe, it, expect, beforeEach } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { HostElement } from "./SelectorsDropdown";
import {
    DROPDOWN_BUTTON_CLASSNAME,
    DROPDOWN_CONTENT_CLASSNAME,
    renderContent,
} from "./SelectorsDropdownTemplate";

describe("SelectorsDropdownTemplate", () => {
    let host: HostElement;

    beforeEach(() => {
        host = {
            button_text: "Add filter",
            selectors_entries: [
                { entry_name: "Author" },
                { entry_name: "Reviewer" },
                { entry_name: "Branch" },
                { entry_name: "Label" },
            ],
        } as unknown as HostElement;
    });

    const renderDropdown = (): ShadowRoot => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        const render = renderContent(host);

        render(host, target);

        return target;
    };

    it("should render the dropdown button", () => {
        const dropdown = renderDropdown();
        const dropdown_button = selectOrThrow(dropdown, "[data-test=dropdown-button]");

        expect(dropdown_button.textContent?.trim()).toBe(host.button_text);
        expect(Array.from(dropdown_button.classList)).toContain(DROPDOWN_BUTTON_CLASSNAME);
    });

    it("should render the dropdown menu", () => {
        const dropdown = renderDropdown();
        const dropdown_menu = selectOrThrow(dropdown, "[data-test=dropdown-menu]");
        const menu_items = dropdown_menu.querySelectorAll("[data-test=menu-item]");

        expect(Array.from(dropdown_menu.classList)).toContain(DROPDOWN_CONTENT_CLASSNAME);
        expect(menu_items).toHaveLength(host.selectors_entries.length);

        menu_items.forEach((item, index) =>
            expect(item.textContent?.trim()).toBe(host.selectors_entries[index].entry_name),
        );
    });
});
