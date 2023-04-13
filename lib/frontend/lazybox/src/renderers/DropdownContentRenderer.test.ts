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

import { describe, it, expect, beforeEach } from "vitest";
import { html } from "lit/html.js";
import { DropdownContentRenderer, getRenderedListItem } from "./DropdownContentRenderer";
import { BaseComponentRenderer } from "./BaseComponentRenderer";
import { ItemsMapManager } from "../items/ItemsMapManager";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";
import type { GroupCollection } from "../items/GroupCollection";
import { TemplatingCallbackStub } from "../../tests/stubs/TemplatingCallbackStub";
import { OptionsBuilder } from "../../tests/builders/OptionsBuilder";

describe("DropDownContentRenderer", () => {
    let dropdown: Element, dropdown_list: HTMLElement;

    const render = (groups: GroupCollection): void => {
        const items_map_manager = new ItemsMapManager(
            ListItemMapBuilder(TemplatingCallbackStub.build())
        );
        items_map_manager.refreshItemsMap(groups);
        const renderer = new DropdownContentRenderer(dropdown_list, items_map_manager);
        return renderer.renderLazyboxDropdownContent(groups);
    };

    describe("rendering", () => {
        beforeEach(() => {
            const doc = document.implementation.createHTMLDocument();
            const select = doc.createElement("select");
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(
                document.implementation.createHTMLDocument(),
                select,
                OptionsBuilder.withoutNewItem().build()
            ).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;
        });

        it("renders grouped list items", () => {
            render(GroupCollectionBuilder.withTwoGroups());
            expect(stripExpressionComments(dropdown.innerHTML)).toMatchSnapshot();
        });

        it("renders empty option groups when they have a placeholder", () => {
            const empty_state_text = "No results found on the server";
            const groups = GroupCollectionBuilder.withSingleGroup({
                empty_message: empty_state_text,
                items: [],
            });

            render(groups);

            const empty_state = dropdown_list.querySelector("[data-test=lazybox-empty-state]");
            if (!empty_state) {
                throw new Error("The empty state has not been found in the dropdown");
            }
            expect(empty_state.textContent?.trim()).toBe(empty_state_text);
        });

        it(`renders group footer message below the list of items`, () => {
            const footer_message = "Maybe there are more results";
            const groups = GroupCollectionBuilder.withSingleGroup({
                items: [
                    { id: "value-1", value: { id: 1 }, is_disabled: false },
                    { id: "value-2", value: { id: 2 }, is_disabled: false },
                ],
                footer_message,
            });

            render(groups);

            const footer = dropdown_list.querySelector("[data-test=lazybox-group-footer]");
            expect(footer?.textContent?.trim()).toBe(footer_message);
        });

        it("renders a spinner next to the group title when it is loading", () => {
            const empty_state_text = "I am loading, wait a second!";
            const groups = GroupCollectionBuilder.withSingleGroup({
                label: "A group still loading",
                empty_message: empty_state_text,
                items: [],
                is_loading: true,
            });

            render(groups);

            const spinner = dropdown_list.querySelector(
                "[data-test=lazybox-loading-group-spinner]"
            );
            expect(spinner).toBeDefined();
        });
    });

    describe(`getRenderedListItem()`, () => {
        it(`renders a list item from a given template`, () => {
            const item = getRenderedListItem(
                "value_1",
                html`
                    <span>Badge</span>
                    Value 1
                `,
                false
            );

            expect(stripExpressionComments(item.outerHTML)).toMatchInlineSnapshot(`
                <li role="option" aria-selected="false" data-test="lazybox-item" data-item-id="value_1" class="lazybox-dropdown-option-value">

                  <span>Badge</span>
                  Value 1

                </li>
            `);
        });

        it(`renders a disabled list item`, () => {
            const item = getRenderedListItem(
                "value_1",
                html`
                    <span>Badge</span>
                    Value 1
                `,
                true
            );

            expect(item.classList.contains("lazybox-dropdown-option-value-disabled")).toBe(true);
        });
    });
});

/**
 * See https://github.com/lit/lit/blob/lit%402.0.2/packages/lit-html/src/test/test-utils/strip-markers.ts
 */
function stripExpressionComments(html: string): string {
    return html.replace(/<!--\?lit\$[0-9]+\$-->|<!--\??-->/g, "");
}
