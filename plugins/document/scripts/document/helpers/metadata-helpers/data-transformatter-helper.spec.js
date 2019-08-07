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
    transformFolderMetadataForRecursionAtUpdate,
    transformItemMetadataForCreation,
    transformDocumentMetadataForUpdate,
    transformCustomMetadataForItemCreation
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
                            id: 103
                        }
                    ]
                }
            ]
        };

        const item_to_update = {
            ...item,
            status: {
                value: "rejected",
                recursion: "none"
            }
        };

        expect(transformFolderMetadataForRecursionAtUpdate(item)).toEqual(item_to_update);
    });
});

describe("transformDocumentMetadataForCreation", () => {
    it("Given an existing document, then the default status metadata is the parent one", () => {
        const item = {
            id: 7,
            type: "file"
        };

        const parent = {
            id: 7,
            type: "folder",
            metadata: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103
                        }
                    ]
                }
            ]
        };

        transformItemMetadataForCreation(item, parent, true);

        expect(item.status).toEqual("rejected");
    });

    it("Given an existing document, when status is not used, default status is not set regardless of parent configuration", () => {
        const item = {
            id: 7,
            type: "file"
        };

        const parent = {
            id: 7,
            type: "folder",
            metadata: [
                {
                    short_name: "status",
                    list_value: [
                        {
                            id: 103
                        }
                    ]
                }
            ]
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
                            id: 103
                        }
                    ]
                }
            ]
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
                            id: 103
                        }
                    ]
                }
            ]
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
                is_multiple_value_allowed: false
            }
        ];

        const expected_list = [
            {
                short_name: "custom metadata",
                type: "text",
                name: "field_1",
                is_multiple_value_allowed: false,
                value: "value"
            }
        ];

        const formatted_list = transformCustomMetadataForItemCreation(parent_metadata);

        expect(expected_list).toEqual(formatted_list);
    });
});
