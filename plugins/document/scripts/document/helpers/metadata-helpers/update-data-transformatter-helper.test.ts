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
    formatCustomMetadataForFolderUpdate,
    transformCustomMetadataForItemUpdate,
    transformDocumentMetadataForUpdate,
    transformFolderMetadataForRecursionAtUpdate,
} from "./update-data-transformatter-helper";
import type { Folder, ItemFile } from "../../type";
import type { Metadata, FolderMetadata, ListValue } from "../../store/metadata/module";

describe("transformFolderMetadataForRecursionAtUpdate", () => {
    it("Given an existing folder, then we add specific status update key for update", () => {
        const list_value: Array<ListValue> = [
            {
                id: 103,
                value: "Open",
            } as ListValue,
        ];
        const metadata: Metadata = {
            short_name: "status",
            list_value: list_value,
        } as Metadata;
        const item: Folder = {
            id: 7,
            type: "folder",
            metadata: [metadata],
        } as Folder;

        const item_to_update: Folder = {
            ...item,
            status: {
                value: "rejected",
                recursion: "none",
            },
        };

        expect(transformFolderMetadataForRecursionAtUpdate(item)).toEqual(item_to_update);
    });
});

describe("transformDocumentMetadataForUpdate", () => {
    it("Given status metadata is used, then the default status metadata is applied", () => {
        const list_value: Array<ListValue> = [
            {
                id: 103,
                value: "Open",
            } as ListValue,
        ];
        const metadata: Array<Metadata> = [
            {
                short_name: "status",
                list_value: list_value,
            } as Metadata,
        ];

        const item: ItemFile = {
            id: 7,
            type: "file",
            metadata: metadata,
        } as unknown as ItemFile;

        transformDocumentMetadataForUpdate(item, true);

        expect(item.status).toEqual("rejected");
    });

    it("Given status is not used, then the status is not updated", () => {
        const list_value: Array<ListValue> = [
            {
                id: 103,
                value: "Open",
            } as ListValue,
        ];
        const metadata: Array<Metadata> = [
            {
                short_name: "status",
                list_value: list_value,
            } as Metadata,
        ];

        const item: ItemFile = {
            id: 7,
            type: "file",
            metadata: metadata,
        } as unknown as ItemFile;

        transformDocumentMetadataForUpdate(item, false);

        expect(item.status).toEqual(undefined);
    });
});

describe("transformCustomMetadataForItemUpdate", () => {
    it(`Given parent has a text value,
        it does not update anything`, () => {
        const parent_metadata: Array<Metadata> = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "value",
                type: "text",
                is_multiple_value_allowed: false,
                is_required: false,
            } as Metadata,
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(parent_metadata).toEqual(parent_metadata);
    });

    it(`Given parent has a string value,
        it does not update anything`, () => {
        const parent_metadata: Array<Metadata> = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "value",
                type: "string",
                is_multiple_value_allowed: false,
                is_required: false,
            } as Metadata,
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(parent_metadata).toEqual(parent_metadata);
    });

    it(`Given parent has a single list value,
        then the formatted metadata is bound to value`, () => {
        const list_values: Array<ListValue> = [
            {
                id: 110,
                value: "My value to display",
            } as ListValue,
        ];
        const parent_metadata: Array<Metadata> = [
            {
                short_name: "custom metadata",
                name: "field_1",
                list_value: list_values,
                is_multiple_value_allowed: false,
                type: "list",
                is_required: false,
            } as Metadata,
        ];

        const expected_list: Array<Metadata> = [
            {
                short_name: "custom metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: 110,
                is_required: false,
                list_value: null,
            } as Metadata,
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });

    it(`Given parent has a list with single value, and given list value is null,
        then the formatted metadata is bound to none`, () => {
        const parent_metadata = [
            {
                short_name: "custom list metadata",
                name: "field_1",
                list_value: [],
                is_multiple_value_allowed: false,
                type: "list",
                is_required: false,
            } as Metadata,
        ];

        const expected_list: Array<Metadata> = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: 100,
                is_required: false,
                list_value: null,
            } as Metadata,
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });

    it(`Given parent has a multiple list
        then the formatted metadata only keeps list ids`, () => {
        const list_values: Array<ListValue> = [
            {
                id: 110,
                value: "My value to display",
            },
            {
                id: 120,
                value: "My other value to display",
            },
        ];
        const parent_metadata: Array<Metadata> = [
            {
                short_name: "custom list metadata",
                name: "field_1",
                list_value: list_values,
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
            } as Metadata,
        ];

        const expected_value: Array<number> = [110, 120];
        const expected_list: Array<Metadata> = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: expected_value,
                is_required: false,
                value: null,
            } as Metadata,
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });

    it(`Given parent has a multiple list without any value
        then the formatted metadata should have the 100 id`, () => {
        const parent_metadata: Array<Metadata> = [
            {
                short_name: "custom list metadata",
                name: "field_1",
                list_value: [],
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
            } as Metadata,
        ];

        const expected_value: Array<number> = [100];
        const expected_list: Array<Metadata> = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: expected_value,
                is_required: false,
                value: null,
            } as Metadata,
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });
    it(`Given parent has a date value,
        then the formatted date metadata is bound to value with the formatted date`, () => {
        const parent_metadata: Array<Metadata> = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "2019-08-30T00:00:00+02:00",
                type: "date",
                is_multiple_value_allowed: false,
                is_required: false,
            } as Metadata,
        ];

        const expected_list: Array<Metadata> = [
            {
                short_name: "custom metadata",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "2019-08-30",
                is_required: false,
            } as Metadata,
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });
    it(`Given parent does not have a date value,
        then the formatted date metadata is bound to value with empty string`, () => {
        const parent_metadata: Array<Metadata> = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: null,
                type: "date",
                is_multiple_value_allowed: false,
                is_required: false,
            } as Metadata,
        ];

        const expected_list: Array<Metadata> = [
            {
                short_name: "custom metadata",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "",
                is_required: false,
            } as Metadata,
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });
});

describe("formatCustomMetadataForFolderUpdate", () => {
    it(`Given an item with metadata to update, a list of metadata short name and a recursion option ,
        then each metadata of the item to update has a recursion option`, () => {
        const folder_metadata: Array<FolderMetadata> = [
            { short_name: "field_1" } as FolderMetadata,
            { short_name: "field_2" } as FolderMetadata,
            { short_name: "field_3" } as FolderMetadata,
            { short_name: "field_4" } as FolderMetadata,
        ];
        const item_to_update: Folder = {
            id: 1,
            metadata: folder_metadata,
        } as Folder;

        const metadata_list_to_update = ["field_2", "field_4"];
        const recursion_option = "folders";

        const expected_item_to_update = {
            id: 1,
            metadata: [
                { short_name: "field_1", recursion: "none" },
                { short_name: "field_2", recursion: "folders" },
                { short_name: "field_3", recursion: "none" },
                { short_name: "field_4", recursion: "folders" },
            ],
        };

        formatCustomMetadataForFolderUpdate(
            item_to_update,
            metadata_list_to_update,
            recursion_option
        );

        expect(item_to_update).toEqual(expected_item_to_update);
    });
});
