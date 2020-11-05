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

import { generateItemMapBasedOnSourceSelectOptions } from "./static-list-helper";
import {
    appendGroupedOptionsToSourceSelectBox,
    appendSimpleOptionsToSourceSelectBox,
} from "../test-helpers/select-box-options-generator";

describe("static-list-helper", () => {
    let select: HTMLSelectElement;

    beforeEach(() => {
        select = document.createElement("select");
    });

    it("generates the map of the available options inside the source <select>", () => {
        appendSimpleOptionsToSourceSelectBox(select);

        const map = generateItemMapBasedOnSourceSelectOptions(select);

        expect(map.size).toEqual(4);

        const iterator = map.entries();
        expect(iterator.next().value).toEqual([
            "list-picker-item-value_0",
            {
                id: "list-picker-item-value_0",
                template: "Value 0",
                value: "value_0",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "list-picker-item-value_1",
            {
                id: "list-picker-item-value_1",
                template: "Value 1",
                value: "value_1",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "list-picker-item-value_2",
            {
                id: "list-picker-item-value_2",
                template: "Value 2",
                value: "value_2",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "list-picker-item-value_3",
            {
                id: "list-picker-item-value_3",
                template: "Value 3",
                value: "value_3",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
    });

    it("generates the map of the available grouped options inside the source <select>", () => {
        appendGroupedOptionsToSourceSelectBox(select);

        const map = generateItemMapBasedOnSourceSelectOptions(select);

        expect(map.size).toEqual(6);

        const iterator = map.entries();
        expect(iterator.next().value).toEqual([
            "list-picker-item-group1-value_0",
            {
                id: "list-picker-item-group1-value_0",
                template: "Value 0",
                value: "value_0",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "list-picker-item-group1-value_1",
            {
                id: "list-picker-item-group1-value_1",
                template: "Value 1",
                value: "value_1",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "list-picker-item-group1-value_2",
            {
                id: "list-picker-item-group1-value_2",
                template: "Value 2",
                value: "value_2",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "list-picker-item-group2-value_3",
            {
                id: "list-picker-item-group2-value_3",
                template: "Value 3",
                value: "value_3",
                is_disabled: false,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "list-picker-item-group2-value_4",
            {
                id: "list-picker-item-group2-value_4",
                template: "Value 4",
                value: "value_4",
                is_disabled: false,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "list-picker-item-group2-value_5",
            {
                id: "list-picker-item-group2-value_5",
                template: "Value 5",
                value: "value_5",
                is_disabled: true,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
    });

    it("should ignore options with empty value attribute and remove them from the source <select> options", () => {
        appendSimpleOptionsToSourceSelectBox(select);
        const map = generateItemMapBasedOnSourceSelectOptions(select);
        const item_with_empty_value = Array.from(map.values()).find((item) => {
            return item.value === "";
        });
        expect(item_with_empty_value).toBeUndefined();
        expect(select.querySelector("option[value='']")).toBeNull();
    });

    it("should ignore empty options in the angular-modal remove it from the source <select> options", () => {
        appendSimpleOptionsToSourceSelectBox(select);
        select.options[0].value = "?";

        const map = generateItemMapBasedOnSourceSelectOptions(select);
        const item_with_empty_value = Array.from(map.values()).find((item) => {
            return item.value === "?";
        });
        expect(item_with_empty_value).toBeUndefined();
        expect(select.querySelector("option[value='?']")).toBeNull();
    });
});
