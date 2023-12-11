/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import type { HostElement } from "./LazyboxElement";
import {
    buildClearSelection,
    buildReplaceDropdown,
    buildReplaceSelection,
    connect,
    getDropdownElement,
    getSearchInput,
    getSelectionElement,
} from "./LazyboxElement";
import type { GroupCollection } from "./GroupCollection";
import { GroupCollectionBuilder } from "../tests/builders/GroupCollectionBuilder";
import { LazyboxItemStub } from "../tests/stubs/LazyboxItemStub";
import type { ScrollingManager } from "./events/ScrollingManager";
import * as floating_ui from "@floating-ui/dom";
import { OptionsBuilder } from "../tests/builders/OptionsBuilder";
import type { LazyboxOptions } from "./Options";
import type { SearchInput } from "./SearchInput";
import type { DropdownElement } from "./dropdown/DropdownElement";

vi.mock("@floating-ui/dom", () => {
    return {
        autoUpdate: vi.fn(),
        computePosition: vi.fn(),
    };
});

const noop = (): void => {
    //Do nothing
};

describe(`LazyboxElement`, () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe(`methods`, () => {
        const getHost = (): HostElement => {
            const groups: GroupCollection = [];
            return {
                dropdown_element: { groups },
                selection_element: {
                    clearSelection: noop,
                    replaceSelection: (items) => {
                        if (items) {
                            //Do nothing
                        }
                    },
                },
            } as HostElement;
        };

        it(`replaceDropdownContent() replaces the dropdown content with the new one`, () => {
            const host = getHost();
            buildReplaceDropdown(host)(GroupCollectionBuilder.withSingleGroup({}));
            expect(host.dropdown_element.groups).toHaveLength(1);
        });

        it(`clearSelection() empties the selection`, () => {
            const host = getHost();
            const clear = vi.spyOn(host.selection_element, "clearSelection");
            buildClearSelection(host)();
            expect(clear).toHaveBeenCalled();
        });

        it(`replaceSelection() replaces the selection with the new one`, () => {
            const host = getHost();
            const replace = vi.spyOn(host.selection_element, "replaceSelection");
            const items = [LazyboxItemStub.withDefaults()];
            buildReplaceSelection(host)(items);
            expect(replace).toHaveBeenCalledWith(items);
        });
    });

    describe(`events`, () => {
        let dropdown_open: boolean;
        beforeEach(() => {
            dropdown_open = true;
        });

        const getHost = (): HostElement => {
            const dropdown_element = Object.assign(
                doc.createElement("div") as HTMLElement,
                { open: dropdown_open } as DropdownElement,
            );
            return Object.assign(doc.createElement("div"), {
                dropdown_element,
                scrolling_manager: undefined,
            } as HostElement);
        };

        it(`when I press the "Escape" key on the document,
            it will close the dropdown and stop propagation (to prevent closing modals at the same time)`, () => {
            const host = getHost();
            connect(host);

            const event = new KeyboardEvent("keyup", { key: "Escape", cancelable: true });
            const stopPropagation = vi.spyOn(event, "stopPropagation");
            doc.dispatchEvent(event);

            expect(stopPropagation).toHaveBeenCalled();
            expect(host.dropdown_element.open).toBe(false);
        });

        it(`when I click on the document outside of lazybox,
            it will close the dropdown`, () => {
            const host = getHost();
            connect(host);

            doc.dispatchEvent(new Event("pointerup"));

            expect(host.dropdown_element.open).toBe(false);
        });

        it(`when I press the "enter" key while focusing the lazybox element,
            it will open the dropdown`, () => {
            dropdown_open = false;
            const host = getHost();
            connect(host);

            host.dispatchEvent(new KeyboardEvent("keyup", { key: "Enter" }));

            expect(host.dropdown_element.open).toBe(true);
        });

        it(`when I click on the lazybox element, it toggles the dropdown`, () => {
            dropdown_open = false;
            const host = getHost();
            doc.body.append(host);
            connect(host);

            const event = new Event("pointerup", { bubbles: true });
            host.dispatchEvent(event);

            expect(host.dropdown_element.open).toBe(true);

            host.dispatchEvent(new Event("pointerup"));

            expect(host.dropdown_element.open).toBe(false);
        });

        it(`when I click in the dropdown, it does not close it`, () => {
            const host = getHost();
            host.append(host.dropdown_element);
            connect(host);

            host.dropdown_element.dispatchEvent(new Event("pointerup", { bubbles: true }));

            expect(host.dropdown_element.open).toBe(true);
        });

        it(`on disconnect, it will remove event listeners on document and will unlock scrolling`, () => {
            const host = getHost();
            const disconnect = connect(host);
            if (host.scrolling_manager === undefined) {
                throw Error("Expected scrolling manager to be assigned");
            }
            const unlock = vi.spyOn(host.scrolling_manager, "unlockScrolling");

            disconnect();
            doc.dispatchEvent(new KeyboardEvent("keyup", { key: "Escape" }));
            doc.dispatchEvent(new Event("pointerup"));

            expect(host.dropdown_element.open).toBe(true);
            expect(unlock).toHaveBeenCalled();
        });

        it(`on disconnect, it will remove event listeners on the lazybox element`, () => {
            dropdown_open = false;
            const host = getHost();
            const disconnect = connect(host);

            disconnect();
            host.dispatchEvent(new KeyboardEvent("keyup", { key: "Enter" }));
            expect(host.dropdown_element.open).toBe(false);
            host.dispatchEvent(new Event("pointerup"));
            expect(host.dropdown_element.open).toBe(false);
        });
    });

    describe(`Search Input`, () => {
        let options: LazyboxOptions;
        beforeEach(() => {
            options = OptionsBuilder.withSingle().build();
        });

        const getHost = (): HostElement =>
            Object.assign(doc.createElement("span"), {
                options,
                dropdown_element: { open: false },
            }) as HostElement;

        it(`makes the element focusable`, () => {
            const search_input = getSearchInput(getHost());
            expect(search_input.getAttribute("tabindex")).toBe("0");
        });

        it(`when it receives "search-entered" event, it will open the dropdown`, () => {
            const host = getHost();
            const search_input = getSearchInput(host);

            search_input.dispatchEvent(new CustomEvent("search-entered"));

            expect(host.dropdown_element.open).toBe(true);
        });

        it(`when it receives "search-input" event,
            it will call the search_input_callback with the search input's text`, () => {
            const search_input_callback = vi.spyOn(options, "search_input_callback");
            const host = getHost();
            const search_input = getSearchInput(host);
            const query = "stepfatherly";
            search_input.getQuery = (): string => query;

            search_input.dispatchEvent(new CustomEvent("search-input"));

            expect(search_input_callback).toHaveBeenCalledWith(query);
        });

        it(`assigns the placeholder from options when multiple selection is allowed`, () => {
            const PLACEHOLDER = "I hold the place";
            options = OptionsBuilder.withMultiple().withPlaceholder(PLACEHOLDER).build();
            const search_input = getSearchInput(getHost());

            expect(search_input.placeholder).toBe(PLACEHOLDER);
        });

        it(`assigns the search input placeholder from options when multiple selection is disabled`, () => {
            const search_input_placeholder = "Enter an id";
            options = OptionsBuilder.withSingle()
                .withSearchInputPlaceholder(search_input_placeholder)
                .build();
            const search_input = getSearchInput(getHost());

            expect(search_input.placeholder).toBe(search_input_placeholder);
        });
    });

    describe(`Selection Element`, () => {
        const getHost = (): HostElement =>
            Object.assign(doc.createElement("span"), {
                options: OptionsBuilder.withMultiple().build(),
                search_input_element: { clear: noop },
                dropdown_element: { open: false },
            }) as HostElement;

        it(`when it receives "clear-selection" event, it will clear the search input`, () => {
            const host = getHost();
            const clear = vi.spyOn(host.search_input_element, "clear");
            const selection = getSelectionElement(host);

            selection.dispatchEvent(new CustomEvent("clear-selection"));

            expect(clear).toHaveBeenCalled();
        });

        it(`when it receives "open-dropdown" event, it will open the dropdown`, () => {
            const host = getHost();
            const selection = getSelectionElement(host);

            selection.dispatchEvent(new CustomEvent("open-dropdown"));

            expect(host.dropdown_element.open).toBe(true);
        });
    });

    describe(`Dropdown Element`, () => {
        let options: LazyboxOptions;

        beforeEach(() => {
            options = OptionsBuilder.withSingle()
                .withNewItemButton(noop, () => "New item")
                .build();
        });

        const getHost = (): HostElement => {
            const search_input = {
                clear: noop,
                getQuery: () => "",
            } as SearchInput;
            const search_input_element = Object.assign(doc.createElement("span"), search_input);

            const host = {
                options,
                selection_element: doc.createElement("span"),
                search_input_element,
                cleanupAutoUpdate: noop,
            } as HostElement;
            return Object.assign(doc.createElement("span"), host);
        };

        it(`while the dropdown is open, scrolling is locked,
            the selection element has an additional CSS class,
            the position of the dropdown is updated,
            and the search input is focused.
            When it is closed, the search input is cleared`, () => {
            const cleanup = vi.fn();
            const autoUpdate = vi.spyOn(floating_ui, "autoUpdate").mockReturnValue(cleanup);
            const host = getHost();
            host.scrolling_manager = {
                lockScrolling: noop,
                unlockScrolling: noop,
            } as ScrollingManager;
            const lock = vi.spyOn(host.scrolling_manager, "lockScrolling");
            const unlock = vi.spyOn(host.scrolling_manager, "unlockScrolling");
            const clearSearch = vi.spyOn(host.search_input_element, "clear");
            const focusSearch = vi.spyOn(host.search_input_element, "focus");
            const focusHost = vi.spyOn(host, "focus");
            const dropdown = getDropdownElement(host);

            dropdown.dispatchEvent(new CustomEvent("open"));

            expect(lock).toHaveBeenCalled();
            expect(host.selection_element.classList.contains("lazybox-with-open-dropdown")).toBe(
                true,
            );
            expect(autoUpdate).toHaveBeenCalled();
            expect(focusSearch).toHaveBeenCalled();

            dropdown.dispatchEvent(new CustomEvent("close"));

            expect(unlock).toHaveBeenCalledOnce();
            expect(host.selection_element.classList.contains("lazybox-with-open-dropdown")).toBe(
                false,
            );
            expect(cleanup).toHaveBeenCalledOnce();
            expect(clearSearch).toHaveBeenCalledOnce();
            expect(focusHost).toHaveBeenCalledOnce();
        });

        it(`when it receives "click-create-item" event,
            it will call the new_item_clicked_callback with the search input's text
            and it will clear the search input`, () => {
            const query = "chromazurine";
            const callback = vi.fn();
            options = OptionsBuilder.withSingle()
                .withNewItemButton(callback, () => "New item")
                .build();
            const host = getHost();
            const clearSearch = vi.spyOn(host.search_input_element, "clear");
            vi.spyOn(host.search_input_element, "getQuery").mockReturnValue(query);
            const dropdown = getDropdownElement(host);

            const event = new CustomEvent("click-create-item");
            dropdown.dispatchEvent(event);

            expect(callback).toHaveBeenCalledWith(query);
            expect(clearSearch).toHaveBeenCalled();
        });

        it(`when the search input dispatches a "search-input" event,
            it will call the new_item_label_callback with the search input's text
            to allow having a "reactive" label on the Create new item button`, () => {
            const query = "electrokinetics";
            const callback = vi.fn();
            options = OptionsBuilder.withSingle().withNewItemButton(noop, callback).build();
            const host = getHost();
            vi.spyOn(host.search_input_element, "getQuery").mockReturnValue(query);
            getDropdownElement(host);

            const event = new CustomEvent("search-input");
            host.search_input_element.dispatchEvent(event);

            expect(callback).toHaveBeenCalledWith(query);
        });
    });
});
