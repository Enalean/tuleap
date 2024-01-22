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
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";
import type { GroupCollection, LazyboxItem } from "../GroupCollection";
import { TemplatingCallbackStub } from "../../tests/stubs/TemplatingCallbackStub";
import { getAllGroupsTemplate, getItemTemplate } from "./GroupTemplate";
import type { HostElement } from "./DropdownElement";
import { LazyboxItemStub } from "../../tests/stubs/LazyboxItemStub";
import { selectOrThrow } from "@tuleap/dom";
import * as tuleap_focus from "@tuleap/focus-navigation";

vi.mock("@tuleap/focus-navigation");

const noopSelectItem = (item: LazyboxItem): void => {
    if (item) {
        //Do nothing
    }
};

describe("GroupTemplate", () => {
    let target: ShadowRoot, groups: GroupCollection;

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;

        groups = [];
    });

    const getHost = (): HostElement =>
        ({
            open: true,
            groups,
            selection: {
                selectItem: noopSelectItem,
                isSelected: (item) => (item ? false : false),
            },
            templating_callback: TemplatingCallbackStub.build(),
        }) as HostElement;

    const render = (): void => {
        const host = getHost();
        const updateFunction = getAllGroupsTemplate(host);
        updateFunction(host, target);
    };

    describe("rendering", () => {
        it("renders grouped list items", () => {
            groups = GroupCollectionBuilder.withTwoGroups();
            render();
            expect(target.innerHTML).toMatchSnapshot();
        });

        it("renders empty option groups when they have a placeholder", () => {
            const empty_state_text = "No results found on the server";
            groups = GroupCollectionBuilder.withSingleGroup({
                empty_message: empty_state_text,
                items: [],
            });

            render();

            const empty_state = selectOrThrow(target, "[data-test=lazybox-empty-state]");
            expect(empty_state.textContent?.trim()).toBe(empty_state_text);
        });

        it(`renders group footer message below the list of items`, () => {
            const footer_message = "Maybe there are more results";
            groups = GroupCollectionBuilder.withSingleGroup({
                items: [
                    LazyboxItemStub.withDefaults({ value: { id: 1 } }),
                    LazyboxItemStub.withDefaults({ value: { id: 2 } }),
                ],
                footer_message,
            });

            render();

            const footer = selectOrThrow(target, "[data-test=lazybox-group-footer]");
            expect(footer.textContent?.trim()).toBe(footer_message);
        });

        it("renders a spinner next to the group title when it is loading", () => {
            const empty_state_text = "I am loading, wait a second!";
            groups = GroupCollectionBuilder.withSingleGroup({
                label: "A group still loading",
                empty_message: empty_state_text,
                items: [],
                is_loading: true,
            });

            render();

            const spinner = target.querySelector("[data-test=lazybox-loading-group-spinner]");
            expect(spinner).toBeDefined();
        });
    });

    describe(`getItemTemplate()`, () => {
        let item: LazyboxItem;

        beforeEach(() => {
            item = LazyboxItemStub.withDefaults({ value: { id: 1 } });
        });

        const render = (host: HostElement): void => {
            const updateFunction = getItemTemplate(host, item);
            updateFunction(host, target);
        };

        it(`renders a list item from a given template`, () => {
            render(getHost());
            expect(target.innerHTML).toMatchInlineSnapshot(`
              <li role="option" tabindex="0" class="lazybox-dropdown-option-value" data-navigation="lazybox-item" aria-selected="false">
                <span>Badge</span>Value 1
              </li>
            `);
        });

        it(`when I click on a list item,
            it will select the item and close the dropdown`, () => {
            const host = getHost();
            const selectItem = vi.spyOn(host.selection, "selectItem");

            render(host);
            const list_item = selectOrThrow(target, "[data-test=lazybox-item]");
            list_item.dispatchEvent(new Event("pointerup"));

            expect(selectItem).toHaveBeenCalled();
            expect(host.open).toBe(false);
        });

        it(`when I press the "enter" key while focusing a list item,
            it will select the item and close the dropdown
            and will stop propagation (it has already been handled)`, () => {
            const host = getHost();
            const selectItem = vi.spyOn(host.selection, "selectItem");

            render(host);
            const list_item = selectOrThrow(target, "[data-test=lazybox-item]");
            const event = new KeyboardEvent("keyup", { key: "Enter" });
            const stopPropagation = vi.spyOn(event, "stopPropagation");
            list_item.dispatchEvent(event);

            expect(selectItem).toHaveBeenCalled();
            expect(host.open).toBe(false);
            expect(stopPropagation).toHaveBeenCalled();
        });

        it(`when I press the "arrow down" key while focusing a list item,
            it will prevent default (it will not scroll down)
            and it will focus the next item in the dropdown`, () => {
            const moveFocus = vi.spyOn(tuleap_focus, "moveFocus");
            const key_down_event = new KeyboardEvent("keydown", {
                key: "ArrowDown",
                cancelable: true,
            });
            render(getHost());

            const list_item = selectOrThrow(target, "[data-test=lazybox-item]");
            list_item.dispatchEvent(key_down_event);
            expect(key_down_event.defaultPrevented).toBe(true);

            list_item.dispatchEvent(new KeyboardEvent("keyup", { key: "ArrowDown" }));
            expect(moveFocus.mock.calls[0][1]).toBe("down");
        });

        it(`when I press the "arrow up" key while focusing a list item,
            it will prevent default (it will not scroll up)
            and it will focus the previous item in the dropdown`, () => {
            const moveFocus = vi.spyOn(tuleap_focus, "moveFocus");
            const key_down_event = new KeyboardEvent("keydown", {
                key: "ArrowUp",
                cancelable: true,
            });
            render(getHost());

            const list_item = selectOrThrow(target, "[data-test=lazybox-item]");
            list_item.dispatchEvent(key_down_event);
            expect(key_down_event.defaultPrevented).toBe(true);

            list_item.dispatchEvent(new KeyboardEvent("keyup", { key: "ArrowUp" }));
            expect(moveFocus.mock.calls[0][1]).toBe("up");
        });

        it(`renders a disabled list item`, () => {
            item = LazyboxItemStub.withDefaults({ is_disabled: true });
            render(getHost());

            const item_template = target.firstElementChild;
            expect(
                item_template?.classList.contains("lazybox-dropdown-option-value-disabled"),
            ).toBe(true);
        });
    });
});
