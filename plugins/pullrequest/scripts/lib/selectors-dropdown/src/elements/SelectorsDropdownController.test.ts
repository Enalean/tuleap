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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { Option } from "@tuleap/option";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import type { InternalSelectorsDropdown, SelectorEntry } from "./SelectorsDropdown";
import { SelectorsDropdownController } from "./SelectorsDropdownController";
import { AutocompleterStub } from "../../tests/AutocompleterStub";
import { SelectorEntryStub } from "../../tests/SelectorEntryStub";

vi.mock("@tuleap/tlp-dropdown");

describe("SelectorsDropdownController", () => {
    let active_selector: Option<SelectorEntry>;

    beforeEach(() => {
        active_selector = Option.nothing();
    });

    const getHost = (): InternalSelectorsDropdown => {
        const doc = document.implementation.createHTMLDocument();
        return {
            dropdown_button_element: doc.createElement("button"),
            dropdown_content_element: doc.createElement("div"),
            auto_completer_element: doc.createElement("div"),
            is_dropdown_shown: false,
            active_selector,
        } as unknown as InternalSelectorsDropdown;
    };

    it(`initDropdown() should init a tlp-dropdown using the custom-element's button and menu`, () => {
        const host = getHost();
        const createDropdown = vi.spyOn(tlp_dropdown, "createDropdown");

        SelectorsDropdownController(AutocompleterStub()).initDropdown(host);

        expect(createDropdown).toHaveBeenCalledWith(host.dropdown_button_element, {
            dropdown_menu: host.dropdown_content_element,
        });
    });

    it("onDropdownShown() should set is_dropdown_shown to true", () => {
        const host = getHost();

        SelectorsDropdownController(AutocompleterStub()).onDropdownShown(host);

        expect(host.is_dropdown_shown).toBe(true);
    });

    describe("openSidePanel()", () => {
        it("should assign active_selector and start the autocompleter", () => {
            vi.useFakeTimers();

            const host = getHost();
            const selector = SelectorEntryStub.withEntryName("Author");
            const autocompleter = AutocompleterStub();
            const controller = SelectorsDropdownController(autocompleter);

            vi.spyOn(autocompleter, "start");

            controller.onDropdownShown(host);
            controller.openSidePanel(host, selector);

            vi.advanceTimersToNextTimer();

            expect(host.active_selector.unwrapOr(null)).toBe(selector);
            expect(autocompleter.start).toHaveBeenCalledWith(selector, host);
        });

        it("should not restart the autocompleter when the currently active selector have not changed", () => {
            vi.useFakeTimers();

            const selector = SelectorEntryStub.withEntryName("Author");

            active_selector = Option.fromValue(selector);

            const host = getHost();
            const autocompleter = AutocompleterStub();
            const controller = SelectorsDropdownController(autocompleter);

            vi.spyOn(autocompleter, "start");

            controller.onDropdownShown(host);
            controller.openSidePanel(host, selector);

            vi.advanceTimersToNextTimer();

            expect(autocompleter.start).not.toHaveBeenCalled();
        });
    });

    it("onDropdownHidden() should set is_dropdown_shown to false and clear the currently selected selector", () => {
        const host = getHost();
        const selector = SelectorEntryStub.withEntryName("Author");
        const controller = SelectorsDropdownController(AutocompleterStub());

        controller.onDropdownShown(host);
        controller.openSidePanel(host, selector);
        controller.onDropdownHidden(host);

        expect(host.is_dropdown_shown).toBe(false);
        expect(host.active_selector.isValue()).toBe(false);
    });
});
