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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { EventManager } from "./EventManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { OptionsBuilder } from "../../tests/builders/OptionsBuilder";
import type { DropdownElement } from "../dropdown/DropdownElement";

describe("event manager", () => {
    let doc: Document,
        source_select_box: HTMLSelectElement,
        wrapper_element: HTMLElement,
        lazybox_element: Element,
        dropdown: DropdownElement & HTMLElement;

    const getEventManager = (): EventManager =>
        new EventManager(doc, wrapper_element, lazybox_element, dropdown, source_select_box);

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        source_select_box = document.createElement("select");

        const { wrapper_element: wrapper, lazybox_element: lazybox } = new BaseComponentRenderer(
            doc,
            source_select_box,
            OptionsBuilder.withoutNewItem().build()
        ).renderBaseComponent();

        wrapper_element = wrapper;
        lazybox_element = lazybox;
        dropdown = Object.assign(doc.createElement("span"), {
            open: false,
        }) as DropdownElement & HTMLElement;
    });

    it("When the source <select> is disabled, then it should not attach any event", () => {
        const manager = getEventManager();

        vi.spyOn(doc, "addEventListener");
        vi.spyOn(wrapper_element, "addEventListener");

        source_select_box.setAttribute("disabled", "disabled");

        manager.attachEvents();

        expect(doc.addEventListener).not.toHaveBeenCalled();
        expect(wrapper_element.addEventListener).not.toHaveBeenCalled();
    });

    describe("Dropdown opening", () => {
        let manager: EventManager;

        beforeEach(() => {
            manager = getEventManager();
        });

        it(`Opens the dropdown when I click on the component root
            and stops propagation to avoid triggering "click outside" handler`, () => {
            manager.attachEvents();
            const event = new Event("pointerup");
            const stopPropagation = vi.spyOn(event, "stopPropagation");

            lazybox_element.dispatchEvent(event);

            expect(dropdown.open).toBe(true);
            expect(stopPropagation).toHaveBeenCalled();
        });

        it(`Closes the dropdown when it is open and I click on the component root
            and stops propagation to avoid triggering "click outside" handler`, () => {
            dropdown.open = true;
            manager.attachEvents();
            const event = new Event("pointerup");
            const stopPropagation = vi.spyOn(event, "stopPropagation");

            lazybox_element.dispatchEvent(event);

            expect(dropdown.open).toBe(false);
            expect(stopPropagation).toHaveBeenCalled();
        });

        it(`Does not open the dropdown when I click on the component root
            while the source <select> is disabled`, () => {
            source_select_box.setAttribute("disabled", "disabled");

            manager.attachEvents();
            wrapper_element.dispatchEvent(new Event("pointerup"));

            expect(dropdown.open).toBe(false);
        });
    });

    describe("Dropdown closure", () => {
        let manager: EventManager;

        beforeEach(() => {
            dropdown.open = true;
            manager = getEventManager();
        });

        it.each([
            ["Escape", { key: "Escape" }],
            ["Esc", { key: "Esc" }],
        ])(
            "should close the dropdown when the pressed key is %s",
            (key_name: string, event_init: KeyboardEventInit) => {
                manager.attachEvents();
                doc.dispatchEvent(new KeyboardEvent("keyup", event_init));

                expect(dropdown.open).toBe(false);
            }
        );

        it("should close the dropdown when the user clicks outside the lazybox while it is open", () => {
            manager.attachEvents();
            doc.dispatchEvent(new Event("pointerup"));

            expect(dropdown.open).toBe(false);
        });
    });

    describe("removeEventsListenersOnDocument", () => {
        let manager: EventManager;

        beforeEach(() => {
            manager = getEventManager();
            manager.attachEvents();
        });

        it("should remove the keyup event on document", () => {
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new KeyboardEvent("keyup", { key: "Escape" }));
            expect(dropdown.open).toBe(false);
        });

        it("should remove the pointerup event on document", () => {
            manager.removeEventsListenersOnDocument();
            doc.dispatchEvent(new Event("pointerup"));
            expect(dropdown.open).toBe(false);
        });
    });
});
