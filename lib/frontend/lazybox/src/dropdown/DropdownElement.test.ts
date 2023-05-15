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
import { DropdownElement, observeOpen } from "./DropdownElement";
import type { LazyboxNewItemCallback } from "../Options";
import type { GroupCollection } from "../GroupCollection";
import { selectOrThrow } from "@tuleap/dom";
import * as tuleap_focus from "@tuleap/focus-navigation";

vi.mock("@tuleap/focus-navigation");

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
            new_item_callback: LazyboxNewItemCallback | undefined,
            new_item_button_label: string;

        beforeEach(() => {
            target = doc.createElement("div") as unknown as ShadowRoot;
            open = true;
            multiple_selection = true;
            new_item_callback = undefined;
            new_item_button_label = "";
        });

        const render = (): void => {
            const search_input = doc.createElement("span");
            search_input.dataset.test = "search-input";
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
            const search = target.querySelector("[data-test=search-input]");
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

        it(`when I press the "arrow down" key while focusing the new item button,
            it will prevent default (it will not scroll down)
            and it will focus the next item in the dropdown`, () => {
            new_item_callback = noop;
            render();

            const button = selectOrThrow(target, "[data-test=new-item-button]");
            const moveFocus = vi.spyOn(tuleap_focus, "moveFocus");
            const key_down_event = new KeyboardEvent("keydown", {
                key: "ArrowDown",
                cancelable: true,
            });

            button.dispatchEvent(key_down_event);
            expect(key_down_event.defaultPrevented).toBe(true);

            button.dispatchEvent(new KeyboardEvent("keyup", { key: "ArrowDown" }));
            expect(moveFocus.mock.calls[0][1]).toBe("down");
        });

        it(`when I press the "arrow up" key while focusing the new item button,
            it will prevent default (it will not scroll down)
            and it will focus the next item in the dropdown`, () => {
            new_item_callback = noop;
            render();

            const button = selectOrThrow(target, "[data-test=new-item-button]");
            const moveFocus = vi.spyOn(tuleap_focus, "moveFocus");
            const key_down_event = new KeyboardEvent("keydown", {
                key: "ArrowUp",
                cancelable: true,
            });

            button.dispatchEvent(key_down_event);
            expect(key_down_event.defaultPrevented).toBe(true);

            button.dispatchEvent(new KeyboardEvent("keyup", { key: "ArrowUp" }));
            expect(moveFocus.mock.calls[0][1]).toBe("up");
        });
    });

    describe(`events`, () => {
        const getHost = (): HostElement => {
            const dropdown = doc.createElement("span");
            return Object.assign(dropdown, {
                open: false,
                content: () => dropdown,
                search_input: doc.createElement("span"),
            }) as HostElement;
        };

        it(`when open is set at first render, it does not dispatch a "close" event
            to avoid grabbing the focus as soon as it is connected`, () => {
            const host = getHost();
            const dispatch = vi.spyOn(host, "dispatchEvent");

            observeOpen(host, false, undefined);

            expect(dispatch).not.toHaveBeenCalled();
        });

        it(`when the dropdown opens, it dispatches an "open" event`, () => {
            const host = getHost();
            const dispatch = vi.spyOn(host, "dispatchEvent");

            observeOpen(host, true, false);

            const event = dispatch.mock.calls[0][0];
            expect(event.type).toBe("open");
        });

        it(`when the dropdown closes, it dispatches a "close" event`, () => {
            const host = getHost();
            const dispatch = vi.spyOn(host, "dispatchEvent");

            observeOpen(host, false, true);

            const event = dispatch.mock.calls[0][0];
            expect(event.type).toBe("close");
        });
    });
});
