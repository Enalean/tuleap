/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import {
    transformCustomPropertiesForItemCreation,
    transformStatusPropertyForItemCreation,
} from "./creation-data-transformatter-helper";
import type { Folder, Property, ListValue } from "../../type";

describe("creation properties transformer", () => {
    it("Given an existing document, then the default status property is the parent one", () => {
        const item = {
            id: 7,
            type: "folder",
        } as Folder;

        const list_value: Array<ListValue> = [
            {
                id: 103,
            } as ListValue,
        ];

        const parent = {
            id: 7,
            type: "folder",
            properties: [
                {
                    short_name: "status",
                    list_value: list_value,
                } as Property,
            ],
        } as Folder;

        transformStatusPropertyForItemCreation(item, parent, true);

        expect(item.status).toBe("rejected");
    });

    it("Given an existing document, when status is not used, default status is not set regardless of parent configuration", () => {
        const item = {
            id: 7,
            type: "folder",
        } as Folder;

        const list_value: Array<ListValue> = [
            {
                id: 103,
            } as ListValue,
        ];

        const parent = {
            id: 7,
            type: "folder",
            properties: [
                {
                    short_name: "status",
                    list_value: list_value,
                } as Property,
            ],
        } as Folder;

        transformStatusPropertyForItemCreation(item, parent, false);

        expect(item.status).toBeUndefined();
    });

    it("Given parent has no properties then it returns an empty array", () => {
        const parent_properties: Array<Property> = [];

        const formatted_result = transformCustomPropertiesForItemCreation(parent_properties);

        expect(formatted_result).toEqual([]);
    });

    it("Given parent properties are not set then it returns an empty array", () => {
        const parent_properties = null;

        const formatted_result = transformCustomPropertiesForItemCreation(parent_properties);

        expect(formatted_result).toEqual([]);
    });

    it(`Given parent has a text value,
        then the formatted property is bound to value`, () => {
        const parent_properties: Array<Property> = [
            {
                short_name: "custom property",
                name: "field_1",
                value: "value",
                type: "text",
                is_multiple_value_allowed: false,
                is_required: false,
                list_value: null,
                is_used: true,
                description: "",
                allowed_list_values: null,
            },
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom property",
                type: "text",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "value",
                is_required: false,
                list_value: null,
                description: "",
                is_used: true,
                allowed_list_values: null,
            },
        ];

        const formatted_list = transformCustomPropertiesForItemCreation(parent_properties);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a string value,
        then the formatted property is bound to value`, () => {
        const parent_properties: Array<Property> = [
            {
                short_name: "custom property",
                name: "field_1",
                value: "value",
                type: "string",
                is_multiple_value_allowed: false,
                is_required: false,
                list_value: null,
                is_used: true,
                description: "",
                allowed_list_values: null,
            },
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom property",
                type: "string",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "value",
                is_required: false,
                list_value: null,
                description: "",
                is_used: true,
                allowed_list_values: null,
            },
        ];

        const formatted_list = transformCustomPropertiesForItemCreation(parent_properties);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a single list value,
        then the formatted property is bound to value`, () => {
        const list_value: Array<ListValue> = [
            {
                id: 110,
                name: "My value to display",
            } as ListValue,
        ];

        const parent_properties: Array<Property> = [
            {
                short_name: "custom property",
                name: "field_1",
                list_value: list_value,
                is_multiple_value_allowed: false,
                type: "list",
                is_required: false,
                is_used: true,
                description: "",
                value: null,
                allowed_list_values: null,
            },
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom property",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: 110,
                is_required: false,
                list_value: null,
                description: "",
                is_used: true,
                allowed_list_values: null,
            },
        ];

        const formatted_list = transformCustomPropertiesForItemCreation(parent_properties);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a list with single value, and given list value is null,
        then the formatted property is bound to none`, () => {
        const parent_properties: Array<Property> = [
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
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom list property",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: 100,
                is_required: false,
                list_value: null,
                description: "",
                is_used: true,
                allowed_list_values: null,
            },
        ];

        const formatted_list = transformCustomPropertiesForItemCreation(parent_properties);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a multiple list
        then the formatted property only keeps list ids`, () => {
        const list_value: Array<ListValue> = [
            {
                id: 110,
                name: "My value to display",
            } as ListValue,
            {
                id: 120,
                name: "My other value to display",
            } as ListValue,
        ];

        const parent_properties: Array<Property> = [
            {
                short_name: "custom list property",
                name: "field_1",
                list_value: list_value,
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
                is_used: true,
                description: "",
                value: null,
                allowed_list_values: null,
            },
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom list property",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: [110, 120],
                is_required: false,
                value: null,
                description: "",
                is_used: true,
                allowed_list_values: null,
            },
        ];

        const formatted_list = transformCustomPropertiesForItemCreation(parent_properties);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a multiple list without any value
        then the formatted property should have the 100 id`, () => {
        const parent_properties: Array<Property> = [
            {
                short_name: "custom list property",
                name: "field_1",
                list_value: [],
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
                is_used: true,
                description: "",
                value: null,
                allowed_list_values: null,
            },
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom list property",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: [100],
                is_required: false,
                value: null,
                description: "",
                is_used: true,
                allowed_list_values: null,
            },
        ];

        const formatted_list = transformCustomPropertiesForItemCreation(parent_properties);

        expect(expected_list).toEqual(formatted_list);
    });
    it(`Given parent has a date value,
        then the formatted date property is bound to value with the formatted date`, () => {
        const parent_properties: Array<Property> = [
            {
                short_name: "custom property",
                name: "field_1",
                value: "2019-08-30T00:00:00+02:00",
                type: "date",
                is_multiple_value_allowed: false,
                is_required: false,
                is_used: true,
                description: "",
                list_value: null,
                allowed_list_values: null,
            },
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom property",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "2019-08-30",
                is_required: false,
                list_value: null,
                description: "",
                is_used: true,
                allowed_list_values: null,
            },
        ];

        const formatted_list = transformCustomPropertiesForItemCreation(parent_properties);

        expect(expected_list).toEqual(formatted_list);
    });
    it(`Given parent does not have a date value,
        then the formatted date property is bound to value with empty string`, () => {
        const parent_properties: Array<Property> = [
            {
                short_name: "custom property",
                name: "field_1",
                value: null,
                type: "date",
                is_multiple_value_allowed: false,
                is_required: false,
                is_used: true,
                description: "",
                list_value: null,
                allowed_list_values: null,
            },
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom property",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "",
                is_required: false,
                list_value: null,
                description: "",
                is_used: true,
                allowed_list_values: null,
            },
        ];

        const formatted_list = transformCustomPropertiesForItemCreation(parent_properties);

        expect(expected_list).toEqual(formatted_list);
    });
});
