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

import {
    appendGroupedOptionsToSourceSelectBox,
    appendSimpleOptionsToSourceSelectBox,
} from "../test-helpers/select-box-options-generator";
import { DropdownContentRenderer } from "./DropdownContentRenderer";
import { BaseComponentRenderer } from "./BaseComponentRenderer";
import { ItemsMapManager } from "../items/ItemsMapManager";
import type { GettextProvider } from "@tuleap/gettext";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";
import { html, render } from "lit/html.js";

describe("DropDownContentRenderer", () => {
    let select: HTMLSelectElement,
        dropdown: Element,
        dropdown_list: Element,
        gettext_provider: GettextProvider,
        items_map_manager: ItemsMapManager;

    function getDropdownContentRenderer(): DropdownContentRenderer {
        return new DropdownContentRenderer(
            select,
            dropdown_list,
            items_map_manager,
            gettext_provider
        );
    }

    beforeEach(() => {
        select = document.createElement("select");
        gettext_provider = {
            gettext: (english: string) => english,
        } as GettextProvider;
        items_map_manager = new ItemsMapManager(new ListItemMapBuilder(select));
    });

    describe("rendering", () => {
        beforeEach(() => {
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(
                document.implementation.createHTMLDocument(),
                select,
                ""
            ).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;
        });

        it("renders grouped list items", async () => {
            appendGroupedOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderLinkSelectorDropdownContent();

            expect(stripExpressionComments(dropdown.innerHTML)).toMatchSnapshot();
        });

        it("renders empty option groups when they have a placeholder", async () => {
            const empty_state_text = "No results found on the server";

            select.appendChild(createOptionGroupWithEmptyState(empty_state_text));

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderLinkSelectorDropdownContent();

            const empty_state = dropdown_list.querySelector(
                "[data-test=link-selector-empty-state]"
            );
            if (!empty_state) {
                throw new Error("The empty state has not been found in the dropdown");
            }
            expect(empty_state.textContent?.trim()).toEqual(empty_state_text);
        });

        it("renders simple list items", async () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderLinkSelectorDropdownContent();

            expect(stripExpressionComments(dropdown.innerHTML)).toMatchSnapshot();
        });

        it("when the source option is disabled, then the list item should be disabled", async () => {
            const disabled_option = document.createElement("option");
            disabled_option.setAttribute("disabled", "disabled");
            disabled_option.setAttribute("value", "You can't select me");

            select.appendChild(disabled_option);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderLinkSelectorDropdownContent();

            const disabled_list_item = dropdown.querySelector(
                ".link-selector-dropdown-option-value-disabled"
            );

            expect(disabled_list_item).not.toBeNull();
        });
    });

    describe("renderAfterDependenciesUpdate", () => {
        beforeEach(() => {
            const { dropdown_list_element, dropdown_element } = new BaseComponentRenderer(
                document.implementation.createHTMLDocument(),
                select,
                ""
            ).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;
        });

        it("should re-render the list", async () => {
            select.appendChild(createOption("item_1", "Item 1"));

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderLinkSelectorDropdownContent();

            const list_item_1 = dropdown_list.querySelector(".link-selector-dropdown-option-value");
            if (!list_item_1) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_1.innerHTML).trim()).toBe("Item 1");

            select.innerHTML = "";
            select.appendChild(createOption("item_2", "Item 2"));
            await items_map_manager.refreshItemsMap();
            renderer.renderAfterDependenciesUpdate();

            const list_item_2 = dropdown_list.querySelector(".link-selector-dropdown-option-value");
            if (!list_item_2) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_2.innerHTML).trim()).toBe("Item 2");
        });

        it("should render an empty state when the source <select> has no options", async () => {
            select.appendChild(createOption("item_1", "Item 1"));

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderLinkSelectorDropdownContent();

            const list_item_1 = dropdown_list.querySelector(".link-selector-dropdown-option-value");
            if (!list_item_1) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_1.innerHTML).trim()).toBe("Item 1");

            select.innerHTML = "";
            await items_map_manager.refreshItemsMap();
            renderer.renderAfterDependenciesUpdate();

            expect(dropdown_list.querySelector("#link-selector-item-item_1")).toBeNull();
            const empty_state = dropdown_list.querySelector(".link-selector-empty-dropdown-state");
            if (!empty_state) {
                throw new Error("Empty state not found");
            }
        });
    });
});

function createOption(value: string, label: string): DocumentFragment {
    const document_fragment = document.createDocumentFragment();
    render(
        html`
            <option value="${value}">${label}</option>
        `,
        document_fragment
    );
    return document_fragment;
}

function createOptionGroupWithEmptyState(empty_state_text: string): DocumentFragment {
    const document_fragment = document.createDocumentFragment();
    render(
        html`
            <optgroup label="Auto completed results">
                <option data-link-selector-role="empty-state" value="" disabled>
                    ${empty_state_text}
                </option>
            </optgroup>
        `,
        document_fragment
    );
    return document_fragment;
}

/**
 * See https://github.com/lit/lit/blob/lit%402.0.2/packages/lit-html/src/test/test-utils/strip-markers.ts
 */
function stripExpressionComments(html: string): string {
    return html.replace(/<!--\?lit\$[0-9]+\$-->|<!--\??-->/g, "");
}
