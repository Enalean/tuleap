/**
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

import { ListItemMapBuilder } from "./ListItemMapBuilder";
import type { HTMLTemplateResult } from "lit/html.js";
import { html } from "lit/html.js";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";

describe("ListItemBuilder", () => {
    let builder: ListItemMapBuilder;

    beforeEach(() => {
        builder = new ListItemMapBuilder();
    });

    it(`flattens a single group and builds a RenderedItem for each item
        and returns a map containing all items`, () => {
        const map = builder.buildLinkSelectorItemsMap(GroupCollectionBuilder.withSingleGroup());

        expect(map.size).toBe(5);

        const [first_entry, second_entry, third_entry, fourth_entry, fifth_entry] = Array.from(
            map.entries()
        );
        expect(first_entry[0]).toBe("link-selector-item-100");
        expect(first_entry[1]).toStrictEqual({
            id: "link-selector-item-100",
            template: buildTemplateResult("None"),
            value: "100",
            is_disabled: false,
            group_id: "",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(second_entry[0]).toBe("link-selector-item-value_0");
        expect(second_entry[1]).toStrictEqual({
            id: "link-selector-item-value_0",
            template: buildTemplateResult("Value 0"),
            value: "value_0",
            is_disabled: false,
            group_id: "",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(third_entry[0]).toBe("link-selector-item-value_1");
        expect(third_entry[1]).toStrictEqual({
            id: "link-selector-item-value_1",
            template: buildTemplateResult("Value 1"),
            value: "value_1",
            is_disabled: false,
            group_id: "",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(fourth_entry[0]).toBe("link-selector-item-value_2");
        expect(fourth_entry[1]).toStrictEqual({
            id: "link-selector-item-value_2",
            template: buildTemplateResult("Value 2"),
            value: "value_2",
            is_disabled: false,
            group_id: "",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(fifth_entry[0]).toBe("link-selector-item-value_3");
        expect(fifth_entry[1]).toStrictEqual({
            id: "link-selector-item-value_3",
            template: buildTemplateResult("Value 3"),
            value: "value_3",
            is_disabled: false,
            group_id: "",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
    });

    it(`flattens the given groups and builds a RenderedItem for each item of each group
        and returns a map containing all items`, () => {
        const map = builder.buildLinkSelectorItemsMap(GroupCollectionBuilder.withTwoGroups());

        expect(map.size).toBe(6);

        const [first_entry, second_entry, third_entry, fourth_entry, fifth_entry, sixth_entry] =
            Array.from(map.entries());

        expect(first_entry[0]).toBe("link-selector-item-group1-value_0");
        expect(first_entry[1]).toStrictEqual({
            id: "link-selector-item-group1-value_0",
            template: buildTemplateResult("Value 0"),
            value: "value_0",
            is_disabled: false,
            group_id: "group1",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(second_entry[0]).toBe("link-selector-item-group1-value_1");
        expect(second_entry[1]).toStrictEqual({
            id: "link-selector-item-group1-value_1",
            template: buildTemplateResult("Value 1"),
            value: "value_1",
            is_disabled: false,
            group_id: "group1",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(third_entry[0]).toBe("link-selector-item-group1-value_2");
        expect(third_entry[1]).toStrictEqual({
            id: "link-selector-item-group1-value_2",
            template: buildTemplateResult("Value 2"),
            value: "value_2",
            is_disabled: false,
            group_id: "group1",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(fourth_entry[0]).toBe("link-selector-item-group2-value_3");
        expect(fourth_entry[1]).toStrictEqual({
            id: "link-selector-item-group2-value_3",
            template: buildTemplateResult("Value 3"),
            value: "value_3",
            is_disabled: false,
            group_id: "group2",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(fifth_entry[0]).toBe("link-selector-item-group2-value_4");
        expect(fifth_entry[1]).toStrictEqual({
            id: "link-selector-item-group2-value_4",
            template: buildTemplateResult("Value 4"),
            value: "value_4",
            is_disabled: false,
            group_id: "group2",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
        expect(sixth_entry[0]).toBe("link-selector-item-group2-value_5");
        expect(sixth_entry[1]).toStrictEqual({
            id: "link-selector-item-group2-value_5",
            template: buildTemplateResult("Value 5"),
            value: "value_5",
            is_disabled: false,
            group_id: "group2",
            is_selected: false,
            element: expect.any(Element),
            target_option: expect.any(Element),
        });
    });
});

function buildTemplateResult(value: string): HTMLTemplateResult {
    return html`
        ${value}
    `;
}
