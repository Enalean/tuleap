/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { buildAdvancedSearchParams } from "./build-advanced-search-params";
import type { AdvancedSearchParams } from "../type";

describe("build-advanced-search-params", () => {
    it("should return an empty search params by default", () => {
        const expected: AdvancedSearchParams = {
            global_search: "",
            id: "",
            type: "",
            filename: "",
            title: "",
            description: "",
            owner: "",
            create_date: null,
            update_date: null,
            obsolescence_date: null,
            status: "",
            sort: { name: "update_date", order: "desc" },
        };
        expect(buildAdvancedSearchParams()).toStrictEqual(expected);
    });

    it("should accept partial params", () => {
        const expected: AdvancedSearchParams = {
            global_search: "",
            id: "",
            type: "folder",
            filename: "",
            title: "lorem",
            description: "",
            owner: "",
            create_date: null,
            update_date: null,
            obsolescence_date: null,
            status: "",
            field_2: "ipsum",
            sort: { name: "update_date", order: "desc" },
        };
        expect(
            buildAdvancedSearchParams({ title: "lorem", type: "folder", field_2: "ipsum" }),
        ).toStrictEqual(expected);
    });
});
