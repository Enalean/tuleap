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

import { describe, beforeEach, expect, it } from "vitest";
import {
    appendGroupedOptionsToSourceSelectBox,
    appendSimpleOptionsToSourceSelectBox,
} from "../test-helpers/select-box-options-generator";
import { DropdownContentRenderer } from "./DropdownContentRenderer";
import { BaseComponentRenderer } from "./BaseComponentRenderer";
import { ItemsMapManager } from "../items/ItemsMapManager";
import type { GettextProvider } from "@tuleap/gettext";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";

describe("DropDownContentRenderer", () => {
    let select: HTMLSelectElement,
        dropdown: Element,
        dropdown_list: Element,
        gettext_provider: GettextProvider,
        items_map_manager: ItemsMapManager,
        doc: Document;

    function getDropdownContentRenderer(): DropdownContentRenderer {
        const locale = undefined;
        return new DropdownContentRenderer(
            select,
            dropdown_list,
            items_map_manager,
            gettext_provider,
            locale,
        );
    }

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select = doc.createElement("select");
        gettext_provider = {
            gettext: (english: string) => english,
        } as GettextProvider;
        items_map_manager = new ItemsMapManager(new ListItemMapBuilder(select));
    });

    describe("without search input", () => {
        beforeEach(() => {
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(
                doc,
                select,
            ).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;
        });

        it("renders grouped list items", async () => {
            appendGroupedOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            expect(stripExpressionComments(dropdown.innerHTML)).toMatchSnapshot();
        });

        it("renders simple list items", async () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            expect(stripExpressionComments(dropdown.innerHTML)).toMatchSnapshot();
        });

        it("when the source option is disabled, then the list item should be disabled", async () => {
            const disabled_option = doc.createElement("option");
            disabled_option.setAttribute("disabled", "disabled");
            disabled_option.setAttribute("value", "You can't select me");

            select.appendChild(disabled_option);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            const disabled_list_item = dropdown.querySelector(
                ".list-picker-dropdown-option-value-disabled",
            );

            expect(disabled_list_item).not.toBeNull();
        });
    });

    describe("with search input", () => {
        beforeEach(() => {
            const { dropdown_element, dropdown_list_element } = new BaseComponentRenderer(
                doc,
                select,
                {
                    is_filterable: true,
                },
            ).renderBaseComponent();

            dropdown = dropdown_element;
            dropdown_list = dropdown_list_element;
        });

        describe("accent handling in search", () => {
            it("should match strings with accents regardless of accent differences", async () => {
                // Test accent handling by observing behavior rather than accessing private methods
                const option_accented = doc.createElement("option");
                option_accented.innerText = "résumé";
                option_accented.value = "resume";
                select.appendChild(option_accented);

                const option_francois = doc.createElement("option");
                option_francois.innerText = "François";
                option_francois.value = "francois";
                select.appendChild(option_francois);

                const option_creme = doc.createElement("option");
                option_creme.innerText = "Crème Brûlée";
                option_creme.value = "creme_brulee";
                select.appendChild(option_creme);

                const renderer = getDropdownContentRenderer();
                await items_map_manager.refreshItemsMap();
                renderer.renderListPickerDropdownContent();

                renderer.renderFilteredListPickerDropdownContent("resume");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
                expect(dropdown_list.childElementCount).toBe(1);

                renderer.renderFilteredListPickerDropdownContent("francois");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
                expect(dropdown_list.childElementCount).toBe(1);

                renderer.renderFilteredListPickerDropdownContent("creme brulee");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
                expect(dropdown_list.childElementCount).toBe(1);
            });

            it("should match additional accent combinations", async () => {
                const option_cafe = doc.createElement("option");
                option_cafe.innerText = "café";
                option_cafe.value = "cafe";
                select.appendChild(option_cafe);

                const option_garcon = doc.createElement("option");
                option_garcon.innerText = "garçon";
                option_garcon.value = "garcon";
                select.appendChild(option_garcon);

                const renderer = getDropdownContentRenderer();
                await items_map_manager.refreshItemsMap();
                renderer.renderListPickerDropdownContent();

                renderer.renderFilteredListPickerDropdownContent("cafe");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
                expect(dropdown_list.childElementCount).toBe(1);

                renderer.renderFilteredListPickerDropdownContent("garcon");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
                expect(dropdown_list.childElementCount).toBe(1);
            });

            it("should match partial strings with accents", async () => {
                const option_resume_complet = doc.createElement("option");
                option_resume_complet.innerText = "résumé complet";
                option_resume_complet.value = "resume_complet";
                select.appendChild(option_resume_complet);

                const option_mon_cafe = doc.createElement("option");
                option_mon_cafe.innerText = "Mon café préféré";
                option_mon_cafe.value = "mon_cafe_prefere";
                select.appendChild(option_mon_cafe);

                const renderer = getDropdownContentRenderer();
                await items_map_manager.refreshItemsMap();
                renderer.renderListPickerDropdownContent();

                renderer.renderFilteredListPickerDropdownContent("resume");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
                expect(dropdown_list.childElementCount).toBe(1);

                renderer.renderFilteredListPickerDropdownContent("cafe");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
                expect(dropdown_list.childElementCount).toBe(1);
            });

            it("should not match strings that don't contain the search term", async () => {
                const option = doc.createElement("option");
                option.innerText = "apple";
                option.value = "apple";
                select.appendChild(option);

                const renderer = getDropdownContentRenderer();
                await items_map_manager.refreshItemsMap();
                renderer.renderListPickerDropdownContent();

                renderer.renderFilteredListPickerDropdownContent("orange");
                expect(
                    dropdown_list.querySelector(".list-picker-empty-dropdown-state"),
                ).not.toBeNull();

                renderer.renderFilteredListPickerDropdownContent("applee");
                expect(
                    dropdown_list.querySelector(".list-picker-empty-dropdown-state"),
                ).not.toBeNull();

                renderer.renderFilteredListPickerDropdownContent("");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
            });

            it("should not match partial strings that don't contain the search term", async () => {
                const option_car = doc.createElement("option");
                option_car.innerText = "car";
                option_car.value = "car";
                select.appendChild(option_car);

                const renderer = getDropdownContentRenderer();
                await items_map_manager.refreshItemsMap();
                renderer.renderListPickerDropdownContent();

                // "car" should not match "carpet" since "car" doesn't contain "carpet"
                renderer.renderFilteredListPickerDropdownContent("carpet");
                expect(
                    dropdown_list.querySelector(".list-picker-empty-dropdown-state"),
                ).not.toBeNull();
            });

            it("should handle empty search query correctly", async () => {
                const option = doc.createElement("option");
                option.innerText = "apple";
                option.value = "apple";
                select.appendChild(option);

                const renderer = getDropdownContentRenderer();
                await items_map_manager.refreshItemsMap();
                renderer.renderListPickerDropdownContent();

                // Empty string should show all items (no filtering)
                renderer.renderFilteredListPickerDropdownContent("");
                expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).toBeNull();
                expect(dropdown_list.childElementCount).toBe(1);
            });
        });

        it("renders only items matching the query", async () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("1");

            expect(dropdown_list.childElementCount).toBe(1);

            if (!dropdown_list.firstElementChild) {
                throw new Error("List should not be empty, it should contains the item 'Value 1'");
            }
            expect(dropdown_list.firstElementChild.textContent?.trim()).toBe("Value 1");
        });

        it("renders items with accent when searching without accent", async () => {
            const option_accented = doc.createElement("option");
            option_accented.innerText = "Café crème";
            option_accented.value = "cafe_creme";
            select.appendChild(option_accented);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("cafe");

            expect(dropdown_list.childElementCount).toBe(1);

            if (!dropdown_list.firstElementChild) {
                throw new Error(
                    "List should not be empty, it should contains the item 'Café crème'",
                );
            }
            expect(dropdown_list.firstElementChild.textContent?.trim()).toBe("Café crème");

            renderer.renderFilteredListPickerDropdownContent("café");
            expect(dropdown_list.childElementCount).toBe(1);
            expect(dropdown_list.firstElementChild?.textContent?.trim()).toBe("Café crème");
        });

        it("should not render items when search query doesn't match", async () => {
            const option_regular = doc.createElement("option");
            option_regular.innerText = "Apple";
            option_regular.value = "apple";

            const option_accented = doc.createElement("option");
            option_accented.innerText = "Café crème";
            option_accented.value = "cafe_creme";

            select.appendChild(option_regular);
            select.appendChild(option_accented);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();

            renderer.renderListPickerDropdownContent();

            renderer.renderFilteredListPickerDropdownContent("orange");
            expect(dropdown_list.childElementCount).toBe(1);
            expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).not.toBeNull();

            renderer.renderFilteredListPickerDropdownContent("chocolat");
            expect(dropdown_list.childElementCount).toBe(1);
            expect(dropdown_list.querySelector(".list-picker-empty-dropdown-state")).not.toBeNull();
        });

        it("renders an empty state if no items are matching the query", async () => {
            appendSimpleOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("This query will match no item");

            expect(dropdown_list.querySelector(".list-picker-dropdown-option-value")).toBeNull();

            const empty_state = dropdown_list.querySelector(".list-picker-empty-dropdown-state");
            if (!empty_state) {
                throw new Error("Empty state not found");
            }
        });

        it("renders groups containing matching items", async () => {
            appendGroupedOptionsToSourceSelectBox(select);
            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();

            renderer.renderListPickerDropdownContent();
            renderer.renderFilteredListPickerDropdownContent("Value 1");

            const items = dropdown_list.querySelectorAll(".list-picker-dropdown-option-value");
            const groups = dropdown_list.querySelectorAll(".list-picker-item-group");

            if (items.length === 0 || groups.length === 0) {
                throw new Error("Item or group not found in the filtered list");
            }

            expect(items).toHaveLength(1);
            expect(groups).toHaveLength(1);

            const group = groups[0];
            const item = items[0];

            expect(group.textContent).toContain("Group 1");
            expect(group.contains(item)).toBe(true);
            expect(item.textContent?.trim()).toBe("Value 1");
        });
    });

    describe("renderAfterDependenciesUpdate", () => {
        beforeEach(() => {
            const { dropdown_list_element } = new BaseComponentRenderer(
                doc,
                select,
            ).renderBaseComponent();

            dropdown_list = dropdown_list_element;
        });

        it("should re-render the list", async () => {
            const option_1 = doc.createElement("option");
            option_1.innerText = "Item 1";
            option_1.value = "item_1";
            const option_2 = doc.createElement("option");
            option_2.innerText = "Item 2";
            option_2.value = "item_2";

            select.appendChild(option_1);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            const list_item_1 = dropdown_list.querySelector(".list-picker-dropdown-option-value");
            if (!list_item_1) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_1.innerHTML).trim()).toBe("Item 1");

            select.innerHTML = "";
            select.appendChild(option_2);
            await items_map_manager.refreshItemsMap();
            renderer.renderAfterDependenciesUpdate();

            const list_item_2 = dropdown_list.querySelector(".list-picker-dropdown-option-value");
            if (!list_item_2) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_2.innerHTML).trim()).toBe("Item 2");
        });

        it("should render an empty state when the source <select> has no options", async () => {
            const option_1 = doc.createElement("option");
            option_1.innerText = "Item 1";
            option_1.value = "item_1";
            select.appendChild(option_1);

            const renderer = getDropdownContentRenderer();
            await items_map_manager.refreshItemsMap();
            renderer.renderListPickerDropdownContent();

            const list_item_1 = dropdown_list.querySelector(".list-picker-dropdown-option-value");
            if (!list_item_1) {
                throw new Error("List item not found in the list");
            }
            expect(stripExpressionComments(list_item_1.innerHTML).trim()).toBe("Item 1");

            select.innerHTML = "";
            await items_map_manager.refreshItemsMap();
            renderer.renderAfterDependenciesUpdate();

            expect(dropdown_list.querySelector("#list-picker-item-item_1")).toBeNull();
            const empty_state = dropdown_list.querySelector(".list-picker-empty-dropdown-state");
            if (!empty_state) {
                throw new Error("Empty state not found");
            }
        });
    });
});

/**
 * See https://github.com/lit/lit/blob/lit%402.0.2/packages/lit-html/src/test/test-utils/strip-markers.ts
 */
function stripExpressionComments(html: string): string {
    return html.replace(/<!--\?lit\$[0-9]+\$-->|<!--\??-->/g, "");
}
