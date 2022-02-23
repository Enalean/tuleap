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

import { getCustomProperties } from "./custom-properties-helper";
import type { Property } from "../../store/properties/module";

describe("getCustomProperties", () => {
    it("only returns custom properties", () => {
        const properties = [
            { short_name: "title" } as Property,
            { short_name: "description" } as Property,
            { short_name: "owner" } as Property,
            { short_name: "create_date" } as Property,
            { short_name: "update_date" } as Property,
            { short_name: "field_1" } as Property,
            { short_name: "field_2" } as Property,
            { short_name: "field_3" } as Property,
        ];

        expect(getCustomProperties(properties)).toEqual([
            { short_name: "field_1" },
            { short_name: "field_2" },
            { short_name: "field_3" },
        ]);
    });
    it("Returns empty array if properties is not defined", () => {
        expect(getCustomProperties(null)).toEqual([]);
    });
});
