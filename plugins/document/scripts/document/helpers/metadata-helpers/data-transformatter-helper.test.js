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
    transformCustomMetadataForItemCreation,
    transformCustomMetadataForItemUpdate,
    transformDocumentMetadataForUpdate,
    transformFolderMetadataForRecursionAtUpdate,
    transformItemMetadataForCreation,
} from "./data-transformatter-helper.js";

describe("transformFolderMetadataForRecursionAtUpdate", () => {
    it("Given an existing folder, then we add specific status update key for update", () => {
        const item = {
            id: 7,
            type: "folder",
            metadata: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103,
                        },
                    ],
                },
            ],
        };

        const item_to_update = {
            ...item,
            status: {
                value: "rejected",
                recursion: "none",
            },
        };

        expect(transformFolderMetadataForRecursionAtUpdate(item)).toEqual(item_to_update);
    });
});

describe("transformDocumentMetadataForCreation", () => {
    it("Given an existing document, then the default status metadata is the parent one", () => {
        const item = {
            id: 7,
            type: "file",
        };

        const parent = {
            id: 7,
            type: "folder",
            metadata: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103,
                        },
                    ],
                },
            ],
        };

        transformItemMetadataForCreation(item, parent, true);

        expect(item.status).toEqual("rejected");
    });

    it("Given an existing document, when status is not used, default status is not set regardless of parent configuration", () => {
        const item = {
            id: 7,
            type: "file",
        };

        const parent = {
            id: 7,
            type: "folder",
            metadata: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103,
                        },
                    ],
                },
            ],
        };

        transformItemMetadataForCreation(item, parent, false);

        expect(item.status).toEqual(undefined);
    });
});

describe("transformDocumentMetadataForUpdate", () => {
    it("Given an existing document, then the default status metadata is applied", () => {
        const item = {
            id: 7,
            type: "file",
            metadata: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103,
                        },
                    ],
                },
            ],
        };

        transformDocumentMetadataForUpdate(item, true);

        expect(item.status).toEqual("rejected");
    });

    it("Given an existing document, the status is not updated", () => {
        const item = {
            id: 7,
            type: "file",
            metadata: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103,
                        },
                    ],
                },
            ],
        };

        transformDocumentMetadataForUpdate(item, false);

        expect(item.status).toEqual(undefined);
    });
});

describe("transformCustomMetadataForItemCreation", () => {
    it("Given parent has no metadata then it returns an empty array", () => {
        const parent_metadata = [];

        const formatted_result = transformCustomMetadataForItemCreation(parent_metadata);

        expect(formatted_result).toEqual([]);
    });

    it(`Given parent has a text value,
        then the formatted metadata is bound to value`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "value",
                type: "text",
                is_multiple_value_allowed: false,
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "text",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "value",
                is_required: false,
            },
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a string value,
        then the formatted metadata is bound to value`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "value",
                type: "string",
                is_multiple_value_allowed: false,
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "string",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "value",
                is_required: false,
            },
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a single list value,
        then the formatted metadata is bound to value`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                list_value: [
                    {
                        id: 110,
                        value: "My value to display",
                    },
                ],
                is_multiple_value_allowed: false,
                type: "list",
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: 110,
                is_required: false,
            },
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
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
            },
        ];

        const expected_list = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: 100,
                is_required: false,
            },
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a multiple list
        then the formatted metadata only keeps list ids`, () => {
        const parent_metadata = [
            {
                short_name: "custom list metadata",
                name: "field_1",
                list_value: [
                    {
                        id: 110,
                        value: "My value to display",
                    },
                    {
                        id: 120,
                        value: "My other value to display",
                    },
                ],
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: [110, 120],
                is_required: false,
            },
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
    });

    it(`Given parent has a multiple list without any value
        then the formatted metadata should have the 100 id`, () => {
        const parent_metadata = [
            {
                short_name: "custom list metadata",
                name: "field_1",
                list_value: [],
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: [100],
                is_required: false,
            },
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
    });
    it(`Given parent has a date value,
        then the formatted date metadata is bound to value with the formatted date`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "2019-08-30T00:00:00+02:00",
                type: "date",
                is_multiple_value_allowed: false,
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "2019-08-30",
                is_required: false,
            },
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
    });
    it(`Given parent does not have a date value,
        then the formatted date metadata is bound to value with empty string`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: null,
                type: "date",
                is_multiple_value_allowed: false,
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "",
                is_required: false,
            },
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
    });
});

describe("transformCustomMetadataForItemUpdate", () => {
    it(`Given parent has a text value,
        it does not update anything`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "value",
                type: "text",
                is_multiple_value_allowed: false,
                is_required: false,
            },
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(parent_metadata).toEqual(parent_metadata);
    });

    it(`Given parent has a string value,
        it does not update anything`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "value",
                type: "string",
                is_multiple_value_allowed: false,
                is_required: false,
            },
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(parent_metadata).toEqual(parent_metadata);
    });

    it(`Given parent has a single list value,
        then the formatted metadata is bound to value`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                list_value: [
                    {
                        id: 110,
                        value: "My value to display",
                    },
                ],
                is_multiple_value_allowed: false,
                type: "list",
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: 110,
                is_required: false,
                list_value: null,
            },
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
            },
        ];

        const expected_list = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: 100,
                is_required: false,
                list_value: null,
            },
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });

    it(`Given parent has a multiple list
        then the formatted metadata only keeps list ids`, () => {
        const parent_metadata = [
            {
                short_name: "custom list metadata",
                name: "field_1",
                list_value: [
                    {
                        id: 110,
                        value: "My value to display",
                    },
                    {
                        id: 120,
                        value: "My other value to display",
                    },
                ],
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: [110, 120],
                is_required: false,
                value: null,
            },
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });

    it(`Given parent has a multiple list without any value
        then the formatted metadata should have the 100 id`, () => {
        const parent_metadata = [
            {
                short_name: "custom list metadata",
                name: "field_1",
                list_value: [],
                is_multiple_value_allowed: true,
                type: "list",
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom list metadata",
                type: "list",
                name: "field_1",
                is_multiple_value_allowed: true,
                list_value: [100],
                is_required: false,
                value: null,
            },
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });
    it(`Given parent has a date value,
        then the formatted date metadata is bound to value with the formatted date`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: "2019-08-30T00:00:00+02:00",
                type: "date",
                is_multiple_value_allowed: false,
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "2019-08-30",
                is_required: false,
            },
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });
    it(`Given parent does not have a date value,
        then the formatted date metadata is bound to value with empty string`, () => {
        const parent_metadata = [
            {
                short_name: "custom metadata",
                name: "field_1",
                value: null,
                type: "date",
                is_multiple_value_allowed: false,
                is_required: false,
            },
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "date",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "",
                is_required: false,
            },
        ];

        transformCustomMetadataForItemUpdate(parent_metadata);

        expect(expected_list).toEqual(parent_metadata);
    });
});

describe("formatCustomMetadataForFolderUpdate", () => {
    it(`Given an item with metadata to update, a list of metadata short name and a recursion option ,
        then each metadata of the item to update has a recursion option`, () => {
        const item_to_update = {
            id: 1,
            metadata: [
                { short_name: "field_1" },
                { short_name: "field_2" },
                { short_name: "field_3" },
                { short_name: "field_4" },
            ],
        };

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
