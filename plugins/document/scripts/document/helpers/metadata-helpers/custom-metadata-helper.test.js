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

import { getCustomMetadata } from "./custom-metadata-helper.js";

describe("getCustomMetadata", () => {
    it("only returns custom metadata", () => {
        const metadata = [
            { short_name: "title" },
            { short_name: "description" },
            { short_name: "owner" },
            { short_name: "create_date" },
            { short_name: "update_date" },
            { short_name: "field_1" },
            { short_name: "field_2" },
            { short_name: "field_3" },
        ];

        expect(getCustomMetadata(metadata)).toEqual([
            { short_name: "field_1" },
            { short_name: "field_2" },
            { short_name: "field_3" },
        ]);
    });
    it("Returns empty array if metadata is not defined", () => {
        expect(getCustomMetadata(null)).toEqual([]);
    });
});
