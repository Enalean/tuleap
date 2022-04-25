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

import { html } from "lit/html.js";
import { DropdownContentRenderer, getRenderedListItem } from "./DropdownContentRenderer";
import { BaseComponentRenderer } from "./BaseComponentRenderer";
import { ItemsMapManager } from "../items/ItemsMapManager";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";
import type { GroupCollection } from "../items/GroupCollection";
import { TemplatingCallbackStub } from "../../tests/stubs/TemplatingCallbackStub";

describe("DropDownContentRenderer", () => {
    let dropdown: Element, dropdown_list: HTMLElement;

    const render = (groups: GroupCollection): void => {
        const items_map_manager = new ItemsMapManager(
            ListItemMapBuilder(TemplatingCallbackStub.build())
        );
        items_map_manager.refreshItemsMap(groups);
        const renderer = new DropdownContentRenderer(dropdown_list, items_map_manager);
        return renderer.renderLinkSelectorDropdownContent(groups);
    };

    describe("rendering", () => {
        beforeEach(() => {
            const doc = document.implementation.createHTMLDocument();
            const select = doc.createElement("select");
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(
                document.implementation.createHTMLDocument(),
                select,
                ""
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
            const groups: GroupCollection = [
                {
                    label: "Group 1",
                    empty_message: empty_state_text,
                    items: [],
                },
            ];

            render(groups);

            const empty_state = dropdown_list.querySelector(
                "[data-test=link-selector-empty-state]"
            );
            if (!empty_state) {
                throw new Error("The empty state has not been found in the dropdown");
            }
            expect(empty_state.textContent?.trim()).toBe(empty_state_text);
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
                <li role="option" aria-selected="false" data-test="link-selector-item" data-item-id="value_1" class="link-selector-dropdown-option-value">

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

            expect(item.classList).toContain("link-selector-dropdown-option-value-disabled");
        });
    });
});

/**
 * See https://github.com/lit/lit/blob/lit%402.0.2/packages/lit-html/src/test/test-utils/strip-markers.ts
 */
function stripExpressionComments(html: string): string {
    return html.replace(/<!--\?lit\$[0-9]+\$-->|<!--\??-->/g, "");
}
