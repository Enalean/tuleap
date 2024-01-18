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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { Option } from "@tuleap/option";
import { EVENT_TLP_DROPDOWN_HIDDEN, EVENT_TLP_DROPDOWN_SHOWN } from "@tuleap/tlp-dropdown";
import { ControlSelectorsDropdownStub } from "../../tests/ControlSelectorsDropdownStub";
import type { ControlSelectorsDropdown } from "./SelectorsDropdownController";
import type { HostElement, SelectorEntry } from "./SelectorsDropdown";
import {
    DROPDOWN_BUTTON_CLASSNAME,
    DROPDOWN_CONTENT_CLASSNAME,
    renderContent,
} from "./SelectorsDropdownTemplate";
import { SelectorEntryStub } from "../../tests/SelectorEntryStub";

describe("SelectorsDropdownTemplate", () => {
    let host: HostElement,
        active_selector: Option<SelectorEntry>,
        is_dropdown_shown: boolean,
        controller: ControlSelectorsDropdown;

    beforeEach(() => {
        active_selector = Option.nothing();
        is_dropdown_shown = false;
        controller = ControlSelectorsDropdownStub();
        host = {
            button_text: "Add filter",
            selectors_entries: [
                SelectorEntryStub.withEntryName("Author"),
                SelectorEntryStub.withEntryName("Reviewer"),
                SelectorEntryStub.withEntryName("Branch"),
                SelectorEntryStub.withEntryName("Label"),
            ],
            active_selector,
            is_dropdown_shown,
            controller,
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

    describe("side panel", () => {
        it("Should not be rendered when the dropdown is not open", () => {
            const side_panel = renderDropdown().querySelector("[data-test=side-panel]");

            expect(side_panel).toBeNull();
        });

        it("Should not be rendered when the dropdown is open, but no item is selected", () => {
            host.is_dropdown_shown = true;

            const side_panel = renderDropdown().querySelector("[data-test=side-panel]");

            expect(side_panel).toBeNull();
        });

        it("Should be rendered when the dropdown is open AND an item is selected", () => {
            host.is_dropdown_shown = true;
            host.active_selector = Option.fromValue(host.selectors_entries[0]);

            const side_panel = renderDropdown().querySelector("[data-test=side-panel]");

            expect(side_panel).not.toBeNull();
        });
    });

    describe("dropdown events", () => {
        let dropdown_menu: HTMLElement;

        beforeEach(() => {
            dropdown_menu = selectOrThrow(renderDropdown(), "[data-test=dropdown-menu]");
            vi.spyOn(controller, "onDropdownShown");
            vi.spyOn(controller, "onDropdownHidden");
            vi.spyOn(controller, "openSidePanel");
        });

        it("When the dropdown fires a tlp-dropdown-shown event, then the controller onDropdownShown method should be called", () => {
            dropdown_menu.dispatchEvent(new CustomEvent(EVENT_TLP_DROPDOWN_SHOWN));

            expect(controller.onDropdownShown).toHaveBeenCalledOnce();
        });

        it("When the dropdown fires a tlp-dropdown-hidden event, then the controller onDropdownHidden method should be called", () => {
            dropdown_menu.dispatchEvent(new CustomEvent(EVENT_TLP_DROPDOWN_HIDDEN));

            expect(controller.onDropdownHidden).toHaveBeenCalledOnce();
        });

        it("When an item is clicked, then the controller openSidePanel should be called", () => {
            const menu_item = dropdown_menu.querySelector("[data-test=menu-item]");
            if (!(menu_item instanceof HTMLElement)) {
                throw new Error("Unable to find a menu-item to click on.");
            }

            menu_item.click();

            expect(controller.openSidePanel).toHaveBeenCalledOnce();
            expect(controller.openSidePanel).toHaveBeenCalledWith(host, host.selectors_entries[0]);
        });
    });
});
