/**
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

import { getCustomMetadata } from "./custom-metadata-helper";
import type { Metadata } from "../../store/metadata/module";

describe("getCustomMetadata", () => {
    it("only returns custom metadata", () => {
        const metadata = [
            { short_name: "title" } as Metadata,
            { short_name: "description" } as Metadata,
            { short_name: "owner" } as Metadata,
            { short_name: "create_date" } as Metadata,
            { short_name: "update_date" } as Metadata,
            { short_name: "field_1" } as Metadata,
            { short_name: "field_2" } as Metadata,
            { short_name: "field_3" } as Metadata,
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
