/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import {
    formatCustomPropertiesForFolderUpdate,
    transformCustomPropertiesForItemUpdate,
    transformDocumentPropertiesForUpdate,
    transformFolderPropertiesForRecursionAtUpdate,
} from "./update-data-transformatter-helper";
import type { Folder, ItemFile, Property, ListValue } from "../../type";

describe("transformFolderPropertiesForRecursionAtUpdate", () => {
    it("Given an existing folder, then we add specific status update key for update", () => {
        const list_value: Array<ListValue> = [
            {
                id: 103,
                name: "Open",
            } as ListValue,
        ];
        const property: Property = {
            short_name: "status",
            list_value: list_value,
        } as Property;
        const item: Folder = {
            id: 7,
            type: "folder",
            properties: [property],
        } as Folder;

        const item_to_update: Folder = {
            ...item,
            status: {
                value: "rejected",
                recursion: "none",
            },
        };
        const is_status_property_used = true;
        expect(
            transformFolderPropertiesForRecursionAtUpdate(item, is_status_property_used)
        ).toStrictEqual(item_to_update);
    });
    it("Given an existing folder, then we add 'none' status update key for update if status is not used", () => {
        const list_value: Array<ListValue> = [
            {
                id: 103,
                name: "Open",
            } as ListValue,
        ];
        const property: Property = {
            short_name: "status",
            list_value: list_value,
        } as Property;
        const item: Folder = {
            id: 7,
            type: "folder",
            properties: [property],
        } as Folder;

        const item_to_update: Folder = {
            ...item,
            status: {
                value: "none",
                recursion: "none",
            },
        };
        const is_status_property_used = false;
        expect(
            transformFolderPropertiesForRecursionAtUpdate(item, is_status_property_used)
        ).toStrictEqual(item_to_update);
    });
});

describe("transformDocumentPropertiesForUpdate", () => {
    it("Given status property is used, then the default status property is applied", () => {
        const list_value: Array<ListValue> = [
            {
                id: 103,
                name: "Open",
            } as ListValue,
        ];
        const properties: Array<Property> = [
            {
                short_name: "status",
                list_value: list_value,
            } as Property,
        ];

        const item: ItemFile = {
            id: 7,
            type: "file",
            properties,
        } as unknown as ItemFile;

        transformDocumentPropertiesForUpdate(item, true);

        expect(item.status).toBe("rejected");
    });

    it("Given status is not used, then the status is not updated", () => {
        const list_value: Array<ListValue> = [
            {
                id: 103,
                name: "Open",
            } as ListValue,
        ];
        const properties: Array<Property> = [
            {
                short_name: "status",
                list_value: list_value,
            } as Property,
        ];

        const item: ItemFile = {
            id: 7,
            type: "file",
            properties,
        } as unknown as ItemFile;

        transformDocumentPropertiesForUpdate(item, false);

        expect(item.status).toBeUndefined();
    });

    it("Given item has no properties key, it does not throw an error", () => {
        const item: ItemFile = {
            id: 7,
            type: "file",
        } as unknown as ItemFile;

        transformDocumentPropertiesForUpdate(item, false);

        expect(item.status).toBeUndefined();
    });
});

