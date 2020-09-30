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

    it("generates the map of the available options inside the source <select>, ignoring options with no value", () => {
        appendSimpleOptionsToSourceSelectBox(select);

        const map = generateItemMapBasedOnSourceSelectOptions(select);

        expect(map.size).toEqual(3);

        const iterator = map.entries();
        expect(iterator.next().value).toEqual([
            "item-0",
            {
                id: "item-0",
                template: "Value 0",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "item-1",
            {
                id: "item-1",
                template: "Value 1",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "item-2",
            {
                id: "item-2",
                template: "Value 2",
                is_disabled: false,
                group_id: "",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
    });

    it("generates the map of the available grouped options inside the source <select>, ignoring options with no value", () => {
        appendGroupedOptionsToSourceSelectBox(select);

        const map = generateItemMapBasedOnSourceSelectOptions(select);

        expect(map.size).toEqual(6);

        const iterator = map.entries();
        expect(iterator.next().value).toEqual([
            "item-0",
            {
                id: "item-0",
                template: "Value 0",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "item-1",
            {
                id: "item-1",
                template: "Value 1",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "item-2",
            {
                id: "item-2",
                template: "Value 2",
                is_disabled: false,
                group_id: "group1",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "item-3",
            {
                id: "item-3",
                template: "Value 3",
                is_disabled: false,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "item-4",
            {
                id: "item-4",
                template: "Value 4",
                is_disabled: false,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
        expect(iterator.next().value).toEqual([
            "item-5",
            {
                id: "item-5",
                template: "Value 5",
                is_disabled: true,
                group_id: "group2",
                is_selected: false,
                element: expect.any(Element),
                target_option: expect.any(Element),
            },
        ]);
    });
});
