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

import { transformFolderMetadataForRecursionAtUpdate } from "./update-data-transformatter-helper.js";

describe("transformItemToUpdate", () => {
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
