/**
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

import type { SpyInstance } from "vitest";
import { describe, beforeEach, afterEach, it, expect, vi } from "vitest";
import { DropdownManager } from "./DropdownManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { OptionsBuilder } from "../../tests/builders/OptionsBuilder";

const noop = (): void => {
    //Do nothing
};

describe("dropdown-manager", () => {
    let doc: Document,
        wrapper: HTMLElement,
        lazybox: Element,
        dropdown: HTMLElement,
        list: Element,
        dropdown_manager: DropdownManager,
        ResizeObserverSpy: SpyInstance,
        disconnect: SpyInstance;

    beforeEach(() => {
        disconnect = vi.fn();
        window.ResizeObserver = ResizeObserverSpy = vi.fn().mockImplementation(() => {
            return {
                observe: vi.fn(),
                disconnect,
            };
        });
        vi.spyOn(window, "requestAnimationFrame").mockImplementation((callback): number => {
            callback(1);
            return 1;
        });

        doc = document.implementation.createHTMLDocument();
        const source_select_box = doc.createElement("select");
        const { wrapper_element, lazybox_element, dropdown_element, dropdown_list_element } =
            new BaseComponentRenderer(
                doc,
                source_select_box,
                OptionsBuilder.withoutNewItem().build()
            ).renderBaseComponent();

        wrapper = wrapper_element;
        lazybox = lazybox_element;
        dropdown = dropdown_element;
        list = dropdown_list_element;

        dropdown_manager = new DropdownManager(
            doc,
            wrapper_element,
            lazybox,
            dropdown,
            list,
            noop,
            noop
        );
    });

    afterEach(() => {
        dropdown_manager.destroy();
    });

    it(`opens the dropdown by appending a "shown" class to the dropdown element,
        and moves it under the lazybox`, () => {
        const onOpen = vi.spyOn(dropdown_manager, "onOpen");
        expect(ResizeObserverSpy).toHaveBeenCalled();
        dropdown_manager.openLazybox();

        expect(lazybox.classList.contains("lazybox-with-open-dropdown")).toBe(true);
        expect(dropdown.classList.contains("lazybox-dropdown-shown")).toBe(true);
        expect(list.getAttribute("aria-expanded")).toBe("true");
        expect(dropdown.style.top.length).toBeGreaterThan(0);
        expect(dropdown.style.left.length).toBeGreaterThan(0);
        expect(dropdown.style.width.length).toBeGreaterThan(0);
        expect(onOpen).toHaveBeenCalled();
    });

    it(`closes the dropdown by removing the "shown" class to the dropdown element`, () => {
        const onClose = vi.spyOn(dropdown_manager, "onClose");
        expect(ResizeObserverSpy).toHaveBeenCalled();
        dropdown_manager.openLazybox();
        dropdown_manager.closeLazybox();

        expect(lazybox.classList.contains("lazybox-with-open-dropdown")).toBe(false);
        expect(dropdown.classList.contains("lazybox-dropdown-shown")).toBe(false);
        expect(list.getAttribute("aria-expanded")).toBe("false");
        expect(onClose).toHaveBeenCalled();
    });

    it("should not open the lazybox if it's already open", () => {
        const onOpen = vi.spyOn(dropdown_manager, "onOpen");
        expect(ResizeObserverSpy).toHaveBeenCalled();
        dropdown.classList.add("lazybox-dropdown-shown");

        vi.spyOn(dropdown.classList, "add");
        dropdown_manager.openLazybox();

        expect(dropdown.classList.add).not.toHaveBeenCalled();
        expect(onOpen).not.toHaveBeenCalled();
    });

    it("should not close the lazybox if it's already closed", () => {
        const onClose = vi.spyOn(dropdown_manager, "onClose");
        expect(ResizeObserverSpy).toHaveBeenCalled();
        vi.spyOn(dropdown.classList, "remove");
        dropdown_manager.closeLazybox();

        expect(dropdown.classList.remove).not.toHaveBeenCalled();
        expect(onClose).not.toHaveBeenCalled();
    });

    it("should stop observing items resize", () => {
        expect(ResizeObserverSpy).toHaveBeenCalled();
        dropdown_manager.destroy();

        expect(disconnect).toHaveBeenCalled();
    });

    describe("dropdown positioning", () => {
        function getDropdownManagerWithSizedElements(
            document_client_height: number
        ): DropdownManager {
            const mocked_doc = {
                documentElement: {
                    clientHeight: document_client_height,
                },
                body: document.createElement("body"),
            } as unknown as Document;

            return new DropdownManager(mocked_doc, wrapper, lazybox, dropdown, list, noop, noop);
        }

        beforeEach(() => {
            vi.spyOn(dropdown, "getBoundingClientRect").mockReturnValue({
                height: 250,
            } as DOMRect);
            vi.spyOn(wrapper, "getBoundingClientRect").mockReturnValue({
                left: 60,
                bottom: 900,
                width: 250,
                height: 40,
            } as DOMRect);
        });

        it("should place the dropdown below the lazybox", () => {
            const dropdown_manager = getDropdownManagerWithSizedElements(1200);

            dropdown_manager.openLazybox();

            expect(lazybox.classList.contains("lazybox-with-dropdown-above")).toBe(false);
            expect(dropdown.classList.contains("lazybox-dropdown-above")).toBe(false);
            expect(dropdown.style.left).toBe("60px");
            expect(dropdown.style.width).toBe("250px");
            expect(dropdown.style.top).toBe("900px"); // Below the wrapper

            dropdown_manager.destroy();
        });

        it("should place the dropdown on top of the lazybox when there is not enough room below it", () => {
            const dropdown_manager = getDropdownManagerWithSizedElements(1000);

            dropdown_manager.openLazybox();

            expect(lazybox.classList.contains("lazybox-with-dropdown-above")).toBe(true);
            expect(dropdown.classList.contains("lazybox-dropdown-above")).toBe(true);
            expect(dropdown.style.left).toBe("60px");
            expect(dropdown.style.width).toBe("250px");
            expect(dropdown.style.top).toBe("610px"); // Above the wrapper

            dropdown_manager.destroy();
        });
    });
});
