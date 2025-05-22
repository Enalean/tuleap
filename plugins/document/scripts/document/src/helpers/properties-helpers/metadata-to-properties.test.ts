/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

import { describe, expect, it } from "vitest";
import type { Item, Property } from "../../type";
import type { RestItem } from "../../api/rest-querier";
import { convertArrayOfItems } from "./metadata-to-properties";

describe("metadataToProperties", () => {
    it("Transform the key metadata into properties", () => {
        const properties: Array<Property> = [
            {
                short_name: "custom list property",
                name: "field_1",
                list_value: [],
                is_multiple_value_allowed: false,
                type: "list",
                is_required: false,
                is_used: true,
                description: "",
                value: null,
                allowed_list_values: null,
            },
            {
                short_name: "test property",
                name: "field_2",
                list_value: [],
                is_multiple_value_allowed: false,
                type: "text",
                is_required: false,
                is_used: true,
                description: "",
                value: "aaaa",
                allowed_list_values: null,
            },
        ];

        const rest_item: RestItem = {
            id: 1,
            title: "My item",
            description: "description",
            metadata: properties,
        } as RestItem;

        const item: Item = {
            id: 1,
            title: "My item",
            description: "description",
            properties,
        } as Item;

        expect(convertArrayOfItems([rest_item])).toEqual([item]);
    });
});
