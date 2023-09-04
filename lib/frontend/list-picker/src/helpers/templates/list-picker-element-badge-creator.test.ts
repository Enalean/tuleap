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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { describe, beforeEach, it, expect, vi } from "vitest";
import { appendSimpleOptionsToSourceSelectBox } from "../../test-helpers/select-box-options-generator";
import { ItemsMapManager } from "../../items/ItemsMapManager";
import { createItemBadgeTemplate } from "./list-picker-element-badge-creator";
import { ListItemMapBuilder } from "../../items/ListItemMapBuilder";
import { render } from "lit/html.js";

describe("list-picker-element-badge-creator", () => {
    let source_select_box: HTMLSelectElement,
        item_map_manager: ItemsMapManager,
        event_listener: (event: Event) => void,
        doc: Document;

    beforeEach(async () => {
        doc = document.implementation.createHTMLDocument();
        source_select_box = doc.createElement("select");
        source_select_box.setAttribute("multiple", "multiple");
        appendSimpleOptionsToSourceSelectBox(source_select_box);
        item_map_manager = new ItemsMapManager(new ListItemMapBuilder(source_select_box));
        await item_map_manager.refreshItemsMap();
        event_listener = vi.fn();
    });

    describe("listPickerElementBadgeCreator", () => {
        it("should create a simple badge", () => {
            const badge_document_fragment = doc.createDocumentFragment();
            const item_1 = item_map_manager.findListPickerItemInItemMap("list-picker-item-value_1");
            const badge = createItemBadgeTemplate(event_listener, item_1);
            render(badge, badge_document_fragment);
            const badge_document_element = badge_document_fragment.firstElementChild;
            if (!badge_document_element) {
                throw new Error("badge_document_element should not be null");
            }
            expect(badge_document_element.className).toBe(" list-picker-badge ");
        });

        it("should create the custom colored badge if the source option has the color data set", () => {
            const badge_document_fragment = doc.createDocumentFragment();
            const colored_badge = item_map_manager.findListPickerItemInItemMap(
                "list-picker-item-value_colored",
            );
            const badge = createItemBadgeTemplate(event_listener, colored_badge);
            render(badge, badge_document_fragment);
            const badge_document_element = badge_document_fragment.firstElementChild;
            if (!badge_document_element) {
                throw new Error("badge_document_element should not be null");
            }
            expect(badge_document_element.className).toBe(
                "list-picker-badge list-picker-badge-acid-green",
            );
        });

        it("should create a custom badge and the user badge if the source option has the user data set", () => {
            const badge_document_fragment = doc.createDocumentFragment();
            const user_badge = item_map_manager.findListPickerItemInItemMap(
                "list-picker-item-peraltaj",
            );
            const badge = createItemBadgeTemplate(event_listener, user_badge);
            render(badge, badge_document_fragment);
            const badge_document_element = badge_document_fragment.firstElementChild;
            if (!badge_document_element) {
                throw new Error("badge_document_element should not be null");
            }
            expect(badge_document_element.className).toBe(
                " list-picker-badge list-picker-badge-custom ",
            );
        });

        it("should not create the custom colored badge if the source option has the legacy color data set", () => {
            const badge_document_fragment = doc.createDocumentFragment();
            const colored_badge = item_map_manager.findListPickerItemInItemMap(
                "list-picker-item-bad_colored",
            );
            const badge = createItemBadgeTemplate(event_listener, colored_badge);
            render(badge, badge_document_fragment);
            const badge_document_element = badge_document_fragment.firstElementChild;
            if (!badge_document_element) {
                throw new Error("badge_document_element should not be null");
            }
            expect(badge_document_element.className).toBe("list-picker-badge");
        });
    });
});
