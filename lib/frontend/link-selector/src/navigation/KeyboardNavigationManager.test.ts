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

import { KeyboardNavigationManager } from "./KeyboardNavigationManager";
import { BaseComponentRenderer } from "../renderers/BaseComponentRenderer";
import { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import { ListItemHighlighter } from "./ListItemHighlighter";
import { ItemsMapManager } from "../items/ItemsMapManager";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";

describe("KeyboardNavigationManager", () => {
    let manager: KeyboardNavigationManager,
        highlighter: ListItemHighlighter,
        dropdown_list: Element,
        item_map_manager: ItemsMapManager;

    function assertOnlyOneItemIsHighlighted(): void {
        expect(dropdown_list.querySelectorAll(".link-selector-item-highlighted")).toHaveLength(1);
    }

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        const source_select_box = doc.createElement("select");

        const { dropdown_list_element } = new BaseComponentRenderer(
            doc,
            source_select_box,
            ""
        ).renderBaseComponent();
        dropdown_list = dropdown_list_element;

        const groups = GroupCollectionBuilder.withTwoGroups();

        item_map_manager = new ItemsMapManager(new ListItemMapBuilder());
        item_map_manager.refreshItemsMap(groups);
        const content_renderer = new DropdownContentRenderer(
            dropdown_list_element,
            item_map_manager
        );
        content_renderer.renderLinkSelectorDropdownContent(groups);

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
                    item_map_manager.findLinkSelectorItemInItemMap(
                        "link-selector-item-group1-value_1"
                    ).element.classList
                ).toContain("link-selector-item-highlighted");
            });

            it("When the user reaches the last valid item, then it should keep it highlighted", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights value_1
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights value_2
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights value_3
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights value_4

                expect(
                    item_map_manager.findLinkSelectorItemInItemMap(
                        "link-selector-item-group2-value_4"
                    ).element.classList
                ).toContain("link-selector-item-highlighted");
            });
        });

        describe("ArrowUp key", () => {
            it("removes the highlight on the next item and highlights the previous one", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 2nd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // highlights 1st

                expect(
                    item_map_manager.findLinkSelectorItemInItemMap(
                        "link-selector-item-group1-value_0"
                    ).element.classList
                ).toContain("link-selector-item-highlighted");
            });

            it("When the user reaches the first item, then it should keep it highlighted", () => {
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowDown" })); // highlights 2nd
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // highlights 1st
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // can't go upper, highlights 1st
                manager.navigate(new KeyboardEvent("keydown", { key: "ArrowUp" })); // same

                expect(
                    item_map_manager.findLinkSelectorItemInItemMap(
                        "link-selector-item-group1-value_0"
                    ).element.classList
                ).toContain("link-selector-item-highlighted");
            });
        });
    });
});
