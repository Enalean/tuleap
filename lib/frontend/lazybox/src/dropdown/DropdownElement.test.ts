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

import { describe, it, beforeEach, vi, expect } from "vitest";
import type { HostElement } from "./DropdownElement";
import { DropdownElement, observeOpen, selectionSetter } from "./DropdownElement";
import type { SelectionElement } from "../selection/SelectionElement";
import type { LazyboxNewItemCallback } from "../type";
import type { GroupCollection } from "../items/GroupCollection";
import { selectOrThrow } from "@tuleap/dom";

const noop = (): void => {
    //Do nothing
};

describe(`DropdownElement`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe(`rendering`, () => {
        let target: ShadowRoot,
            open: boolean,
            multiple_selection: boolean,
            search_input: HTMLElement,
            new_item_callback: LazyboxNewItemCallback | undefined,
            new_item_button_label: string;

        beforeEach(() => {
            target = doc.createElement("div") as unknown as ShadowRoot;
            open = true;
            multiple_selection = true;
            search_input = doc.createElement("span");
            new_item_callback = undefined;
            new_item_button_label = "";
        });

        const render = (): void => {
            const groups: GroupCollection = [];
            const host: HostElement = {
                open,
                groups,
                multiple_selection,
                search_input,
                new_item_callback,
                new_item_button_label,
            } as HostElement;
            const updateFunction = DropdownElement.content(host);
            updateFunction(host, target);
        };

        it(`renders nothing when dropdown is closed
            to avoid weird bugs where things stop reacting`, () => {
            open = false;
            render();
            expect(target.children).toHaveLength(0);
        });

        it(`renders a search section in the dropdown when multiple selection is disabled`, () => {
            multiple_selection = false;
            render();
            const search = target.querySelector("[data-test=single-search-section]");
            expect(search).not.toBeNull();
        });

        it(`renders a new item button in the dropdown when its callback is defined`, () => {
            new_item_button_label = "Create a new item";
            new_item_callback = vi.fn();
            render();

            const button = selectOrThrow(target, "[data-test=new-item-button]");
            expect(button.textContent?.trim()).toBe(new_item_button_label);
            button.click();

            expect(new_item_callback).toHaveBeenCalled();
        });
    });

    describe(`events`, () => {
        const getHost = (): HostElement => {
            const dropdown = doc.createElement("span");
            return Object.assign(dropdown, {
                open: false,
                content: () => dropdown,
                search_input: { setFocus: noop },
                selection: { setFocus: noop },
            }) as HostElement;
        };

        it(`when open is set at first render, it does not focus the selection
            to avoid grabbing the focus as soon as it is connected`, () => {
            const host = getHost();
            const focusSelection = vi.spyOn(host.selection, "setFocus");
            const dispatch = vi.spyOn(host, "dispatchEvent");

            observeOpen(host, false, undefined);

            expect(focusSelection).not.toHaveBeenCalled();
            expect(dispatch).not.toHaveBeenCalled();
        });

        it(`when the dropdown opens, it dispatches an "open" event
            and focuses the search input`, () => {
            const host = getHost();
            const focusSearch = vi.spyOn(host.search_input, "setFocus");
            const dispatch = vi.spyOn(host, "dispatchEvent");

            observeOpen(host, true, false);

            expect(focusSearch).toHaveBeenCalled();
            const event = dispatch.mock.calls[0][0];
            expect(event.type).toBe("open");
        });

        it(`when the dropdown closes, it dispatches a "close" event
            and focuses the selection element`, () => {
            const host = getHost();
            const focusSelection = vi.spyOn(host.selection, "setFocus");
            const dispatch = vi.spyOn(host, "dispatchEvent");

            observeOpen(host, false, true);

            expect(focusSelection).toHaveBeenCalled();
            const event = dispatch.mock.calls[0][0];
            expect(event.type).toBe("close");
        });

        it(`when it receives "open-dropdown" from the selection, it will open the dropdown`, () => {
            const host = getHost();
            const selection = doc.createElement("span") as SelectionElement & HTMLElement;

            selectionSetter(host, selection);
            selection.dispatchEvent(new CustomEvent("open-dropdown"));

            expect(host.open).toBe(true);
        });
    });
});