describe("transformCustomPropertiesForItemUpdate", () => {
    it(`Given parent has a text value,
        it does not update anything`, () => {
        const parent_properties: Array<Property> = [
            {
                short_name: "custom property",
                name: "field_1",
                value: "value",
                type: "text",
                is_multiple_value_allowed: false,
                is_required: false,
            } as Property,
        ];

        transformCustomPropertiesForItemUpdate(parent_properties);

        expect(parent_properties).toStrictEqual(parent_properties);
    });

    it(`Given parent has a string value,
        it does not update anything`, () => {
        const parent_properties: Array<Property> = [
            {
                short_name: "custom property",
                name: "field_1",
                value: "value",
                type: "string",
                is_multiple_value_allowed: false,
                is_required: false,
            } as Property,
        ];

        transformCustomPropertiesForItemUpdate(parent_properties);

        expect(parent_properties).toStrictEqual(parent_properties);
    });

    it(`Given parent has a single list value,
        then the formatted properties is bound to value`, () => {
        const list_values: Array<ListValue> = [
            {
                id: 110,
                name: "My value to display",
            } as ListValue,
        ];
        const parent_properties: Array<Property> = [
            {
                short_name: "custom property",
                name: "field_1",
                list_value: list_values,
                is_multiple_value_allowed: false,
                type: "list",
                is_required: false,
            } as Property,
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
            } as Property,
        ];

        transformCustomPropertiesForItemUpdate(parent_properties);

        expect(expected_list).toStrictEqual(parent_properties);
    });

    it(`Given parent has a list with single value, and given list value is null,
        then the formatted properties is bound to none`, () => {
        const parent_properties = [
            {
                short_name: "custom list property",
                name: "field_1",
                list_value: [],
                is_multiple_value_allowed: false,
                type: "list",
                is_required: false,
            } as Property,
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
            } as Property,
        ];

        transformCustomPropertiesForItemUpdate(parent_properties);

        expect(expected_list).toStrictEqual(parent_properties);
    });

    it(`Given parent has a multiple list
        then the formatted properties only keeps list ids`, () => {
        const list_values: Array<ListValue> = [
            {
                id: 110,
                name: "My value to display",
            },
            {
                id: 120,
                name: "My other value to display",
            },
        ];
        const parent_properties: Array<Property> = [
            {
                short_name: "custom list property",
                name: "field_1",
                list_value: list_values,
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
            } as Property,
        ];

        const expected_value: Array<number> = [110, 120];
        const expected_list: Array<Property> = [
            {
                short_name: "custom list property",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: expected_value,
                is_required: false,
                value: null,
            } as Property,
        ];

        transformCustomPropertiesForItemUpdate(parent_properties);

        expect(expected_list).toStrictEqual(parent_properties);
    });

    it(`Given parent has a multiple list without any value
        then the formatted properties should have the 100 id`, () => {
        const parent_properties: Array<Property> = [
            {
                short_name: "custom list property",
                name: "field_1",
                list_value: [],
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
            } as Property,
        ];

        const expected_value: Array<number> = [100];
        const expected_list: Array<Property> = [
            {
                short_name: "custom list property",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: expected_value,
                is_required: false,
                value: null,
            } as Property,
        ];

        transformCustomPropertiesForItemUpdate(parent_properties);

        expect(expected_list).toStrictEqual(parent_properties);
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
            } as Property,
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom property",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "2019-08-30",
                is_required: false,
            } as Property,
        ];

        transformCustomPropertiesForItemUpdate(parent_properties);

        expect(expected_list).toStrictEqual(parent_properties);
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
            } as Property,
        ];

        const expected_list: Array<Property> = [
            {
                short_name: "custom property",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "",
                is_required: false,
            } as Property,
        ];

        transformCustomPropertiesForItemUpdate(parent_properties);

        expect(expected_list).toStrictEqual(parent_properties);
    });
});

describe("formatCustomPropertiesForFolderUpdate", () => {
    it(`Given an item with properties to update, a list of properties short name and a recursion option ,
        then each property of the item to update has a recursion option`, () => {
        const parent_properties: Array<Property> = [
            { short_name: "field_1" } as Property,
            { short_name: "field_2" } as Property,
            { short_name: "field_3" } as Property,
            { short_name: "field_4" } as Property,
        ];
        const item_to_update: Folder = {
            id: 1,
            properties: parent_properties,
        } as Folder;

        const properties_to_update = ["field_2", "field_4"];
        const recursion_option = "folders";

        const expected_item_to_update = {
            id: 1,
            properties: [
                { short_name: "field_1", recursion: "none" },
                { short_name: "field_2", recursion: "folders" },
                { short_name: "field_3", recursion: "none" },
                { short_name: "field_4", recursion: "folders" },
            ],
        };

        formatCustomPropertiesForFolderUpdate(
            item_to_update,
            properties_to_update,
            recursion_option
        );

        expect(item_to_update).toStrictEqual(expected_item_to_update);
    });
});
