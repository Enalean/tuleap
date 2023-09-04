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

import { describe, expect, beforeEach, afterEach, it } from "vitest";
import { KeyboardNavigationManager } from "./KeyboardNavigationManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { appendGroupedOptionsToSourceSelectBox } from "../test-helpers/select-box-options-generator";
import { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import type { GettextProvider } from "@tuleap/gettext";
import { ListItemHighlighter } from "./ListItemHighlighter";
import { ItemsMapManager } from "../items/ItemsMapManager";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";

describe("KeyboardNavigationManager", () => {
    let manager: KeyboardNavigationManager,
        highlighter: ListItemHighlighter,
        dropdown_list: Element,
        item_map_manager: ItemsMapManager;

    function assertOnlyOneItemIsHighlighted(): void {
        expect(dropdown_list.querySelectorAll(".list-picker-item-highlighted")).toHaveLength(1);
    }

    beforeEach(async () => {
        const doc = document.implementation.createHTMLDocument();
        const source_select_box = doc.createElement("select");
        appendGroupedOptionsToSourceSelectBox(source_select_box);

        const { dropdown_list_element } = new BaseComponentRenderer(
            doc,
            source_select_box,
        ).renderBaseComponent();

        item_map_manager = new ItemsMapManager(new ListItemMapBuilder(source_select_box));
        await item_map_manager.refreshItemsMap();
        const content_renderer = new DropdownContentRenderer(
            source_select_box,
            dropdown_list_element,
            item_map_manager,
            {
                gettext: (english: string) => english,
            } as GettextProvider,
        );

        dropdown_list = dropdown_list_element;

        content_renderer.renderListPickerDropdownContent();
        highlighter = new ListItemHighlighter(dropdown_list_element);
        manager = new KeyboardNavigationManager(dropdown_list_element, highlighter);

        highlighter.resetHighlight();
    });

    describe("arrows up/down", () => {
        afterEach(() => {
            assertOnlyOneItemIsHighlighted();
        });

        describe("ArrowDown key", () => {
            it("removes the highlight on the previous item and highlights the next one", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" }));

                expect(
                    item_map_manager
                        .findListPickerItemInItemMap("list-picker-item-group1-value_1")
                        .element.classList.contains("list-picker-item-highlighted"),
                ).toBe(true);
            });

            it("When the user reaches the last valid item, then it should keep it highlighted", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 2nd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 3rd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 4th
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // won't highlight 5th since it is disabled

                expect(
                    item_map_manager
                        .findListPickerItemInItemMap("list-picker-item-group2-value_4")
                        .element.classList.contains("list-picker-item-highlighted"),
                ).toBe(true);
            });
        });

        describe("ArrowUp key", () => {
            it("removes the highlight on the next item and highlights the previous one", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 2nd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // highlights 1st

                expect(
                    item_map_manager
                        .findListPickerItemInItemMap("list-picker-item-group1-value_0")
                        .element.classList.contains("list-picker-item-highlighted"),
                ).toBe(true);
            });

            it("When the user reaches the first item, then it should keep it highlighted", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 2nd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // highlights 1st
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // can't go upper, highlights 1st
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // same

                expect(
                    item_map_manager
                        .findListPickerItemInItemMap("list-picker-item-group1-value_0")
                        .element.classList.contains("list-picker-item-highlighted"),
                ).toBe(true);
            });
        });
    });
});
