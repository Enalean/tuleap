/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, expect, it, beforeEach } from "vitest";
import {
    BASIC_TEXT_ITEMS_GROUP,
    TEXT_STYLES_ITEMS_GROUP,
    LIST_ITEMS_GROUP,
    LINK_ITEMS_GROUP,
    SCRIPTS_ITEMS_GROUP,
} from "../main";
import type { AdditionalElement, ItemGroup } from "../elements/toolbar-element";
import { ADDITIONAL_ITEMS_GROUP } from "../elements/toolbar-element";
import { buildToolbarItems } from "./build-toolbar-items-collection";

describe("buildToolbarItems", () => {
    const element = document.createElement("div");
    let default_item_positon: ItemGroup[] = [];
    beforeEach(() => {
        default_item_positon = [
            { name: BASIC_TEXT_ITEMS_GROUP, element: element },
            { name: TEXT_STYLES_ITEMS_GROUP, element: element },
            { name: LIST_ITEMS_GROUP, element: element },
            { name: LINK_ITEMS_GROUP, element: element },
            { name: SCRIPTS_ITEMS_GROUP, element: element },
        ];
    });

    it("it should return the default item position when there is no additional element", () => {
        expect(buildToolbarItems(default_item_positon, [])).toEqual(default_item_positon);
    });

    it("it should return additional_items at the first position when it is positioned before basic_text_items", () => {
        const additional_item: AdditionalElement[] = [
            { position: "before", target_name: BASIC_TEXT_ITEMS_GROUP, item_element: element },
        ];
        expect(
            buildToolbarItems(default_item_positon, additional_item).map(
                (group_item) => group_item.name,
            ),
        ).toStrictEqual([
            ADDITIONAL_ITEMS_GROUP,
            BASIC_TEXT_ITEMS_GROUP,
            TEXT_STYLES_ITEMS_GROUP,
            LIST_ITEMS_GROUP,
            LINK_ITEMS_GROUP,
            SCRIPTS_ITEMS_GROUP,
        ]);
    });

    it("it should return additional_items at the last position when it is positioned after supersubscript_items", () => {
        const additional_item: AdditionalElement[] = [
            { position: "after", target_name: SCRIPTS_ITEMS_GROUP, item_element: element },
        ];
        expect(
            buildToolbarItems(default_item_positon, additional_item).map(
                (group_item) => group_item.name,
            ),
        ).toStrictEqual([
            BASIC_TEXT_ITEMS_GROUP,
            TEXT_STYLES_ITEMS_GROUP,
            LIST_ITEMS_GROUP,
            LINK_ITEMS_GROUP,
            SCRIPTS_ITEMS_GROUP,
            ADDITIONAL_ITEMS_GROUP,
        ]);
    });

    it("it should return additional_items at the first and the last position when they are positioned before basic_text_items and after supersubscript_items", () => {
        const additional_item: AdditionalElement[] = [
            { position: "before", target_name: BASIC_TEXT_ITEMS_GROUP, item_element: element },
            { position: "after", target_name: SCRIPTS_ITEMS_GROUP, item_element: element },
        ];
        expect(
            buildToolbarItems(default_item_positon, additional_item).map(
                (group_item) => group_item.name,
            ),
        ).toStrictEqual([
            ADDITIONAL_ITEMS_GROUP,
            BASIC_TEXT_ITEMS_GROUP,
            TEXT_STYLES_ITEMS_GROUP,
            LIST_ITEMS_GROUP,
            LINK_ITEMS_GROUP,
            SCRIPTS_ITEMS_GROUP,
            ADDITIONAL_ITEMS_GROUP,
        ]);
    });

    it("it should return additional_items before and after list_items when they are positioned before and after list_items", () => {
        const additional_item: AdditionalElement[] = [
            { position: "before", target_name: LIST_ITEMS_GROUP, item_element: element },
            { position: "after", target_name: LIST_ITEMS_GROUP, item_element: element },
        ];
        expect(
            buildToolbarItems(default_item_positon, additional_item).map(
                (group_item) => group_item.name,
            ),
        ).toStrictEqual([
            BASIC_TEXT_ITEMS_GROUP,
            TEXT_STYLES_ITEMS_GROUP,
            ADDITIONAL_ITEMS_GROUP,
            LIST_ITEMS_GROUP,
            ADDITIONAL_ITEMS_GROUP,
            LINK_ITEMS_GROUP,
            SCRIPTS_ITEMS_GROUP,
        ]);
    });
});
